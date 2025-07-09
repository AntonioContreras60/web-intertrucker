<?php
/* -------- Portes pendientes asignados a una empresa -------- */
session_start();
include 'conexion.php';

ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);
if(!isset($_SESSION['usuario_id'])) die("Error: Falta usuario_id en la sesión.");
$usuarioSesion = (int)$_SESSION['usuario_id'];
if(!isset($_GET['usuario_id']))  die("Error: Falta usuario_id (empresa) en la URL.");
$empresaId = (int)$_GET['usuario_id'];

/* Nombre empresa */
$stmtNom = $conn->prepare("SELECT nombre_empresa FROM usuarios WHERE id=?");
$stmtNom->bind_param("i",$empresaId); $stmtNom->execute();
$nombreEmpresa = $stmtNom->get_result()->fetch_assoc()['nombre_empresa']??''; $stmtNom->close();

/* Portes */
$sql = "
  SELECT p.id AS porte_id, p.mercancia_descripcion,
         p.localizacion_recogida, p.fecha_recogida,
         p.localizacion_entrega, p.fecha_entrega,
         p.estado_recogida_entrega
  FROM seleccionados_oferta so
  JOIN portes p ON so.porte_id = p.id
  WHERE so.ofertante_id = ? AND so.usuario_id = ? AND p.fecha_entrega >= CURDATE()
  ORDER BY p.fecha_entrega ASC";
$stmt = $conn->prepare($sql) or die($conn->error);
$stmt->bind_param("ii",$usuarioSesion,$empresaId);
$stmt->execute(); $portes=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Portes asignados a <?= htmlspecialchars($nombreEmpresa) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/styles.css">
<style>
/* ---------- Ajuste de ancho para desktop ---------- */
@media (min-width:992px){
    main.container{max-width:1200px;}
    .desktop-only table{font-size:15px;}
}

/* ---------- Botones ---------- */
.btn{display:inline-block;padding:.55rem 1.1rem;border-radius:8px;font-weight:600;text-decoration:none;color:#fff}
.btn-gray{background:#6c757d} .btn-gray:hover{filter:brightness(90%)}
.btn-yellow{background:#ffc107;color:#000} .btn-yellow:hover{filter:brightness(92%)}
.btn-cyan{background:#17a2b8} .btn-cyan:hover{filter:brightness(92%)}

/* ---------- Cards móviles ---------- */
.card h3{margin:0 0 .4rem;font-size:1rem}
.actions a{margin-right:.4rem;margin-top:.35rem}

/* ---------- Tabla escritorio ---------- */
.desktop-only table{width:100%;border-collapse:collapse;margin-top:1rem}
.desktop-only th,.desktop-only td{border:1px solid #ccc;padding:.6rem .5rem;vertical-align:top}
.desktop-only th{background:#f5f7fa;text-align:left}
td.acciones{white-space:nowrap}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="container">
  <h1>Portes asignados a <?= htmlspecialchars($nombreEmpresa) ?></h1>

  <p><a href="portes_cedidos.php" class="btn btn-gray">&larr; Volver al listado de empresas</a></p>

  <!-- ====== MÓVIL (tarjetas) ====== -->
  <div class="mobile-only">
    <?php if($portes): foreach($portes as $p): ?>
      <div class="card">
        <h3>Porte #<?= $p['porte_id'] ?></h3>
        <p><strong>Mercancía:</strong> <?= htmlspecialchars($p['mercancia_descripcion']) ?></p>
        <p><strong>Origen:</strong> <?= htmlspecialchars($p['localizacion_recogida']) ?> — <?= htmlspecialchars($p['fecha_recogida']) ?></p>
        <p><strong>Destino:</strong> <?= htmlspecialchars($p['localizacion_entrega']) ?> — <?= htmlspecialchars($p['fecha_entrega']) ?></p>
        <p><strong>Estado:</strong> <?= htmlspecialchars($p['estado_recogida_entrega'] ?: 'Pendiente') ?></p>
        <div class="actions">
          <a href="detalle_porte.php?id=<?= $p['porte_id'] ?>" class="btn btn-gray"  target="_blank">Detalles</a>
          <a href="recogida_entrega_vista.php?porte_id=<?= $p['porte_id'] ?>&tipo_evento=recogida" class="btn btn-yellow" target="_blank">Recogida</a>
          <a href="recogida_entrega_vista.php?porte_id=<?= $p['porte_id'] ?>&tipo_evento=entrega" class="btn btn-cyan"   target="_blank">Entrega</a>
        </div>
      </div>
    <?php endforeach; else: ?>
      <p>No hay portes pendientes para esta empresa.</p>
    <?php endif; ?>
  </div>

  <!-- ====== ESCRITORIO (tabla) ====== -->
  <div class="desktop-only">
    <?php if($portes): ?>
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Mercancía</th><th>Origen</th><th>Fecha Recogida</th>
            <th>Destino</th><th>Fecha Entrega</th><th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($portes as $p): ?>
          <tr>
            <td>#<?= $p['porte_id'] ?></td>
            <td><?= htmlspecialchars($p['mercancia_descripcion']) ?></td>
            <td><?= htmlspecialchars($p['localizacion_recogida']) ?></td>
            <td><?= htmlspecialchars($p['fecha_recogida']) ?></td>
            <td><?= htmlspecialchars($p['localizacion_entrega']) ?></td>
            <td><?= htmlspecialchars($p['fecha_entrega']) ?></td>
            <td><?= htmlspecialchars($p['estado_recogida_entrega'] ?: 'Pendiente') ?></td>
            <td class="acciones">
              <a href="detalle_porte.php?id=<?= $p['porte_id'] ?>" class="btn btn-gray"  target="_blank">Detalles</a>
              <a href="recogida_entrega_vista.php?porte_id=<?= $p['porte_id'] ?>&tipo_evento=recogida" class="btn btn-yellow" target="_blank">Recogida</a>
              <a href="recogida_entrega_vista.php?porte_id=<?= $p['porte_id'] ?>&tipo_evento=entrega" class="btn btn-cyan"   target="_blank">Entrega</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No hay portes pendientes para esta empresa.</p>
    <?php endif; ?>
  </div>
</main>

<?php $conn->close(); ?>
</body>
</html>
