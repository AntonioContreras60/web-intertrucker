<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ======================================================
// CSRF: Generar token si no existe
// ======================================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener el ID del usuario de sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: Falta usuario_id en la sesión.");
}
$usuario_id = $_SESSION['usuario_id'];

// Obtener el admin_id del usuario desde la base de datos
$sql_admin = "SELECT admin_id FROM usuarios WHERE id = ?";
$stmt_admin = $conn->prepare($sql_admin);
if (!$stmt_admin) {
    die("Error en prepare stmt_admin: " . $conn->error);
}
$stmt_admin->bind_param("i", $usuario_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
if ($result_admin->num_rows > 0) {
    $row_admin = $result_admin->fetch_assoc();
    $admin_id = $row_admin['admin_id'] ? $row_admin['admin_id'] : $usuario_id;
} else {
    die("Error: No se encontró el admin_id para el usuario.");
}
$stmt_admin->close();

/***************************************************************************************
 * (1) FILTROS => no_ofrecidos, ofrecidos, asignados, tren
 ***************************************************************************************/
$buscar_no_ofrecidos = isset($_GET['buscar_no_ofrecidos']) ? trim($_GET['buscar_no_ofrecidos']) : '';
$fd_no_ofrecidos     = isset($_GET['fecha_desde_no_ofrecidos']) ? trim($_GET['fecha_desde_no_ofrecidos']) : '';
$fh_no_ofrecidos     = isset($_GET['fecha_hasta_no_ofrecidos']) ? trim($_GET['fecha_hasta_no_ofrecidos']) : '';

$buscar_ofrecidos = isset($_GET['buscar_ofrecidos']) ? trim($_GET['buscar_ofrecidos']) : '';
$fd_ofrecidos     = isset($_GET['fecha_desde_ofrecidos']) ? trim($_GET['fecha_desde_ofrecidos']) : '';
$fh_ofrecidos     = isset($_GET['fecha_hasta_ofrecidos']) ? trim($_GET['fecha_hasta_ofrecidos']) : '';

$buscar_asignados = isset($_GET['buscar_asignados']) ? trim($_GET['buscar_asignados']) : '';
$fd_asignados     = isset($_GET['fecha_desde_asignados']) ? trim($_GET['fecha_desde_asignados']) : '';
$fh_asignados     = isset($_GET['fecha_hasta_asignados']) ? trim($_GET['fecha_hasta_asignados']) : '';

$buscar_tren = isset($_GET['buscar_tren']) ? trim($_GET['buscar_tren']) : '';
$fd_tren     = isset($_GET['fecha_desde_tren']) ? trim($_GET['fecha_desde_tren']) : '';
$fh_tren     = isset($_GET['fecha_hasta_tren']) ? trim($_GET['fecha_hasta_tren']) : '';

/***************************************************************************************
 * (2) Función => buildFilterWhere => Devuelve (WHERE, params, types)
 ***************************************************************************************/
function buildFilterWhere($buscar, $fd, $fh, $alias='p') {
    $where  = "";
    $params = [];
    $types  = "";

    if ($buscar !== '') {
        $like = "%$buscar%";
        $where .= " AND (
            $alias.mercancia_descripcion LIKE ?
            OR $alias.localizacion_recogida LIKE ?
            OR $alias.localizacion_entrega LIKE ?
        )";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= "sss";
    }
    if ($fd !== '') {
        $where .= " AND $alias.fecha_recogida >= ? ";
        $params[] = $fd;
        $types   .= "s";
    }
    if ($fh !== '') {
        $where .= " AND $alias.fecha_recogida <= ? ";
        $params[] = $fh;
        $types   .= "s";
    }
    return [$where, $params, $types];
}

/***************************************************************************************
 * (3) obtenerPortes($conn, $usuario_id, $admin_id, $tipo, $extraWhere, $extraParams, $extraTypes)
 *     => según tipo: no_ofrecidos, ofrecidos, asignados, asociados_trenes
 ***************************************************************************************/
function obtenerPortes($conn, $usuario_id, $admin_id, $tipo, $extraWhere='', $extraParams=[], $extraTypes='') {

    // --------------------------------------------------------------------
    // Base query común, luego según $tipo se ajusta $extra (y a veces la base).
    // --------------------------------------------------------------------
    $baseQuery = "
      SELECT p.id,
             p.mercancia_descripcion,
             p.localizacion_recogida,
             p.localizacion_entrega,
             p.fecha_recogida,
             p.fecha_entrega,
             u.nombre_usuario AS creador_nombre
      FROM portes p
      JOIN usuarios u ON p.usuario_creador_id = u.id
      WHERE u.admin_id=?
        AND p.usuario_creador_id != ?
    ";
    $params = [$admin_id, $usuario_id];
    $types  = "ii";

    $extra = "";

    switch($tipo) {

        case 'no_ofrecidos':
            $extra = "
              AND p.id NOT IN (
                  SELECT DISTINCT p2.id
                  FROM portes p2
                  LEFT JOIN cambios_titularidad ct ON p2.id = ct.porte_id
                  JOIN usuarios u2 ON p2.usuario_creador_id = u2.id
                  WHERE 
                    (p2.usuario_creador_id = ? OR ct.usuario_id_2 = ?)
                    AND (ct.usuario_id_2 IS NULL OR ct.usuario_id_2 = ?)
                    AND (u2.admin_id = ? OR u2.id = ?)
              )
              AND NOT EXISTS (
                  SELECT 1 FROM ofertas_varios ov
                  WHERE ov.porte_id = p.id
                    AND ov.estado_oferta IN ('pendiente','asignado')
              )
              AND NOT EXISTS (
                  SELECT 1 FROM porte_tren pt
                  WHERE pt.porte_id = p.id
              )
            ";
            $params = array_merge($params, [$usuario_id, $usuario_id, $usuario_id, $admin_id, $admin_id]);
            $types  .= "iiiii";
            break;

        case 'ofrecidos':
            $extra = "
              AND EXISTS (
                SELECT 1 FROM ofertas_varios ov
                WHERE ov.porte_id = p.id
                  AND ov.estado_oferta = 'pendiente'
              )
            ";
            break;

        case 'asignados':
            $baseQuery = "
              SELECT DISTINCT
                p.id,
                p.mercancia_descripcion,
                p.localizacion_recogida,
                p.localizacion_entrega,
                p.fecha_recogida,
                p.fecha_entrega,
                u_comp.nombre_usuario AS ofertado_por,
                u_asig.nombre_usuario AS asignado_a,
                u.nombre_usuario AS creador_nombre
              FROM portes p
              LEFT JOIN seleccionados_oferta so ON p.id = so.porte_id
              LEFT JOIN usuarios u_comp ON so.ofertante_id = u_comp.id
              LEFT JOIN usuarios u_asig ON so.usuario_id = u_asig.id
              JOIN usuarios u ON p.usuario_creador_id = u.id
              WHERE u.admin_id = ?
                AND p.usuario_creador_id != ?
                AND so.ofertante_id IS NOT NULL
                AND so.usuario_id IS NOT NULL
                AND EXISTS(
                  SELECT 1 FROM ofertas_varios ov
                  WHERE ov.porte_id = p.id
                    AND ov.estado_oferta = 'asignado'
                )
            ";
            $params = [$admin_id, $usuario_id];
            $types  = "ii";
            break;

        case 'asociados_trenes':
            $baseQuery = "
              SELECT DISTINCT
                p.id,
                p.mercancia_descripcion,
                p.localizacion_recogida,
                p.localizacion_entrega,
                p.fecha_recogida,
                p.fecha_entrega,
                t.tren_nombre AS tren_nombre,
                pt.inicio_tren,
                pt.fin_tren,
                u.nombre_usuario AS creador_nombre
              FROM portes p
              JOIN porte_tren pt ON p.id = pt.porte_id
              JOIN tren t ON pt.tren_id = t.id
              JOIN usuarios u ON p.usuario_creador_id = u.id
              LEFT JOIN cambios_titularidad ct ON p.id = ct.porte_id
              WHERE
                u.admin_id = ?
                AND p.usuario_creador_id != ?
                AND (
                  (p.usuario_creador_id = ? AND (ct.usuario_id_2 IS NULL OR ct.usuario_id_2 = ?))
                  OR (ct.usuario_id_2 = ?)
                )
                AND u.id = ?
            ";
            $params = [
                $admin_id,
                $usuario_id,
                $usuario_id,
                $usuario_id,
                $usuario_id,
                $usuario_id
            ];
            $types  = "iiiiii";
            break;

        default:
            throw new Exception("Tipo no reconocido: $tipo");
    }

    $sql = $baseQuery . $extra . $extraWhere . " ORDER BY p.id DESC";
    $finalParams = array_merge($params, $extraParams);
    $finalTypes  = $types . $extraTypes;

    if (substr_count($sql, '?') !== count($finalParams)) {
        die("Error en $tipo: placeholders != params\nSQL:\n$sql\nParams:\n".print_r($finalParams,true));
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en prepare($tipo): " . $conn->error);
    }
    $stmt->bind_param($finalTypes, ...$finalParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

/***************************************************************************************
 * (4) Construir where + params para cada submenú
 ***************************************************************************************/
list($wNoOfr, $pNoOfr, $tNoOfr) = buildFilterWhere($buscar_no_ofrecidos, $fd_no_ofrecidos, $fh_no_ofrecidos);
list($wOfr,  $pOfr,  $tOfr )   = buildFilterWhere($buscar_ofrecidos,  $fd_ofrecidos,     $fh_ofrecidos);
list($wAsig, $pAsig, $tAsig)   = buildFilterWhere($buscar_asignados,  $fd_asignados,     $fh_asignados);
list($wTren, $pTren, $tTren)   = buildFilterWhere($buscar_tren,       $fd_tren,          $fh_tren);

// Añadir condición OR t.tren_nombre LIKE ? cuando se busca tren
if ($buscar_tren !== '') {
    $wTren = str_replace(')', ' OR t.tren_nombre LIKE ?)', $wTren);
    $pTren[] = "%$buscar_tren%";
    $tTren  .= "s";
}

/***************************************************************************************
 * (5) Ejecutar => no_ofrecidos, ofrecidos, asignados, trenes
 ***************************************************************************************/
$res_no_ofrec = obtenerPortes($conn, $usuario_id, $admin_id, 'no_ofrecidos',
                              $wNoOfr, $pNoOfr, $tNoOfr);
$num_no_ofrec = $res_no_ofrec->num_rows;

$res_ofrec = obtenerPortes($conn, $usuario_id, $admin_id, 'ofrecidos',
                           $wOfr, $pOfr, $tOfr);
$num_ofrec = $res_ofrec->num_rows;

$res_asig = obtenerPortes($conn, $usuario_id, $admin_id, 'asignados',
                          $wAsig, $pAsig, $tAsig);
$num_asig = $res_asig->num_rows;

$res_tren = obtenerPortes($conn, $usuario_id, $admin_id, 'asociados_trenes',
                          $wTren, $pTren, $tTren);
$num_tren = $res_tren->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portes Nuevos (Compañeros)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="header.css">
  <script src="header.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded',function(){
      // Collapsibles => mobile
      const collapsibles = document.querySelectorAll('.collapsible');
      collapsibles.forEach(btn=>{
        btn.addEventListener('click', function(){
          this.classList.toggle('active');
          const c = this.nextElementSibling;
          c.style.display = (c.style.display==="block") ? "none" : "block";
        });
      });

      // 🟢 Mantener la pestaña en la que estabas (LEE el 'tab' de la URL):
      const urlParams = new URLSearchParams(window.location.search);
      const activeTab = urlParams.get('tab') || 'noOfrecidosTab';
      showSection(activeTab);
    });

    function showSection(sectionId){
      const allTabs = document.querySelectorAll('.tabs li');
      allTabs.forEach(t => t.classList.remove('active'));

      const allContent = document.querySelectorAll('.tab-content');
      allContent.forEach(c => c.classList.remove('active'));

      const selTab = document.querySelector(`.tabs li[data-section="${sectionId}"]`);
      if(selTab) selTab.classList.add('active');

      const selContent = document.getElementById(sectionId);
      if(selContent) selContent.classList.add('active');
    }
    // Checkbox general => multi-check
    function toggleTodos(chk, nameGroup){
      const checks = document.querySelectorAll(`input[name="${nameGroup}[]"]`);
      checks.forEach(c => c.checked = chk.checked);
    }
    function confirmarTomarMultiple(){
      return confirm("¿Estás seguro de TOMAR los portes seleccionados?");
    }
  </script>
</head>
<body>
<?php include 'header.php'; ?>

<main>
  <h1>PORTES NUEVOS</h1>
  <nav class="nav-margin">
    <ul class="nav-list">
      <li>
        <a href="portes_nuevos_recibidos.php"
           class="nav-link-btn btn-blue">
          Recibidos
        </a>
      </li>
      <li>
        <a href="portes_nuevos_propios.php"
           class="nav-link-btn btn-green">
          Creados
        </a>
      </li>
    </ul>
  </nav>
  <h2 class="nav-margin nav-subtitle">Submenú:</h2>
  <nav class="nav-margin">
    <ul class="nav-list">
      <li>
        <a href="portes_nuevos_propios.php"
           class="nav-link-btn btn-blue">
          Mios
        </a>
      </li>
      <li>
        <a href="portes_nuevos_propios_todos.php"
           class="nav-link-btn btn-green active">
          Compañeros
        </a>
      </li>
    </ul>
  </nav>
  <br>

  <!-- ********* Versión MÓVIL => collapsibles + multi-check ********* -->
  <div class="mobile-only m-16">

    <!-- (1) Portes no_ofrecidos -->
    <button class="collapsible">
      Portes Creados Compañeros (<?php echo $num_no_ofrec; ?>)
    </button>
    <div class="content">
      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <?php if($num_no_ofrec>0): ?>
          <div style="margin-bottom:5px;">
            <label>
              <input type="checkbox" onclick="toggleTodos(this,'porte_id_no_ofrec_mobile')">
              Seleccionar todos
            </label>
          </div>

          <?php
          $res_no_ofrec->data_seek(0);
          while($row=$res_no_ofrec->fetch_assoc()): ?>
            <div class="card">
              <label style="display:block; margin-bottom:5px;">
                <input type="checkbox" name="porte_id_no_ofrec_mobile[]" value="<?php echo $row['id']; ?>">
                <strong>#<?php echo htmlspecialchars($row['id']); ?></strong>
              </label>
              <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>

              <p><strong>Responsable:</strong> <?php echo htmlspecialchars($row['creador_nombre']); ?></p>

              <p><strong>Origen:</strong>
                 <?php echo htmlspecialchars($row['localizacion_recogida']); ?> -
                 <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </p>
              <p><strong>Destino:</strong>
                 <?php echo htmlspecialchars($row['localizacion_entrega']); ?> -
                 <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </p>
              <div class="actions">
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>">
                  <button class="btn-gray">Detalles</button>
                </a>
                <form action="cambiar_titularidad_creados.php" method="POST" style="display:inline;">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button class="btn-blue">Tomar</button>
                </form>
              </div>
            </div>
          <?php endwhile; ?>
          <br>
          <button type="submit" class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php else: ?>
          <p>No hay portes creados.</p>
        <?php endif; ?>
      </form>
    </div>

    <!-- (2) Ofrecidos -->
    <button class="collapsible">
      Portes Ofrecidos Compañeros (<?php echo $num_ofrec; ?>)
    </button>
    <div class="content">
      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <?php if($num_ofrec>0): ?>
          <div style="margin-bottom:5px;">
            <label>
              <input type="checkbox" onclick="toggleTodos(this,'porte_id_ofrec_mobile')">
              Seleccionar todos
            </label>
          </div>
          <?php
          $res_ofrec->data_seek(0);
          while($row=$res_ofrec->fetch_assoc()): ?>
            <div class="card">
              <label style="display:block; margin-bottom:5px;">
                <input type="checkbox" name="porte_id_ofrec_mobile[]" value="<?php echo $row['id']; ?>">
                <strong>#<?php echo htmlspecialchars($row['id']); ?></strong>
              </label>
              <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>

              <p><strong>Responsable:</strong> <?php echo htmlspecialchars($row['creador_nombre']); ?></p>

              <p><strong>Origen:</strong>
                 <?php echo htmlspecialchars($row['localizacion_recogida']); ?> -
                 <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </p>
              <p><strong>Destino:</strong>
                 <?php echo htmlspecialchars($row['localizacion_entrega']); ?> -
                 <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </p>
              <div class="actions">
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>">
                  <button class="btn-gray">Detalles</button>
                </a>
                <form action="cambiar_titularidad_creados.php" method="POST" style="display:inline;">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button class="btn-blue">Tomar</button>
                </form>
              </div>
            </div>
          <?php endwhile; ?>
          <br>
          <button type="submit" class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php else: ?>
          <p>No hay portes ofrecidos.</p>
        <?php endif; ?>
      </form>
    </div>

    <!-- (3) Asignados -->
    <button class="collapsible">
      Portes Asignados Compañeros (<?php echo $num_asig; ?>)
    </button>
    <div class="content">
      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <?php if($num_asig>0): ?>
          <div style="margin-bottom:5px;">
            <label>
              <input type="checkbox" onclick="toggleTodos(this,'porte_id_asig_mobile')">
              Seleccionar todos
            </label>
          </div>
          <?php
          $res_asig->data_seek(0);
          while($row=$res_asig->fetch_assoc()):
            $ofertado_por = isset($row['ofertado_por'])?$row['ofertado_por']:'';
            $asignado_a   = isset($row['asignado_a'])?$row['asignado_a']:'';
          ?>
            <div class="card">
              <label style="display:block; margin-bottom:5px;">
                <input type="checkbox" name="porte_id_asig_mobile[]" value="<?php echo $row['id']; ?>">
                <strong>#<?php echo htmlspecialchars($row['id']); ?></strong>
              </label>
              <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>

              <p><strong>Origen:</strong>
                 <?php echo htmlspecialchars($row['localizacion_recogida']); ?> -
                 <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </p>
              <p><strong>Destino:</strong>
                 <?php echo htmlspecialchars($row['localizacion_entrega']); ?> -
                 <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </p>
              <p>
                <strong>Ofertado Por:</strong> <?php echo htmlspecialchars($ofertado_por); ?><br>
                <strong>Asignado a:</strong> <?php echo htmlspecialchars($asignado_a); ?>
              </p>
              <div class="actions">
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>">
                  <button class="btn-gray">Detalles</button>
                </a>
                <form action="cambiar_titularidad_creados.php" method="POST" style="display:inline;">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button class="btn-blue">Tomar</button>
                </form>
              </div>
            </div>
          <?php endwhile; ?>
          <br>
          <button type="submit" class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php else: ?>
          <p>No hay portes asignados.</p>
        <?php endif; ?>
      </form>
    </div>

    <!-- (4) Trenes -->
    <button class="collapsible">
      Portes en Trenes (<?php echo $num_tren; ?>)
    </button>
    <div class="content">
      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <?php if($num_tren>0): ?>
          <div style="margin-bottom:5px;">
            <label>
              <input type="checkbox" onclick="toggleTodos(this,'porte_id_tren_mobile')">
              Seleccionar todos
            </label>
          </div>
          <?php
          $res_tren->data_seek(0);
          while($row=$res_tren->fetch_assoc()): ?>
            <div class="card">
              <label style="display:block; margin-bottom:5px;">
                <input type="checkbox" name="porte_id_tren_mobile[]" value="<?php echo $row['id']; ?>">
                <strong>#<?php echo htmlspecialchars($row['id']); ?></strong>
              </label>
              <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>

              <p><strong>Responsable:</strong> <?php echo htmlspecialchars($row['creador_nombre']); ?></p>

              <p><strong>Tren:</strong> <?php echo htmlspecialchars($row['tren_nombre']); ?></p>

              <p><strong>Origen:</strong>
                 <?php echo htmlspecialchars($row['localizacion_recogida']); ?> -
                 <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </p>
              <p><strong>Destino:</strong>
                 <?php echo htmlspecialchars($row['localizacion_entrega']); ?> -
                 <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </p>
              <div class="actions">
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>">
                  <button class="btn-gray">Detalles</button>
                </a>
                <form action="cambiar_titularidad_completo.php" method="POST" style="display:inline;">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button class="btn-blue">Tomar</button>
                </form>
              </div>
            </div>
          <?php endwhile; ?>
          <br>
          <button type="submit" class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php else: ?>
          <p>No hay portes en tren.</p>
        <?php endif; ?>
      </form>
    </div>
  </div><!-- Fin mobile-only -->


  <!-- ********* Versión ESCRITORIO => TABS / TABLES + multi-check ********* -->
  <div class="desktop-only">
    <ul class="tabs">
      <li data-section="noOfrecidosTab" onclick="showSection('noOfrecidosTab')">
        Portes Creados (<?php echo $num_no_ofrec; ?>)
      </li>
      <li data-section="ofrecidosTab" onclick="showSection('ofrecidosTab')">
        Portes Ofrecidos (<?php echo $num_ofrec; ?>)
      </li>
      <li data-section="asignadosTab" onclick="showSection('asignadosTab')">
        Portes Asignados (<?php echo $num_asig; ?>)
      </li>
      <li data-section="trenTab" onclick="showSection('trenTab')">
        Portes en Tren (<?php echo $num_tren; ?>)
      </li>
    </ul>

    <!-- A) NO_OFRECIDOS -->
    <div id="noOfrecidosTab" class="tab-content">
      <h2 class="nav-subtitle">Portes Creados (Compañeros) [No Ofrecidos]</h2>

      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_propios_todos.php">
          <input type="hidden" name="tab" value="noOfrecidosTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_no_ofrecidos" value="<?php echo htmlspecialchars($buscar_no_ofrecidos); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_no_ofrecidos" value="<?php echo htmlspecialchars($fd_no_ofrecidos); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_no_ofrecidos" value="<?php echo htmlspecialchars($fh_no_ofrecidos); ?>">
          <button type="submit" class="btn-accion btn-blue">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-gray"
                  onclick="window.location.href='portes_nuevos_propios_todos.php?tab=noOfrecidosTab';">
            Limpiar
          </button>
        </form>
      </div>

      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this,'porte_id_no_ofrec')"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Origen - Fecha</th>
              <th>Destino - Fecha</th>
              <th>Responsable</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $res_no_ofrec->data_seek(0);
          if($num_no_ofrec>0):
            while($row=$res_no_ofrec->fetch_assoc()):
          ?>
            <tr>
              <td>
                <input type="checkbox" name="porte_id_no_ofrec[]" value="<?php echo $row['id']; ?>">
              </td>
              <td>#<?php echo htmlspecialchars($row['id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['creador_nombre']); ?></td>
              <td>
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>"
                   class="btn-accion btn-gray" target="_blank">
                  Detalles
                </a>
                <form action="cambiar_titularidad_creados.php" method="POST" style="display:inline;">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button class="btn-accion btn-blue">Tomar</button>
                </form>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="7">No hay portes creados por compañeros disponibles.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>

        <?php if($num_no_ofrec>0): ?>
          <br>
          <button type="submit" name="accion_global" value="tomar_no_ofrecidos_multiple"
                  class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php endif; ?>
      </form>
    </div><!-- fin noOfrecidosTab -->

    <!-- B) OFRECIDOS -->
    <div id="ofrecidosTab" class="tab-content">
      <h2 class="nav-subtitle">Portes Ofrecidos (Compañeros)</h2>
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_propios_todos.php">
          <input type="hidden" name="tab" value="ofrecidosTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_ofrecidos" value="<?php echo htmlspecialchars($buscar_ofrecidos); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_ofrecidos" value="<?php echo htmlspecialchars($fd_ofrecidos); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_ofrecidos" value="<?php echo htmlspecialchars($fh_ofrecidos); ?>">
          <button type="submit" class="btn-accion btn-blue">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-gray"
                  onclick="window.location.href='portes_nuevos_propios_todos.php?tab=ofrecidosTab';">
            Limpiar
          </button>
        </form>
      </div>

      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this,'porte_id_ofrec')"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Origen - Fecha</th>
              <th>Destino - Fecha</th>
              <th>Responsable</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $res_ofrec->data_seek(0);
          if($num_ofrec>0):
            while($row=$res_ofrec->fetch_assoc()):
          ?>
            <tr>
              <td>
                <input type="checkbox" name="porte_id_ofrec[]" value="<?php echo $row['id']; ?>">
              </td>
              <td>#<?php echo htmlspecialchars($row['id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['creador_nombre']); ?></td>
              <td>
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>"
                   class="btn-accion btn-gray" target="_blank">
                  Detalles
                </a>
                <form action="cambiar_titularidad_creados.php" method="POST" style="display:inline;">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button class="btn-accion btn-blue">Tomar</button>
                </form>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="7">No hay portes ofrecidos.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>

        <?php if($num_ofrec>0): ?>
          <br>
          <button type="submit" name="accion_global" value="tomar_ofrecidos_multiple"
                  class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php endif; ?>
      </form>
    </div><!-- fin ofrecidosTab -->

    <!-- C) ASIGNADOS -->
    <div id="asignadosTab" class="tab-content">
      <h2 class="nav-subtitle">Portes Asignados (Compañeros)</h2>
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_propios_todos.php">
          <input type="hidden" name="tab" value="asignadosTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_asignados" value="<?php echo htmlspecialchars($buscar_asignados); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_asignados" value="<?php echo htmlspecialchars($fd_asignados); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_asignados" value="<?php echo htmlspecialchars($fh_asignados); ?>">
          <button type="submit" class="btn-accion btn-blue">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-gray"
                  onclick="window.location.href='portes_nuevos_propios_todos.php?tab=asignadosTab';">
            Limpiar
          </button>
        </form>
      </div>

      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this,'porte_id_asign')"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Origen - Fecha</th>
              <th>Destino - Fecha</th>
              <th>Ofertado Por</th>
              <th>Asignado a</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $res_asig->data_seek(0);
          if($num_asig>0):
            while($row=$res_asig->fetch_assoc()):
              $ofertado_por = isset($row['ofertado_por'])?$row['ofertado_por']:'';
              $asignado_a   = isset($row['asignado_a'])?$row['asignado_a']:'';
          ?>
            <tr>
              <td>
                <input type="checkbox" name="porte_id_asign[]" value="<?php echo $row['id']; ?>">
              </td>
              <td>#<?php echo htmlspecialchars($row['id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($ofertado_por); ?></td>
              <td><?php echo htmlspecialchars($asignado_a); ?></td>
              <td>
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>"
                   class="btn-accion btn-gray" target="_blank">
                  Detalles
                </a>
                <form action="cambiar_titularidad_creados.php" method="POST" style="display:inline;">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button class="btn-accion btn-blue">Tomar</button>
                </form>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="8">No hay portes asignados disponibles.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>

        <?php if($num_asig>0): ?>
          <br>
          <button type="submit" name="accion_global" value="tomar_asignados_multiple"
                  class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php endif; ?>
      </form>
    </div><!-- fin asignadosTab -->

    <!-- D) TREN -->
    <div id="trenTab" class="tab-content">
      <h2 class="nav-subtitle">Portes en Tren (Compañeros)</h2>
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_propios_todos.php">
          <input type="hidden" name="tab" value="trenTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_tren" value="<?php echo htmlspecialchars($buscar_tren); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_tren" value="<?php echo htmlspecialchars($fd_tren); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_tren" value="<?php echo htmlspecialchars($fh_tren); ?>">
          <button type="submit" class="btn-accion btn-blue">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-gray"
                  onclick="window.location.href='portes_nuevos_propios_todos.php?tab=trenTab';">
            Limpiar
          </button>
        </form>
      </div>

      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this,'porte_id_tren')"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Tren</th>
              <th>Inicio</th>
              <th>Fin</th>
              <th>Origen - Fecha</th>
              <th>Destino - Fecha</th>
              <th>Responsable</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $res_tren->data_seek(0);
          if($num_tren>0):
            while($row=$res_tren->fetch_assoc()):
          ?>
            <tr>
              <td>
                <input type="checkbox" name="porte_id_tren[]" value="<?php echo $row['id']; ?>">
              </td>
              <td>#<?php echo htmlspecialchars($row['id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td><?php echo htmlspecialchars($row['tren_nombre']); ?></td>
              <td><?php echo isset($row['inicio_tren'])? htmlspecialchars($row['inicio_tren']) : ''; ?></td>
              <td><?php echo isset($row['fin_tren'])? htmlspecialchars($row['fin_tren']) : ''; ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['creador_nombre']); ?></td>
              <td>
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>"
                   class="btn-accion btn-gray" target="_blank">
                  Detalles
                </a>
                <form action="cambiar_titularidad_completo.php" method="POST" style="display:inline;">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button class="btn-accion btn-blue">Tomar</button>
                </form>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="10">No hay portes en tren.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
        <?php if($num_tren>0): ?>
          <br>
          <button type="submit" name="accion_global" value="tomar_tren_multiple"
                  class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php endif; ?>
      </form>
    </div><!-- fin trenTab -->
  </div><!-- Fin desktop-only -->

<?php include 'footer.php'; ?>
