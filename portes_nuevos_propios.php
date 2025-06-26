<?php
session_start();
include 'conexion.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar usuario
if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}
$usuario_id = $_SESSION['usuario_id'];

// Obtener admin_id
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
    $admin_id = $usuario_id;
}
$stmt_admin->close();

// Verificar cuántos gestores tiene este admin => para mostrar submenú
$query_gestores = "
    SELECT COUNT(*) AS total_gestores
    FROM usuarios
    WHERE rol = 'gestor'
      AND admin_id = ?
";
$stmt_gestores = $conn->prepare($query_gestores);
if (!$stmt_gestores) {
    die('Error en prepare de gestores: ' . $conn->error);
}
$stmt_gestores->bind_param("i", $admin_id);
$stmt_gestores->execute();
$res_gestores = $stmt_gestores->get_result();
$row_gestores = $res_gestores->fetch_assoc();
$total_gestores = $row_gestores ? $row_gestores['total_gestores'] : 0;
$stmt_gestores->close();
$mostrar_submenu = ($total_gestores > 0);

/***************************************************************************************
 * (1) FILTROS => no_ofrecidos, ofrecidos, asignados, trenes
 ***************************************************************************************/

// 1.1 Filtros => Creados (no_ofrecidos)
$buscar_creados = isset($_GET['buscar_creados']) ? trim($_GET['buscar_creados']) : '';
$fd_creados     = isset($_GET['fecha_desde_creados']) ? trim($_GET['fecha_desde_creados']) : '';
$fh_creados     = isset($_GET['fecha_hasta_creados']) ? trim($_GET['fecha_hasta_creados']) : '';

// 1.2 Filtros => Ofrecidos
$buscar_ofrec   = isset($_GET['buscar_ofrec']) ? trim($_GET['buscar_ofrec']) : '';
$fd_ofrec       = isset($_GET['fecha_desde_ofrec']) ? trim($_GET['fecha_desde_ofrec']) : '';
$fh_ofrec       = isset($_GET['fecha_hasta_ofrec']) ? trim($_GET['fecha_hasta_ofrec']) : '';

// 1.3 Filtros => Asignados
$buscar_asig    = isset($_GET['buscar_asig']) ? trim($_GET['buscar_asig']) : '';
$fd_asig        = isset($_GET['fecha_desde_asig']) ? trim($_GET['fecha_desde_asig']) : '';
$fh_asig        = isset($_GET['fecha_hasta_asig']) ? trim($_GET['fecha_hasta_asig']) : '';

// 1.4 Filtros => Trenes
$buscar_tren    = isset($_GET['buscar_tren']) ? trim($_GET['buscar_tren']) : '';
$fd_tren        = isset($_GET['fecha_desde_tren']) ? trim($_GET['fecha_desde_tren']) : '';
$fh_tren        = isset($_GET['fecha_hasta_tren']) ? trim($_GET['fecha_hasta_tren']) : '';

/***************************************************************************************
 * (2) obtenerPortes => adaptado para recibir extraWhere y extraParams
 ***************************************************************************************/
function obtenerPortes($conn, $usuario_id, $admin_id, $tipo, $extraWhere = '', $extraParams = [], $extraTypes = '')
{
    // Base de la consulta
    $baseQuery = "
        SELECT DISTINCT p.*
        FROM portes p
        LEFT JOIN cambios_titularidad ct ON p.id = ct.porte_id
        JOIN usuarios u ON p.usuario_creador_id = u.id
        WHERE 
          (p.usuario_creador_id = ? OR ct.usuario_id_2 = ?)
          AND (ct.usuario_id_2 IS NULL OR ct.usuario_id_2 = ?)
          AND (u.admin_id = ? OR u.id = ?)
    ";

    $params = [$GLOBALS['usuario_id'], $GLOBALS['usuario_id'], $GLOBALS['usuario_id'], $GLOBALS['admin_id'], $GLOBALS['admin_id']];
    $types  = "iiiii";

    $extra = "";
    switch ($tipo) {
        case 'no_ofrecidos':
            $extra = "
                AND NOT EXISTS (
                    SELECT 1 
                    FROM ofertas_varios ov
                    WHERE ov.porte_id = p.id
                      AND ov.estado_oferta IN ('pendiente','asignado')
                )
                AND NOT EXISTS (
                    SELECT 1 
                    FROM porte_tren pt
                    WHERE pt.porte_id = p.id
                )
            ";
            break;

        case 'ofrecidos':
            $extra = "
                AND EXISTS (
                    SELECT 1
                    FROM ofertas_varios ov
                    WHERE ov.porte_id = p.id
                      AND ov.estado_oferta = 'pendiente'
                      AND ov.ofertante_id = ?
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM porte_tren pt
                    WHERE pt.porte_id = p.id
                )
            ";
            $params[] = $GLOBALS['usuario_id'];
            $types   .= "i";
            break;

        case 'asignados':
            $extra = "
                AND EXISTS (
                    SELECT 1
                    FROM ofertas_varios ov
                    WHERE ov.porte_id = p.id
                      AND ov.estado_oferta = 'asignado'
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM porte_tren pt
                    WHERE pt.porte_id = p.id
                )
            ";
            break;

        case 'asociados_trenes':
            // Reemplazamos la baseQuery por otra
            $baseQuery = "
                SELECT DISTINCT p.*, pt.tren_id, pt.inicio_tren, pt.fin_tren, t.tren_nombre AS tren_nombre
                FROM portes p
                JOIN porte_tren pt ON p.id = pt.porte_id
                JOIN tren t ON pt.tren_id = t.id
                LEFT JOIN cambios_titularidad ct ON p.id = ct.porte_id
                JOIN usuarios u ON p.usuario_creador_id = u.id
                WHERE
                (
                    (p.usuario_creador_id = ? AND (ct.usuario_id_2 IS NULL OR ct.usuario_id_2 = ?))
                    OR (ct.usuario_id_2 = ?)
                )
                AND u.id = ?
            ";
            // Base param: 4
            $params = [$GLOBALS['usuario_id'], $GLOBALS['usuario_id'], $GLOBALS['usuario_id'], $GLOBALS['usuario_id']];
            $types  = "iiii";
            break;

        default:
            throw new Exception("Tipo no reconocido: $tipo");
    }

    $finalQuery = $baseQuery . $extra . $extraWhere . " ORDER BY p.id DESC";
    $finalParams = array_merge($params, $extraParams);
    $finalTypes  = $types . $extraTypes;

    // Check placeholders
    if (substr_count($finalQuery, '?') !== count($finalParams)) {
        die("Error: # placeholders != # parámetros en $tipo. \nQuery:\n$finalQuery");
    }

    $stmt = $conn->prepare($finalQuery);
    if (!$stmt) {
        die("Error en prepare ($tipo): " . $conn->error);
    }

    if (count($finalParams) > 0) {
        if (strlen($finalTypes) !== count($finalParams)) {
            die("Error: length($finalTypes) != count(\$finalParams) en $tipo");
        }
        $stmt->bind_param($finalTypes, ...$finalParams);
    }
    $stmt->execute();
    return $stmt->get_result();
}

/***************************************************************************************
 * (3) Funciones para buildWhereLike y buildWhereFecha
 ***************************************************************************************/
function buildWhereLike($buscar, &$params, &$types) {
    $like = "%$buscar%";
    $params[] = $like; 
    $params[] = $like; 
    $params[] = $like;
    $types   .= "sss";
    return " (p.mercancia_descripcion LIKE ? 
              OR p.localizacion_recogida LIKE ? 
              OR p.localizacion_entrega LIKE ?) ";
}

function buildWhereFecha($fd, $fh, &$params, &$types) {
    $w = "";
    if ($fd !== '') {
        $w .= " AND p.fecha_recogida >= ? ";
        $params[] = $fd;
        $types   .= "s";
    }
    if ($fh !== '') {
        $w .= " AND p.fecha_recogida <= ? ";
        $params[] = $fh;
        $types   .= "s";
    }
    return $w;
}

// 3.1 => no_ofrecidos
$extraWhere_creados   = "";
$extraParams_creados  = [];
$extraTypes_creados   = "";

if ($buscar_creados !== '') {
    $extraWhere_creados .= " AND " . buildWhereLike($buscar_creados, $extraParams_creados, $extraTypes_creados);
}
$extraWhere_creados .= buildWhereFecha($fd_creados, $fh_creados, $extraParams_creados, $extraTypes_creados);

// 3.2 => ofrecidos
$extraWhere_ofrec    = "";
$extraParams_ofrec   = [];
$extraTypes_ofrec    = "";

if ($buscar_ofrec !== '') {
    $extraWhere_ofrec .= " AND " . buildWhereLike($buscar_ofrec, $extraParams_ofrec, $extraTypes_ofrec);
}
$extraWhere_ofrec .= buildWhereFecha($fd_ofrec, $fh_ofrec, $extraParams_ofrec, $extraTypes_ofrec);

// 3.3 => asignados
$extraWhere_asig    = "";
$extraParams_asig   = [];
$extraTypes_asig    = "";

if ($buscar_asig !== '') {
    $extraWhere_asig .= " AND " . buildWhereLike($buscar_asig, $extraParams_asig, $extraTypes_asig);
}
$extraWhere_asig .= buildWhereFecha($fd_asig, $fh_asig, $extraParams_asig, $extraTypes_asig);

// 3.4 => trenes
$extraWhere_tren    = "";
$extraParams_tren   = [];
$extraTypes_tren    = "";

if ($buscar_tren !== '') {
    // Agregamos manualmente la condición para tren_nombre
    $extraWhere_tren .= " AND (".buildWhereLike($buscar_tren, $extraParams_tren, $extraTypes_tren)." OR t.tren_nombre LIKE ?)";
    $extraParams_tren[] = "%$buscar_tren%";
    $extraTypes_tren   .= "s";
}
$extraWhere_tren .= buildWhereFecha($fd_tren, $fh_tren, $extraParams_tren, $extraTypes_tren);

/***************************************************************************************
 * (4) Ejecutar las consultas
 ***************************************************************************************/
$result_no_ofrecidos = obtenerPortes(
    $conn,
    $usuario_id,
    $admin_id,
    'no_ofrecidos', 
    $extraWhere_creados,
    $extraParams_creados,
    $extraTypes_creados
);
$num_rows_no_ofrecidos = $result_no_ofrecidos ? $result_no_ofrecidos->num_rows : 0;

$result_ofrecidos = obtenerPortes(
    $conn,
    $usuario_id,
    $admin_id,
    'ofrecidos', 
    $extraWhere_ofrec,
    $extraParams_ofrec,
    $extraTypes_ofrec
);
$num_rows_ofrecidos = $result_ofrecidos ? $result_ofrecidos->num_rows : 0;

$result_asignados = obtenerPortes(
    $conn,
    $usuario_id,
    $admin_id,
    'asignados', 
    $extraWhere_asig,
    $extraParams_asig,
    $extraTypes_asig
);
$num_rows_asignados = $result_asignados ? $result_asignados->num_rows : 0;

$result_trenes = obtenerPortes(
    $conn,
    $usuario_id,
    $admin_id,
    'asociados_trenes', 
    $extraWhere_tren,
    $extraParams_tren,
    $extraTypes_tren
);
$num_rows_trenes = $result_trenes ? $result_trenes->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portes Nuevos (Creados)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { margin: 0; font-family: Arial, sans-serif; font-size: 16px; }
    h1, h2 { margin: 16px; }
    nav ul { list-style: none; padding: 0; margin: 0; display: flex; gap: 20px; }
    .collapsible {
      background-color: #e2e2e2; cursor: pointer;
      padding: 12px 15px; margin-bottom: 5px;
      border: none; outline: none; width: 100%;
      text-align: left; font-size: 1em;
    }
    .collapsible.active { background-color: #ccc; }
    .content {
      display: none; padding: 10px; background-color: #f9f9f9; margin-bottom: 15px;
    }
    .card {
      border: 1px solid #ccc; border-radius: 5px; background-color: #fff;
      padding: 10px; margin-bottom: 10px;
    }
    .card h3 { margin: 0 0 8px; font-size: 1em; font-weight: bold; }
    .card p { margin: 4px 0; font-size: 0.95em; }
    .actions { margin-top: 8px; }
    .actions form, .actions a {
      /* Quitar display:inline; para cumplir requerimiento */
      margin-right: 5px;
    }
    .actions button {
      border: none; border-radius: 3px; padding: 6px 10px;
      cursor: pointer; color: #fff;
    }
    .tabs {
      display: flex; gap: 10px; list-style: none; margin: 16px; padding: 0;
    }
    .tabs li {
      background-color: #007bff; color: #fff;
      padding: 10px 15px; border-radius: 5px;
      cursor: pointer; font-weight: bold; font-size: 1em;
    }
    .tabs li:hover { background-color: #0056b3; }
    .tabs li.active { background-color: #28a745; }
    .tab-content {
      display: none; padding: 16px;
    }
    .tab-content.active { display: block; }

    @media (max-width:767px){
      .desktop-only { display: none; }
    }
    @media (min-width:768px){
      .mobile-only { display: none; }
      .desktop-only {
        max-width: 1600px; margin: 0 auto; font-size: 14px;
      }
      table {
        width: 100%; border-collapse: collapse; margin-top: 10px;
      }
      th, td {
        border: 1px solid #ccc; padding: 6px 8px;
      }
      th { background: #f2f2f2; }
      .btn-accion {
        padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;
        margin-right: 5px; color: #fff; text-decoration: none;
      }
      /* -------- NUEVO: organiza los botones dentro de la celda “Acciones” -------- */
      td.acciones{
        display:flex;
        flex-wrap:wrap;
        gap:5px;
      }
    }
    .filtro-container {
      background-color: #f2f2f2; padding: 10px; border-radius: 5px; margin-bottom: 10px;
    }
    .filtro-container label { margin-right: 5px; }
    .filtro-container input[type="text"],
    .filtro-container input[type="date"] {
      padding: 5px; margin-right: 10px;
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Collapsibles => mobile
      const collapsibles = document.querySelectorAll('.collapsible');
      collapsibles.forEach(btn => {
        btn.addEventListener('click', function() {
          this.classList.toggle('active');
          const c = this.nextElementSibling;
          c.style.display = (c.style.display === "block") ? "none" : "block";
        });
      });

      // Leer param 'tab' => mantener pestaña
      const urlParams = new URLSearchParams(window.location.search);
      const activeTab = urlParams.get('tab') || 'creadosTab';
      showSection(activeTab);
    });

    function showSection(sectionId) {
      const allTabs = document.querySelectorAll('.tabs li');
      allTabs.forEach(t => t.classList.remove('active'));

      const allContent = document.querySelectorAll('.tab-content');
      allContent.forEach(c => c.classList.remove('active'));

      const selTab = document.querySelector(`.tabs li[data-section="${sectionId}"]`);
      if (selTab) selTab.classList.add('active');

      const selContent = document.getElementById(sectionId);
      if (selContent) selContent.classList.add('active');
    }

    // Para multi-check => solo en "creados"
    function toggleTodos(chk, nameGroup) {
      const checks = document.querySelectorAll(`input[name="${nameGroup}[]"]`);
      checks.forEach(c => c.checked = chk.checked);
    }
    function confirmarHacerMultiple(){
      return confirm("¿Estás seguro de HACER los portes seleccionados?");
    }
  </script>
</head>
<body>

<?php include 'header.php'; ?>

<main>
  <h1>PORTES NUEVOS</h1>

  <!-- Menú principal (Recibidos / Creados) -->
  <nav style="margin-left:16px;">
    <ul style="list-style:none; display:flex; gap:20px;">
      <li>
        <a href="portes_nuevos_recibidos.php"
           style="display:block; padding:15px 20px; text-align:center;
                  background-color:#007bff; color:#fff; border-radius:5px;
                  text-decoration:none; font-weight:bold; font-size:1.2em;">
          Recibidos
        </a>
      </li>
      <li>
        <a href="portes_nuevos_propios.php"
           style="display:block; padding:15px 20px; text-align:center;
                  background-color:#28a745; color:#fff; border-radius:5px;
                  text-decoration:underline; font-weight:bold; font-size:1.2em;">
          Creados
        </a>
      </li>
    </ul>
  </nav>

  <!-- Submenú si hay gestores -->
  <?php if ($mostrar_submenu): ?>
    <h2>Submenú:</h2>
    <nav style="margin-left:16px;">
      <ul style="display:flex; gap:20px;">
        <li>
          <a href="portes_nuevos_propios.php"
             style="display:block; padding:15px 20px; background-color:#28a745;
                    color:white; border-radius:5px; text-decoration:underline;
                    font-weight:bold; font-size:1.2em;">
            Mios
          </a>
        </li>
        <li>
          <a href="portes_nuevos_propios_todos.php"
             style="display:block; padding:15px 20px; background-color:#007bff;
                    color:white; border-radius:5px; text-decoration:none;
                    font-weight:bold; font-size:1.2em;">
            Compañeros
          </a>
        </li>
      </ul>
    </nav>
    <br>
  <?php endif; ?>

  <!-- Botón "Crear Porte" -->
  <div style="text-align:left; margin:16px;">
    <a href="crear_porte.php"
       style="display:inline-block; padding:15px 20px; text-decoration:none;
              background-color:#007bff; color:white; border-radius:5px;
              font-weight:bold; font-size:1.2em; margin-right:15px;">
      Crear Porte
    </a>
    <!--
    <a href="importar_portes.php"
       style="display:inline-block; padding:15px 20px; text-decoration:none;
              background-color:#ff9800; color:white; border-radius:5px;
              font-weight:bold; font-size:1.2em;">
      Importar Portes
    </a>
    -->
  </div>

  <!-- ********* Versión MÓVIL => collapsibles ********* -->
  <div class="mobile-only" style="margin:16px;">
    <!-- A) Portes Creados (<?php echo $num_rows_no_ofrecidos; ?>) -->
    <button class="collapsible">
      Portes Creados (<?php echo $num_rows_no_ofrecidos; ?>)
    </button>
    <div class="content">
      <?php if($num_rows_no_ofrecidos > 0): ?>
        <?php while($row = $result_no_ofrecidos->fetch_assoc()): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Origen:</strong>
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?>
               - <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </p>
            <p><strong>Destino:</strong>
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?>
               - <?php echo htmlspecialchars($row['fecha_entrega']); ?>
            </p>
            <div class="actions">
              <a href="detalle_porte.php?id=<?php echo $row['id']; ?>">
                <button style="background-color:#6c757d;">Detalles</button>
              </a>
              <form action="hacer_oferta.php" method="POST">
                <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                <button style="background-color:#007bff;">Ofrecer</button>
              </form>
              <form action="hacer_porte.php" method="POST">
                <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                <button style="background-color:#28a745;">Hacer</button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes creados y no ofrecidos.</p>
      <?php endif; ?>
    </div>

    <!-- B) Portes Ofrecidos (<?php echo $num_rows_ofrecidos; ?>) -->
    <button class="collapsible">
      Portes Ofrecidos (<?php echo $num_rows_ofrecidos; ?>)
    </button>
    <div class="content">
      <?php if($num_rows_ofrecidos > 0): ?>
        <?php while($row = $result_ofrecidos->fetch_assoc()): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Origen:</strong>
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?>
               - <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </p>
            <p><strong>Destino:</strong>
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?>
               - <?php echo htmlspecialchars($row['fecha_entrega']); ?>
            </p>
            <div class="actions">
              <a href="detalle_porte.php?id=<?php echo $row['id']; ?>">
                <button style="background-color:#6c757d;">Detalles</button>
              </a>
              <!-- Sin formularios aquí -->
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes ofrecidos.</p>
      <?php endif; ?>
    </div>

    <!-- C) Portes Asignados (<?php echo $num_rows_asignados; ?>) -->
    <button class="collapsible">
      Portes Asignados (<?php echo $num_rows_asignados; ?>)
    </button>
    <div class="content">
      <?php if($num_rows_asignados > 0): ?>
        <?php while($row = $result_asignados->fetch_assoc()): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Origen:</strong>
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?>
               - <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </p>
            <p><strong>Destino:</strong>
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?>
               - <?php echo htmlspecialchars($row['fecha_entrega']); ?>
            </p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></p>
            <div class="actions">
              <a href="detalle_porte.php?id=<?php echo $row['id']; ?>">
                <button style="background-color:#6c757d;">Detalles</button>
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=recogida">
                <button style="background-color:#ffc107;">Recogida</button>
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=entrega">
                <button style="background-color:#17a2b8;">Entrega</button>
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes asignados.</p>
      <?php endif; ?>
    </div>

    <!-- D) Portes en Trenes (<?php echo $num_rows_trenes; ?>) -->
    <button class="collapsible">
      Portes en Trenes (<?php echo $num_rows_trenes; ?>)
    </button>
    <div class="content">
      <?php if($num_rows_trenes>0): ?>
        <?php while($row = $result_trenes->fetch_assoc()): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Tren:</strong> <?php echo htmlspecialchars($row['tren_nombre']); ?></p>
            <p><strong>Inicio:</strong> <?php echo htmlspecialchars($row['inicio_tren']); ?></p>
            <p><strong>Fin:</strong> <?php echo htmlspecialchars($row['fin_tren']); ?></p>
            <p><strong>Origen:</strong>
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?>
               - <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </p>
            <p><strong>Destino:</strong>
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?>
               - <?php echo htmlspecialchars($row['fecha_entrega']); ?>
            </p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></p>
            <div class="actions">
              <a href="detalle_porte.php?id=<?php echo $row['id']; ?>">
                <button style="background-color:#6c757d;">Detalles</button>
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=recogida">
                <button style="background-color:#ffc107;">Recogida</button>
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=entrega">
                <button style="background-color:#17a2b8;">Entrega</button>
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay portes en tren.</p>
      <?php endif; ?>
    </div>
  </div><!-- Fin .mobile-only -->


  <!-- ********* Versión ESCRITORIO => TABS / TABLES ********* -->
  <div class="desktop-only">
    <ul class="tabs">
      <li data-section="creadosTab"   onclick="showSection('creadosTab')">
        Portes Creados (<?php echo $num_rows_no_ofrecidos; ?>)
      </li>
      <li data-section="ofrecidosTab" onclick="showSection('ofrecidosTab')">
        Portes Ofrecidos (<?php echo $num_rows_ofrecidos; ?>)
      </li>
      <li data-section="asignadosTab" onclick="showSection('asignadosTab')">
        Portes Asignados (<?php echo $num_rows_asignados; ?>)
      </li>
      <li data-section="trenesTab"    onclick="showSection('trenesTab')">
        Portes en Trenes (<?php echo $num_rows_trenes; ?>)
      </li>
    </ul>

    <!-- (A) Portes Creados => no_ofrecidosTab -->
    <div id="creadosTab" class="tab-content">
      <h2>Portes Creados (No Ofrecidos)</h2>

      <!-- Filtro Creados -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_propios.php">
          <input type="hidden" name="tab" value="creadosTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_creados" value="<?php echo htmlspecialchars($buscar_creados); ?>">

          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_creados" value="<?php echo htmlspecialchars($fd_creados); ?>">

          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_creados" value="<?php echo htmlspecialchars($fh_creados); ?>">

          <button type="submit" class="btn-accion" style="background-color:#007bff;">
            Filtrar
          </button>
          <button type="button" class="btn-accion" style="background-color:#6c757d;"
                  onclick="window.location.href='portes_nuevos_propios.php?tab=creadosTab';">
            Limpiar
          </button>
        </form>
      </div>

      <!-- MULTISELECT => hacer_porte_multiple.php -->
      <form method="POST" action="hacer_porte_multiple.php" onsubmit="return confirmarHacerMultiple();">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this,'porte_id_creados')"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Origen - Fecha</th>
              <th>Destino - Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $result_no_ofrecidos->data_seek(0);
          if($num_rows_no_ofrecidos > 0):
            while($row = $result_no_ofrecidos->fetch_assoc()):
          ?>
            <tr>
              <td>
                <input type="checkbox" name="porte_id_creados[]" value="<?php echo $row['id']; ?>">
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
              <td class="acciones">
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>"
                   class="btn-accion" style="background-color:#6c757d;">
                  Detalles
                </a>
                <form action="hacer_oferta.php" method="POST">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <button class="btn-accion" style="background-color:#007bff;">Ofrecer</button>
                </form>
                <form action="hacer_porte.php" method="POST">
                  <input type="hidden" name="porte_id" value="<?php echo $row['id']; ?>">
                  <button class="btn-accion" style="background-color:#28a745;">Hacer</button>
                </form>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="6">No hay portes creados y no ofrecidos.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>

        <!-- Botón masivo => Hacer (seleccionados) -->
        <?php if($num_rows_no_ofrecidos>0): ?>
          <br>
          <button type="submit" name="accion_global" value="hacer_creados_multiple"
                  class="btn-accion" style="background-color:#28a745;">
            Hacer (seleccionados)
          </button>
        <?php endif; ?>
      </form>
    </div><!-- fin creadosTab -->

    <!-- (B) Portes Ofrecidos -->
    <div id="ofrecidosTab" class="tab-content">
      <h2>Portes Ofrecidos</h2>

      <!-- Filtro Ofrecidos -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_propios.php">
          <input type="hidden" name="tab" value="ofrecidosTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_ofrec" value="<?php echo htmlspecialchars($buscar_ofrec); ?>">

          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_ofrec" value="<?php echo htmlspecialchars($fd_ofrec); ?>">

          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_ofrec" value="<?php echo htmlspecialchars($fh_ofrec); ?>">

          <button type="submit" class="btn-accion" style="background-color:#007bff;">
            Filtrar
          </button>
          <button type="button" class="btn-accion" style="background-color:#6c757d;"
                  onclick="window.location.href='portes_nuevos_propios.php?tab=ofrecidosTab';">
            Limpiar
          </button>
        </form>
      </div>

      <!-- Sin multi-check -->
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Origen - Fecha</th>
            <th>Destino - Fecha</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result_ofrecidos->data_seek(0);
        if($num_rows_ofrecidos>0):
          while($row = $result_ofrecidos->fetch_assoc()):
        ?>
          <tr>
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
            <td class="acciones">
              <a href="detalle_porte.php?id=<?php echo $row['id']; ?>"
                 class="btn-accion" style="background-color:#6c757d;" target="_blank">
                Detalles
              </a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="5">No hay portes ofrecidos.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div><!-- fin ofrecidosTab -->

    <!-- (C) Portes Asignados -->
    <div id="asignadosTab" class="tab-content">
      <h2>Portes Asignados</h2>

      <!-- Filtro Asignados -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_propios.php">
          <input type="hidden" name="tab" value="asignadosTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_asig" value="<?php echo htmlspecialchars($buscar_asig); ?>">

          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_asig" value="<?php echo htmlspecialchars($fd_asig); ?>">

          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_asig" value="<?php echo htmlspecialchars($fh_asig); ?>">

          <button type="submit" class="btn-accion" style="background-color:#007bff;">
            Filtrar
          </button>
          <button type="button" class="btn-accion" style="background-color:#6c757d;"
                  onclick="window.location.href='portes_nuevos_propios.php?tab=asignadosTab';">
            Limpiar
          </button>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Origen - Fecha</th>
            <th>Destino - Fecha</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result_asignados->data_seek(0);
        if($num_rows_asignados>0):
          while($row = $result_asignados->fetch_assoc()):
        ?>
          <tr>
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
            <td><?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></td>
            <td class="acciones">
              <a href="detalle_porte.php?id=<?php echo $row['id']; ?>"
                 class="btn-accion" style="background-color:#6c757d;" target="_blank">
                Detalles
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=recogida"
                 class="btn-accion" style="background-color:#ffc107;" target="_blank">
                Recogida
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=entrega"
                 class="btn-accion" style="background-color:#17a2b8;" target="_blank">
                Entrega
              </a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6">No hay portes asignados.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div><!-- fin asignadosTab -->

    <!-- (D) Portes en Trenes -->
    <div id="trenesTab" class="tab-content">
      <h2>Portes en Trenes</h2>

      <!-- Filtro Trenes -->
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_propios.php">
          <input type="hidden" name="tab" value="trenesTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_tren" value="<?php echo htmlspecialchars($buscar_tren); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_tren" value="<?php echo htmlspecialchars($fd_tren); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_tren" value="<?php echo htmlspecialchars($fh_tren); ?>">

          <button type="submit" class="btn-accion" style="background-color:#007bff;">
            Filtrar
          </button>
          <button type="button" class="btn-accion" style="background-color:#6c757d;"
                  onclick="window.location.href='portes_nuevos_propios.php?tab=trenesTab';">
            Limpiar
          </button>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Tren</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Origen</th>
            <th>Destino</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result_trenes->data_seek(0);
        if($num_rows_trenes>0):
          while($row = $result_trenes->fetch_assoc()):
        ?>
          <tr>
            <td>#<?php echo htmlspecialchars($row['id']); ?></td>
            <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
            <td><?php echo htmlspecialchars($row['tren_nombre']); ?></td>
            <td><?php echo htmlspecialchars($row['inicio_tren']); ?></td>
            <td><?php echo htmlspecialchars($row['fin_tren']); ?></td>
            <td>
              <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
              <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </td>
            <td>
              <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
              <?php echo htmlspecialchars($row['fecha_entrega']); ?>
            </td>
            <td><?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></td>
            <td class="acciones">
              <a href="detalle_porte.php?id=<?php echo $row['id']; ?>"
                 class="btn-accion" style="background-color:#6c757d;" target="_blank">
                Detalles
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=recogida"
                 class="btn-accion" style="background-color:#ffc107;" target="_blank">
                Recogida
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=entrega"
                 class="btn-accion" style="background-color:#17a2b8;" target="_blank">
                Entrega
              </a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="9">No hay portes en tren.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div><!-- fin trenesTab -->
  </div><!-- Fin .desktop-only -->
</main>

</body>
</html>
