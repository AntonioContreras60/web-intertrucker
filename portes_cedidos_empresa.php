<?php
session_start();
include 'conexion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) {
    die("Error: Falta usuario_id en la sesión.");
}
$usuarioSesion = $_SESSION['usuario_id'];

if (!isset($_GET['usuario_id'])) {
    die("Error: Falta usuario_id (empresa) en la URL.");
}
$empresaId = (int)$_GET['usuario_id'];

$stmtNombre = $conn->prepare("SELECT nombre_empresa FROM usuarios WHERE id=?");
$stmtNombre->bind_param("i", $empresaId);
$stmtNombre->execute();
$resNom = $stmtNombre->get_result();
$nombreEmpresa = "";
if ($filaNom = $resNom->fetch_assoc()) {
    $nombreEmpresa = $filaNom['nombre_empresa'];
}
$stmtNombre->close();

$sql = "
    SELECT 
      p.id AS porte_id,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      p.estado_recogida_entrega
    FROM seleccionados_oferta so
    JOIN portes p ON so.porte_id = p.id
    WHERE so.ofertante_id = ?
      AND so.usuario_id = ?
      AND p.fecha_entrega >= CURDATE()
    ORDER BY p.fecha_entrega ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Error preparando la consulta de portes: '.$conn->error);
}
$stmt->bind_param("ii", $usuarioSesion, $empresaId);
$stmt->execute();
$res = $stmt->get_result();

$portes = [];
while ($row = $res->fetch_assoc()) {
    $portes[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portes asignados a <?php echo htmlspecialchars($nombreEmpresa); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { margin:0; font-family:Arial,sans-serif; font-size:16px; padding:16px; }
    h1 { margin: 16px 0; }
    .btn {
      display:inline-block; padding:8px 12px; background:#007bff; color:#fff;
      text-decoration:none; border-radius:4px; margin-right:8px;
    }
    .card {
      border: 1px solid #ccc; border-radius: 5px; background-color: #fff;
      padding: 10px; margin-bottom: 10px;
    }
    .card h3 { margin: 0 0 8px; font-size: 1em; font-weight: bold; }
    .card p { margin: 4px 0; font-size: 0.95em; }
    .actions a {
      display: inline-block; margin-top: 6px; margin-right: 5px;
      padding: 6px 10px; color: #fff; border-radius: 4px; text-decoration: none;
    }
    .desktop-only { display: none; }
    @media (min-width:768px) {
      .mobile-only { display: none; }
      .desktop-only { display: block; max-width: 1600px; margin: 0 auto; font-size: 14px; }
      table {
        width: 100%; border-collapse: collapse; margin-top: 16px;
      }
      th, td {
        border: 1px solid #ccc; padding: 6px 8px;
      }
      th { background: #f2f2f2; }
      .btn-accion {
        padding: 5px 10px; border: none; border-radius: 3px;
        color: #fff; text-decoration: none;
      }
    }
  </style>
  <link rel="stylesheet" href="/header.css">
  <script src="/header.js"></script>
</head>
<body>

<?php include 'header.php'; ?>

<h1>Portes asignados a <?php echo htmlspecialchars($nombreEmpresa); ?></h1>

<div style="margin-bottom:16px;">
  <a href="portes_cedidos.php" class="btn" style="background:#6c757d;">
    &larr; Volver a Listado de Empresas
  </a>
</div>

<!-- VISTA MÓVIL: tarjetas -->
<div class="mobile-only">
  <?php if (count($portes) > 0): ?>
    <?php foreach($portes as $p): ?>
      <div class="card">
        <h3>ID Porte: #<?php echo htmlspecialchars($p['porte_id']); ?></h3>
        <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($p['mercancia_descripcion']); ?></p>
        <p><strong>Origen:</strong> <?php echo htmlspecialchars($p['localizacion_recogida']); ?> - <?php echo htmlspecialchars($p['fecha_recogida']); ?></p>
        <p><strong>Destino:</strong> <?php echo htmlspecialchars($p['localizacion_entrega']); ?> - <?php echo htmlspecialchars($p['fecha_entrega']); ?></p>
        <p><strong>Estado:</strong> <?php echo htmlspecialchars($p['estado_recogida_entrega'] ?: 'Pendiente'); ?></p>
        <div class="actions">
          <a href="detalle_porte.php?id=<?php echo $p['porte_id']; ?>" style="background:#6c757d;" target="_blank">Detalles</a>
          <a href="recogida_entrega_vista.php?porte_id=<?php echo $p['porte_id']; ?>&tipo_evento=recogida" style="background-color:#ffc107; color:#000;" target="_blank">Recogida</a>
          <a href="recogida_entrega_vista.php?porte_id=<?php echo $p['porte_id']; ?>&tipo_evento=entrega" style="background-color:#17a2b8;" target="_blank">Entrega</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No hay portes pendientes para esta empresa (fecha de entrega &lt; hoy o no existen).</p>
  <?php endif; ?>
</div>

<!-- VISTA ESCRITORIO: tabla -->
<div class="desktop-only">
  <?php if (count($portes) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Mercancía</th>
          <th>Origen</th>
          <th>Fecha Recogida</th>
          <th>Destino</th>
          <th>Fecha Entrega</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($portes as $p): ?>
        <tr>
          <td>#<?php echo htmlspecialchars($p['porte_id']); ?></td>
          <td><?php echo htmlspecialchars($p['mercancia_descripcion']); ?></td>
          <td><?php echo htmlspecialchars($p['localizacion_recogida']); ?></td>
          <td><?php echo htmlspecialchars($p['fecha_recogida']); ?></td>
          <td><?php echo htmlspecialchars($p['localizacion_entrega']); ?></td>
          <td><?php echo htmlspecialchars($p['fecha_entrega']); ?></td>
          <td><?php echo htmlspecialchars($p['estado_recogida_entrega'] ?: 'Pendiente'); ?></td>
          <td>
            <a href="detalle_porte.php?id=<?php echo $p['porte_id']; ?>" class="btn-accion" style="background-color:#6c757d;" target="_blank">Detalles</a>
            <a href="recogida_entrega_vista.php?porte_id=<?php echo $p['porte_id']; ?>&tipo_evento=recogida" class="btn-accion" style="background-color:#ffc107; color:#000;" target="_blank">Recogida</a>
            <a href="recogida_entrega_vista.php?porte_id=<?php echo $p['porte_id']; ?>&tipo_evento=entrega" class="btn-accion" style="background-color:#17a2b8;" target="_blank">Entrega</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No hay portes pendientes para esta empresa (fecha de entrega &lt; hoy o no existen).</p>
  <?php endif; ?>
</div>

</body>
</html>
