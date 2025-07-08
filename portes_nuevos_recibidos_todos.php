<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['usuario_id'])) {
    die("Error: Falta admin_id o usuario_id en la sesión.");
}
$admin_id   = $_SESSION['admin_id'];
$usuario_id = $_SESSION['usuario_id'];

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

/***************************************************************************************
 * (1) FILTROS => Nuevos, Aceptados, Reofrecidos, Reasignados, En Tren
 ***************************************************************************************/

/*
  Añadimos “nombre_empresa” como elemento de búsqueda en varios JOIN, etc.
  Ajusta según tus necesidades exactas.
*/

/* (1.1) Filtros => NUEVOS */
$buscar_nuevos = isset($_GET['buscar_nuevos']) ? trim($_GET['buscar_nuevos']) : '';
$fd_nuevos     = isset($_GET['fecha_desde_nuevos']) ? trim($_GET['fecha_desde_nuevos']) : '';
$fh_nuevos     = isset($_GET['fecha_hasta_nuevos']) ? trim($_GET['fecha_hasta_nuevos']) : '';

$filtroSQL_nuevos = "
    ov.estado_oferta='pendiente'
    AND u_comp.admin_id = ?
    AND ov.usuario_id   != ?
";
$paramNuevos = [$admin_id, $usuario_id];
$typeNuevos  = "ii";

if($buscar_nuevos !== ''){
    $filtroSQL_nuevos .= "
      AND (
        p.mercancia_descripcion    LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR p.localizacion_entrega  LIKE ?
        OR u_ofert.nombre_usuario  LIKE ?
        OR u_ofert.nombre_empresa  LIKE ?
        OR u_comp.nombre_usuario   LIKE ?
        OR u_comp.nombre_empresa   LIKE ?
      )
    ";
    $like_nuevos = "%$buscar_nuevos%";
    $paramNuevos[] = $like_nuevos; 
    $paramNuevos[] = $like_nuevos; 
    $paramNuevos[] = $like_nuevos; 
    $paramNuevos[] = $like_nuevos; 
    $paramNuevos[] = $like_nuevos; 
    $paramNuevos[] = $like_nuevos; 
    $paramNuevos[] = $like_nuevos; 
    $typeNuevos   .= "sssssss"; 
}
if($fd_nuevos !== ''){
    $filtroSQL_nuevos .= " AND p.fecha_recogida >= ? ";
    $paramNuevos[] = $fd_nuevos;
    $typeNuevos   .= "s";
}
if($fh_nuevos !== ''){
    $filtroSQL_nuevos .= " AND p.fecha_recogida <= ? ";
    $paramNuevos[] = $fh_nuevos;
    $typeNuevos   .= "s";
}

/* (1.2) Filtros => ACEPTADOS */
$buscar_acept = isset($_GET['buscar_acept']) ? trim($_GET['buscar_acept']) : '';
$fd_acept     = isset($_GET['fecha_desde_acept']) ? trim($_GET['fecha_desde_acept']) : '';
$fh_acept     = isset($_GET['fecha_hasta_acept']) ? trim($_GET['fecha_hasta_acept']) : '';

$filtroSQL_acept = "
    u_comp.admin_id = ?
    AND so.usuario_id != ?
    AND NOT EXISTS (
      SELECT 1 FROM ofertas_varios ov2
      WHERE ov2.porte_id = so.porte_id
        AND ov2.fecha_oferta > so.fecha_seleccion
    )
    AND NOT EXISTS (
      SELECT 1 FROM porte_tren pt
      WHERE pt.porte_id = so.porte_id
        AND pt.inicio_tren > so.fecha_seleccion
    )
";
$paramAcept = [$admin_id, $usuario_id];
$typeAcept  = "ii";

if($buscar_acept !== ''){
    $filtroSQL_acept .= "
      AND (
        p.mercancia_descripcion    LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR p.localizacion_entrega  LIKE ?
        OR u_ofert.nombre_usuario  LIKE ?
        OR u_ofert.nombre_empresa  LIKE ?
        OR u_comp.nombre_usuario   LIKE ?
        OR u_comp.nombre_empresa   LIKE ?
      )
    ";
    $like_acept = "%$buscar_acept%";
    $paramAcept[] = $like_acept;
    $paramAcept[] = $like_acept;
    $paramAcept[] = $like_acept;
    $paramAcept[] = $like_acept;
    $paramAcept[] = $like_acept;
    $paramAcept[] = $like_acept;
    $paramAcept[] = $like_acept;
    $typeAcept   .= "sssssss";
}
if($fd_acept !== ''){
    $filtroSQL_acept .= " AND p.fecha_recogida >= ? ";
    $paramAcept[] = $fd_acept;
    $typeAcept   .= "s";
}
if($fh_acept !== ''){
    $filtroSQL_acept .= " AND p.fecha_recogida <= ? ";
    $paramAcept[] = $fh_acept;
    $typeAcept   .= "s";
}

/* (1.3) Filtros => REOFRECIDOS */
$buscar_reof = isset($_GET['buscar_reof']) ? trim($_GET['buscar_reof']) : '';
$fd_reof     = isset($_GET['fecha_desde_reof']) ? trim($_GET['fecha_desde_reof']) : '';
$fh_reof     = isset($_GET['fecha_hasta_reof']) ? trim($_GET['fecha_hasta_reof']) : '';

$filtroSQL_reof = "
    u_comp.admin_id = ?
    AND so.usuario_id != ?
    AND NOT EXISTS (
      SELECT 1 FROM porte_tren pt
      WHERE pt.porte_id = so.porte_id
        AND pt.inicio_tren > so.fecha_seleccion
    )
";
$paramReof = [$admin_id, $usuario_id];
$typeReof  = "ii";

if($buscar_reof !== ''){
    $filtroSQL_reof .= "
      AND (
        p.mercancia_descripcion    LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR p.localizacion_entrega  LIKE ?
        OR u_ofert.nombre_usuario  LIKE ?
        OR u_ofert.nombre_empresa  LIKE ?
        OR u_comp.nombre_usuario   LIKE ?
        OR u_comp.nombre_empresa   LIKE ?
      )
    ";
    $like_reof = "%$buscar_reof%";
    $paramReof[] = $like_reof;
    $paramReof[] = $like_reof;
    $paramReof[] = $like_reof;
    $paramReof[] = $like_reof;
    $paramReof[] = $like_reof;
    $paramReof[] = $like_reof;
    $paramReof[] = $like_reof;
    $typeReof   .= "sssssss";
}
if($fd_reof !== ''){
    $filtroSQL_reof .= " AND p.fecha_recogida >= ? ";
    $paramReof[] = $fd_reof;
    $typeReof   .= "s";
}
if($fh_reof !== ''){
    $filtroSQL_reof .= " AND p.fecha_recogida <= ? ";
    $paramReof[] = $fh_reof;
    $typeReof   .= "s";
}

/* (1.4) Filtros => REASIGNADOS */
$buscar_reasig = isset($_GET['buscar_reasig']) ? trim($_GET['buscar_reasig']) : '';
$fd_reasig     = isset($_GET['fecha_desde_reasig']) ? trim($_GET['fecha_desde_reasig']) : '';
$fh_reasig     = isset($_GET['fecha_hasta_reasig']) ? trim($_GET['fecha_hasta_reasig']) : '';

$filtroSQL_reasig = "
    u1.admin_id = ?
    AND so1.usuario_id != ?
    AND NOT EXISTS (
      SELECT 1
      FROM porte_tren pt
      WHERE pt.porte_id = so1.porte_id
        AND pt.inicio_tren>so1.fecha_seleccion
    )
";
$paramReasig = [$admin_id, $usuario_id];
$typeReasig  = "ii";

if($buscar_reasig !== ''){
    $filtroSQL_reasig .= "
      AND (
        p.mercancia_descripcion    LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR p.localizacion_entrega  LIKE ?
      )
    ";
    $like_reasig = "%$buscar_reasig%";
    $paramReasig[] = $like_reasig;
    $paramReasig[] = $like_reasig;
    $paramReasig[] = $like_reasig;
    $typeReasig   .= "sss";
}
if($fd_reasig !== ''){
    $filtroSQL_reasig .= " AND p.fecha_recogida >= ? ";
    $paramReasig[] = $fd_reasig;
    $typeReasig   .= "s";
}
if($fh_reasig !== ''){
    $filtroSQL_reasig .= " AND p.fecha_recogida <= ? ";
    $paramReasig[] = $fh_reasig;
    $typeReasig   .= "s";
}

/* (1.5) Filtros => EN TREN */
$buscar_tren = isset($_GET['buscar_tren']) ? trim($_GET['buscar_tren']) : '';
$fd_tren     = isset($_GET['fecha_desde_tren']) ? trim($_GET['fecha_desde_tren']) : '';
$fh_tren     = isset($_GET['fecha_hasta_tren']) ? trim($_GET['fecha_hasta_tren']) : '';

$filtroSQL_tren = "
    u.admin_id = ?
    AND pt.usuario_id != ?
";
$paramTren = [$admin_id, $usuario_id];
$typeTren  = "ii";

if($buscar_tren !== ''){
    $filtroSQL_tren .= "
      AND (
        p.mercancia_descripcion    LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR p.localizacion_entrega  LIKE ?
        OR t.tren_nombre           LIKE ?
      )
    ";
    $like_tren = "%$buscar_tren%";
    $paramTren[] = $like_tren;
    $paramTren[] = $like_tren;
    $paramTren[] = $like_tren;
    $paramTren[] = $like_tren;
    $typeTren   .= "ssss";
}
if($fd_tren !== ''){
    $filtroSQL_tren .= " AND p.fecha_recogida >= ? ";
    $paramTren[] = $fd_tren;
    $typeTren   .= "s";
}
if($fh_tren !== ''){
    $filtroSQL_tren .= " AND p.fecha_recogida <= ? ";
    $paramTren[] = $fh_tren;
    $typeTren   .= "s";
}

/***************************************************************************************
 * (2) CONSULTAS => Nuevos, Aceptados, Reofrecidos, Reasignados, En Tren
 ***************************************************************************************/

/* (2.1) NUEVOS => $filtroSQL_nuevos */
$query_nuevos = "
    SELECT 
      ov.porte_id,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      u_comp.nombre_usuario AS nombre_companero,
      u_comp.nombre_empresa  AS empresa_companero,
      u_ofert.nombre_usuario AS ofertante_nombre,
      u_ofert.nombre_empresa AS empresa_ofertante
    FROM ofertas_varios ov
    JOIN usuarios u_comp ON ov.usuario_id = u_comp.id
    JOIN portes p ON ov.porte_id = p.id
    LEFT JOIN usuarios u_ofert ON ov.ofertante_id = u_ofert.id
    WHERE $filtroSQL_nuevos
    ORDER BY p.id DESC
";
$stmt_nuevos = $conn->prepare($query_nuevos);
if (!$stmt_nuevos) { die("Error en 'nuevos': ".$conn->error); }
$stmt_nuevos->bind_param($typeNuevos, ...$paramNuevos);
$stmt_nuevos->execute();
$res_nuevos = $stmt_nuevos->get_result();
$num_nuevos = $res_nuevos->num_rows;

/* (2.2) ACEPTADOS => $filtroSQL_acept */
$query_aceptados = "
    SELECT 
      so.porte_id,
      so.fecha_seleccion,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      u_comp.nombre_usuario AS asignado_a,
      u_comp.nombre_empresa  AS empresa_asignado,
      u_ofert.nombre_usuario AS ofertante_nombre,
      u_ofert.nombre_empresa AS empresa_ofertante
    FROM seleccionados_oferta so
    JOIN usuarios u_comp ON so.usuario_id = u_comp.id
    JOIN portes p ON so.porte_id = p.id
    LEFT JOIN ofertas_varios ov
      ON so.porte_id = ov.porte_id
     AND so.ofertante_id = ov.ofertante_id
    LEFT JOIN usuarios u_ofert 
      ON ov.ofertante_id = u_ofert.id
    WHERE $filtroSQL_acept
    ORDER BY p.id DESC
";
$stmt_acept = $conn->prepare($query_aceptados);
if (!$stmt_acept) { die("Error 'aceptados': ".$conn->error); }
$stmt_acept->bind_param($typeAcept, ...$paramAcept);
$stmt_acept->execute();
$res_acept = $stmt_acept->get_result();
$num_acept = $res_acept->num_rows;

/* (2.3) REOFRECIDOS => $filtroSQL_reof */
$query_reofrecidos = "
    SELECT
      so.porte_id,
      so.fecha_seleccion,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      u_comp.nombre_usuario AS asignado_a,
      u_comp.nombre_empresa  AS empresa_asignado,
      u_ofert.nombre_usuario AS ofertante_nombre,
      u_ofert.nombre_empresa AS empresa_ofertante
    FROM seleccionados_oferta so
    JOIN usuarios u_comp ON so.usuario_id = u_comp.id
    JOIN portes p ON so.porte_id = p.id
    JOIN (
      SELECT porte_id, usuario_id, MAX(fecha_oferta) as ultima
      FROM ofertas_varios
      WHERE estado_oferta='pendiente'
      GROUP BY porte_id, usuario_id
    ) last_ov
      ON so.porte_id = last_ov.porte_id
     AND so.usuario_id = last_ov.usuario_id
    LEFT JOIN ofertas_varios ov
      ON ov.porte_id     = last_ov.porte_id
     AND ov.usuario_id   = last_ov.usuario_id
     AND ov.fecha_oferta = last_ov.ultima
    LEFT JOIN usuarios u_ofert
      ON ov.ofertante_id = u_ofert.id
    WHERE $filtroSQL_reof
    ORDER BY p.id DESC
";
$stmt_reof = $conn->prepare($query_reofrecidos);
if (!$stmt_reof) { die("Error 'reofrecidos': ".$conn->error); }
$stmt_reof->bind_param($typeReof, ...$paramReof);
$stmt_reof->execute();
$res_reof = $stmt_reof->get_result();
$num_reof = $res_reof->num_rows;

/* (2.4) REASIGNADOS => $filtroSQL_reasig
    => AÑADIR p.estado_recogida_entrega
*/
$query_reasign = "
    SELECT
      so1.porte_id,
      so2.usuario_id AS reasignado_a,
      so2.fecha_seleccion,
      so2.ofertante_id AS responsable,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      p.estado_recogida_entrega
    FROM seleccionados_oferta so1
    JOIN seleccionados_oferta so2
      ON so1.porte_id = so2.porte_id
     AND so1.usuario_id=so2.ofertante_id
     AND so2.fecha_seleccion>so1.fecha_seleccion
     AND NOT EXISTS (
       SELECT 1
       FROM seleccionados_oferta so_inner
       WHERE so_inner.porte_id = so1.porte_id
         AND so_inner.fecha_seleccion>so1.fecha_seleccion
         AND so_inner.fecha_seleccion<so2.fecha_seleccion
     )
    JOIN usuarios u1 ON so1.usuario_id = u1.id
    JOIN portes p ON so1.porte_id = p.id
    WHERE $filtroSQL_reasig
    ORDER BY p.id DESC
";
$stmt_reasign = $conn->prepare($query_reasign);
if (!$stmt_reasign) { die("Error 'reasign': ".$conn->error); }
$stmt_reasign->bind_param($typeReasig, ...$paramReasig);
$stmt_reasign->execute();
$res_reasign = $stmt_reasign->get_result();
$num_reasign = $res_reasign->num_rows;

/* (2.5) EN TREN => $filtroSQL_tren
    => AÑADIMOS p.estado_recogida_entrega
*/
$query_tren = "
    SELECT 
      pt.porte_id,
      pt.inicio_tren,
      pt.fin_tren,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      p.estado_recogida_entrega,
      t.tren_nombre,
      u.nombre_usuario   AS tren_usuario_nombre,
      u.nombre_empresa   AS tren_usuario_empresa
    FROM porte_tren pt
    JOIN portes p ON pt.porte_id = p.id
    JOIN tren   t ON pt.tren_id   = t.id
    JOIN usuarios u ON pt.usuario_id = u.id
    WHERE $filtroSQL_tren
    ORDER BY p.id DESC
";
$stmt_tren = $conn->prepare($query_tren);
if (!$stmt_tren) { die("Error 'tren': ".$conn->error); }
$stmt_tren->bind_param($typeTren, ...$paramTren);
$stmt_tren->execute();
$res_tren = $stmt_tren->get_result();
$num_tren = $res_tren->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portes Nuevos Recibidos (Compañeros)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles.css">
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Collapsibles => mobile
      const collapsibles = document.querySelectorAll('.collapsible');
      collapsibles.forEach(btn => {
        btn.addEventListener('click',function(){
          this.classList.toggle('active');
          const content = this.nextElementSibling;
          content.style.display = (content.style.display === "block") ? "none" : "block";
        });
      });

      // LEER param 'tab' de la URL
      const urlParams = new URLSearchParams(window.location.search);
      const activeTab = urlParams.get('tab') || 'nuevosTab'; // por defecto "nuevosTab"
      showSection(activeTab);
    });

    function showSection(sectionId){
      const allTabs = document.querySelectorAll('.tabs li');
      allTabs.forEach(tab => tab.classList.remove('active'));

      const allContents = document.querySelectorAll('.tab-content');
      allContents.forEach(cont => cont.classList.remove('active'));

      const tabButton = document.querySelector(`.tabs li[data-section="${sectionId}"]`);
      if(tabButton) tabButton.classList.add('active');

      const content = document.getElementById(sectionId);
      if(content) content.classList.add('active');
    }

    function confirmarTomar(){
      return confirm("¿Estás seguro de tomar el porte?");
    }
    function confirmarTomarMultiple(){
      return confirm("¿Estás seguro de tomar los portes seleccionados?");
    }
    function toggleTodos(chk, clase){
      const checks = document.querySelectorAll(`input[name="${clase}[]"]`);
      checks.forEach(c => c.checked = chk.checked);
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
           class="nav-link-btn btn-green active">
          Recibidos
        </a>
      </li>
      <li>
        <a href="portes_nuevos_propios.php"
           class="nav-link-btn btn-blue">
          Creados
        </a>
      </li>
    </ul>
  </nav>
  <h2>Submenú:</h2>
  <nav class="nav-margin">
    <ul class="nav-list">
      <li>
        <a href="portes_nuevos_recibidos.php"
           class="nav-link-btn btn-blue">
          Mios
        </a>
      </li>
      <li>
        <a href="portes_nuevos_recibidos_todos.php"
           class="nav-link-btn btn-green active">
          Compañeros
        </a>
      </li>
    </ul>
  </nav>
  <br>

  <!-- ***************************************
       (A) VERSIÓN MÓVIL => TARJETAS
       *************************************** -->
  <div class="mobile-only m-16">
    <!-- (1) NUEVOS RECIBIDOS -->
    <button class="collapsible">
      Nuevos Recibidos (<?php echo $num_nuevos; ?>)
    </button>
    <div class="content">
      <?php if($num_nuevos>0): ?>
        <?php while($row=$res_nuevos->fetch_assoc()): ?>
          <div class="card">
            <h3>ID: #<?php echo htmlspecialchars($row['porte_id']); ?></h3>
            <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></p>
            <p><strong>Recogida:</strong> 
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Entrega:</strong> 
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <p><strong>Recibido de:</strong> <?php echo htmlspecialchars($row['empresa_ofertante']); ?></p>
            <p><strong>Compañero:</strong> <?php echo htmlspecialchars($row['nombre_companero']); ?></p>
            <div class="actions">
              <form action="cambiar_titularidad.php" method="POST" onsubmit="return confirmarTomar();">
                <input type="hidden" name="porte_id" value="<?php echo htmlspecialchars($row['porte_id']); ?>">
                <button class="btn-blue">Tomar</button>
              </form>
              <a href="detalle_porte.php?id=<?php echo htmlspecialchars($row['porte_id']); ?>">
                <button class="btn-gray">Detalles</button>
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes nuevos recibidos.</p>
      <?php endif; ?>
    </div>

    <!-- (2) ACEPTADOS -->
    <button class="collapsible">
      Aceptados (<?php echo $num_acept; ?>)
    </button>
    <div class="content">
      <?php if($num_acept>0): ?>
        <?php while($row=$res_acept->fetch_assoc()): ?>
          <div class="card">
            <h3>ID: #<?php echo htmlspecialchars($row['porte_id']); ?></h3>
            <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></p>
            <p><strong>Recogida:</strong> 
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Entrega:</strong> 
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <p><strong>Recibido de:</strong> <?php echo htmlspecialchars($row['empresa_ofertante']); ?></p>
            <p><strong>Compañero:</strong> <?php echo htmlspecialchars($row['asignado_a']); ?></p>
            <div class="actions">
              <form action="cambiar_titularidad.php" method="POST" onsubmit="return confirmarTomar();">
                <input type="hidden" name="porte_id" value="<?php echo htmlspecialchars($row['porte_id']); ?>">
                <button class="btn-blue">Tomar</button>
              </form>
              <a href="detalle_porte.php?id=<?php echo htmlspecialchars($row['porte_id']); ?>">
                <button class="btn-gray">Detalles</button>
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes aceptados.</p>
      <?php endif; ?>
    </div>

    <!-- (3) REOFRECIDOS -->
    <button class="collapsible">
      Reofrecidos (<?php echo $num_reof; ?>)
    </button>
    <div class="content">
      <?php if($num_reof>0): ?>
        <?php while($row=$res_reof->fetch_assoc()): ?>
          <div class="card">
            <h3>ID: #<?php echo htmlspecialchars($row['porte_id']); ?></h3>
            <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></p>
            <p><strong>Recogida:</strong> 
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Entrega:</strong> 
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <p><strong>Recibido de:</strong> <?php echo htmlspecialchars($row['empresa_ofertante']); ?></p>
            <p><strong>Asignado a:</strong> <?php echo htmlspecialchars($row['asignado_a']); ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes reofrecidos.</p>
      <?php endif; ?>
    </div>

    <!-- (4) REASIGNADOS -->
    <button class="collapsible">
      Reasignados (<?php echo $num_reasign; ?>)
    </button>
    <div class="content">
      <?php if($num_reasign>0): ?>
        <?php while($row=$res_reasign->fetch_assoc()): ?>
          <div class="card">
            <h3>ID: #<?php echo htmlspecialchars($row['porte_id']); ?></h3>
            <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></p>
            <p><strong>Recogida:</strong> 
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Entrega:</strong> 
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <!-- Añadimos ESTADO -->
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></p>
            <p><strong>Reasignado a:</strong> <?php echo htmlspecialchars($row['reasignado_a']); ?></p>
            <p><strong>Fecha:</strong> <?php echo htmlspecialchars($row['fecha_seleccion']); ?></p>
            <p><strong>Responsable:</strong> <?php echo htmlspecialchars($row['responsable']); ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes reasignados.</p>
      <?php endif; ?>
    </div>

    <!-- (5) EN TREN -->
    <button class="collapsible">
      En Tren (<?php echo $num_tren; ?>)
    </button>
    <div class="content">
      <?php if($num_tren>0): ?>
        <?php while($row=$res_tren->fetch_assoc()): ?>
          <div class="card">
            <h3>ID: #<?php echo htmlspecialchars($row['porte_id']); ?></h3>
            <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></p>
            <p><strong>Recogida:</strong> 
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Entrega:</strong> 
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <!-- Añadimos ESTADO -->
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></p>
            <p><strong>Tren:</strong> <?php echo htmlspecialchars($row['tren_nombre']); ?></p>
            <p><strong>Inicio:</strong> <?php echo htmlspecialchars($row['inicio_tren']); ?></p>
            <p><strong>Fin:</strong> <?php echo htmlspecialchars($row['fin_tren']); ?></p>
            <p><strong>Asignado a:</strong> <?php echo htmlspecialchars($row['tren_usuario_nombre']); ?></p>
            <p><strong>Empresa:</strong> <?php echo htmlspecialchars($row['tren_usuario_empresa']); ?></p>

            <div class="actions">
              <form action="cambiar_titularidad_completo.php" method="POST"
                    onsubmit="return confirm('¿Seguro de tomar este porte del tren?');">
                <input type="hidden" name="porte_id" value="<?php echo htmlspecialchars($row['porte_id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button class="btn-blue">Tomar</button>
              </form>
              <a href="detalle_porte.php?id=<?php echo htmlspecialchars($row['porte_id']); ?>">
                <button class="btn-gray">Detalles</button>
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes en tren.</p>
      <?php endif; ?>
    </div>
  </div><!-- Fin versión móvil -->


  <!-- ***************************************
       (B) VERSIÓN ESCRITORIO => TABS / TABLAS
       *************************************** -->
  <div class="desktop-only">
    <ul class="tabs">
      <li data-section="nuevosTab"    onclick="showSection('nuevosTab')">Nuevos (<?php echo $num_nuevos; ?>)</li>
      <li data-section="aceptadosTab" onclick="showSection('aceptadosTab')">Aceptados (<?php echo $num_acept; ?>)</li>
      <li data-section="reofTab"      onclick="showSection('reofTab')">Reofrecidos (<?php echo $num_reof; ?>)</li>
      <li data-section="reasignTab"   onclick="showSection('reasignTab')">Reasignados (<?php echo $num_reasign; ?>)</li>
      <li data-section="trenTab"      onclick="showSection('trenTab')">En Tren (<?php echo $num_tren; ?>)</li>
    </ul>

    <!-- (B1) NUEVOS -->
    <div id="nuevosTab" class="tab-content">
      <h2>Nuevos Recibidos</h2>

      <!-- Filtro NUEVOS -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos_todos.php">
          <input type="hidden" name="tab" value="nuevosTab">

          <label>Buscar:</label>
          <input type="text" name="buscar_nuevos" value="<?php echo htmlspecialchars($buscar_nuevos); ?>">

          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_nuevos" value="<?php echo htmlspecialchars($fd_nuevos); ?>">

          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_nuevos" value="<?php echo htmlspecialchars($fh_nuevos); ?>">

          <button type="submit" class="btn-accion btn-blue">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-gray"
                  onclick="window.location.href='portes_nuevos_recibidos_todos.php?tab=nuevosTab';">
            Limpiar
          </button>
        </form>
      </div>

      <!-- MULTISELECT => Tomar varios -->
      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this,'porte_id_nuevos')"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Recogida</th>
              <th>Entrega</th>
              <th>Recibido de</th>
              <th>Compañero</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $res_nuevos->data_seek(0);
          if($num_nuevos>0):
            while($row=$res_nuevos->fetch_assoc()):
          ?>
            <tr>
              <td>
                <input type="checkbox" name="porte_id_nuevos[]" value="<?php echo htmlspecialchars($row['porte_id']); ?>">
              </td>
              <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['empresa_ofertante']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_companero']); ?></td>
              <td>
                <form action="cambiar_titularidad.php" method="POST" style="display:inline;" onsubmit="return confirmarTomar();">
                  <input type="hidden" name="porte_id" value="<?php echo htmlspecialchars($row['porte_id']); ?>">
                  <button class="btn-accion btn-blue">Tomar</button>
                </form>
                <a href="detalle_porte.php?id=<?php echo htmlspecialchars($row['porte_id']); ?>">
                  <button class="btn-accion btn-gray">Detalles</button>
                </a>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="8">No hay portes nuevos recibidos.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>

        <!-- Botón masivo => Tomar seleccionados -->
        <?php if($num_nuevos>0): ?>
          <br>
          <button type="submit" name="accion_global" value="tomar_nuevos_multiple"
                  class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php endif; ?>
      </form>
    </div><!-- fin nuevosTab -->


    <!-- (B2) ACEPTADOS -->
    <div id="aceptadosTab" class="tab-content">
      <h2>Aceptados</h2>

      <!-- Filtro ACEPTADOS -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos_todos.php">
          <input type="hidden" name="tab" value="aceptadosTab">

          <label>Buscar:</label>
          <input type="text" name="buscar_acept" value="<?php echo htmlspecialchars($buscar_acept); ?>">

          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_acept" value="<?php echo htmlspecialchars($fd_acept); ?>">

          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_acept" value="<?php echo htmlspecialchars($fh_acept); ?>">

          <button type="submit" class="btn-accion btn-blue">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-gray"
                  onclick="window.location.href='portes_nuevos_recibidos_todos.php?tab=aceptadosTab';">
            Limpiar
          </button>
        </form>
      </div>

      <form method="POST" action="tomar_multiple_companeros.php" onsubmit="return confirmarTomarMultiple();">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this,'porte_id_acept')"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Recogida</th>
              <th>Entrega</th>
              <th>Recibido de</th>
              <th>Compañero</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $res_acept->data_seek(0);
          if($num_acept>0):
            while($row=$res_acept->fetch_assoc()):
          ?>
            <tr>
              <td>
                <input type="checkbox" name="porte_id_acept[]" value="<?php echo htmlspecialchars($row['porte_id']); ?>">
              </td>
              <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['empresa_ofertante']); ?></td>
              <td><?php echo htmlspecialchars($row['asignado_a']); ?></td>
              <td>
                <form action="cambiar_titularidad.php" method="POST" style="display:inline;" onsubmit="return confirmarTomar();">
                  <input type="hidden" name="porte_id" value="<?php echo htmlspecialchars($row['porte_id']); ?>">
                  <button class="btn-accion btn-blue">Tomar</button>
                </form>
                <a href="detalle_porte.php?id=<?php echo htmlspecialchars($row['porte_id']); ?>">
                  <button class="btn-accion btn-gray">Detalles</button>
                </a>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="8">No hay portes aceptados.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>

        <?php if($num_acept>0): ?>
          <br>
          <button type="submit" name="accion_global" value="tomar_aceptados_multiple"
                  class="btn-accion btn-green">
            Tomar seleccionados
          </button>
        <?php endif; ?>
      </form>
    </div><!-- fin aceptadosTab -->


    <!-- (B3) REOFRECIDOS -->
    <div id="reofTab" class="tab-content">
      <h2>Reofrecidos</h2>

      <!-- Filtro REOFRECIDOS -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos_todos.php">
          <input type="hidden" name="tab" value="reofTab">

          <label>Buscar:</label>
          <input type="text" name="buscar_reof" value="<?php echo htmlspecialchars($buscar_reof); ?>">

          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_reof" value="<?php echo htmlspecialchars($fd_reof); ?>">

          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_reof" value="<?php echo htmlspecialchars($fh_reof); ?>">

          <button type="submit" class="btn-accion btn-blue">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-gray"
                  onclick="window.location.href='portes_nuevos_recibidos_todos.php?tab=reofTab';">
            Limpiar
          </button>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Recogida</th>
            <th>Entrega</th>
            <th>Recibido de</th>
            <th>Asignado a</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $res_reof->data_seek(0);
        if($num_reof>0):
          while($row=$res_reof->fetch_assoc()):
        ?>
          <tr>
            <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
            <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
            <td>
              <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
              <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </td>
            <td>
              <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
              <?php echo htmlspecialchars($row['fecha_entrega']); ?>
            </td>
            <td><?php echo htmlspecialchars($row['empresa_ofertante']); ?></td>
            <td><?php echo htmlspecialchars($row['asignado_a']); ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6">No hay portes reofrecidos.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div><!-- fin reofTab -->


    <!-- (B4) REASIGNADOS -->
    <div id="reasignTab" class="tab-content">
      <h2>Reasignados</h2>

      <!-- Filtro REASIGNADOS -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos_todos.php">
          <input type="hidden" name="tab" value="reasignTab">

          <label>Buscar:</label>
          <input type="text" name="buscar_reasig" value="<?php echo htmlspecialchars($buscar_reasig); ?>">

          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_reasig" value="<?php echo htmlspecialchars($fd_reasig); ?>">

          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_reasig" value="<?php echo htmlspecialchars($fh_reasig); ?>">

          <button type="submit" class="btn-accion btn-blue">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-gray"
                  onclick="window.location.href='portes_nuevos_recibidos_todos.php?tab=reasignTab';">
            Limpiar
          </button>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Recogida</th>
            <th>Entrega</th>
            <th>Estado</th>  <!-- COLUMNA NUEVA -->
            <th>Reasignado a</th>
            <th>Fecha</th>
            <th>Responsable</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $res_reasign->data_seek(0);
        if($num_reasign>0):
          while($row=$res_reasign->fetch_assoc()):
        ?>
          <tr>
            <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
            <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
            <td>
              <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
              <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </td>
            <td>
              <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
              <?php echo htmlspecialchars($row['fecha_entrega']); ?>
            </td>
            <!-- Mostramos “Estado” -->
            <td><?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></td>
            <td><?php echo htmlspecialchars($row['reasignado_a']); ?></td>
            <td><?php echo htmlspecialchars($row['fecha_seleccion']); ?></td>
            <td><?php echo htmlspecialchars($row['responsable']); ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="8">No hay portes reasignados.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div><!-- fin reasignTab -->


    <!-- (B5) EN TREN -->
    <div id="trenTab" class="tab-content">
      <h2>En Tren</h2>

      <!-- Filtro TREN -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos_todos.php">
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
                  onclick="window.location.href='portes_nuevos_recibidos_todos.php?tab=trenTab';">
            Limpiar
          </button>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Recogida</th>
            <th>Entrega</th>
            <th>Estado</th>  <!-- COLUMNA NUEVA -->
            <th>Tren</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Asignado a</th>
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
            <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
            <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
            <td>
              <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
              <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </td>
            <td>
              <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
              <?php echo htmlspecialchars($row['fecha_entrega']); ?>
            </td>
            <!-- Muestra la nueva columna “Estado” -->
            <td><?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></td>
            <td><?php echo htmlspecialchars($row['tren_nombre']); ?></td>
            <td><?php echo htmlspecialchars($row['inicio_tren']); ?></td>
            <td><?php echo htmlspecialchars($row['fin_tren']); ?></td>
            <td>
              <?php echo htmlspecialchars($row['tren_usuario_nombre']); ?>
            </td>
            <td>
              <form action="cambiar_titularidad_completo.php" method="POST" style="display:inline;"
                    onsubmit="return confirm('¿Seguro de tomar este porte del tren?');">
                <input type="hidden" name="porte_id" value="<?php echo htmlspecialchars($row['porte_id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button class="btn-accion btn-blue">Tomar</button>
              </form>
              <a href="detalle_porte.php?id=<?php echo htmlspecialchars($row['porte_id']); ?>">
                <button class="btn-accion btn-gray">Detalles</button>
              </a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="10">No hay portes en tren.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div><!-- fin trenTab -->
  </div><!-- Fin .desktop-only -->
</main>


</body>
</html>
