<?php
/* ---------- LISTADO DE PORTES – EXPEDIDOR ---------- */
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
$usuario_id = (int)$_SESSION['usuario_id'];

/* --- Consulta --- */
$sql = "
    SELECT
        p.id AS id_porte,
        p.mercancia_descripcion,
        p.fecha_recogida,
        p.localizacion_recogida,
        t.tren_nombre,
        u.cif            AS cif_camionero,
        u.nombre_usuario AS nombre_camionero
    FROM portes p
    LEFT JOIN porte_tren     pt ON p.id       = pt.porte_id
    LEFT JOIN tren           t  ON pt.tren_id = t.id
    LEFT JOIN tren_camionero tc ON pt.tren_id = tc.tren_id
    LEFT JOIN camioneros     c  ON tc.camionero_id = c.id
    LEFT JOIN usuarios       u  ON c.usuario_id    = u.id
    WHERE p.expedidor_usuario_id = ?
    ORDER BY p.fecha_recogida DESC
";
$stmt = $conn->prepare($sql) or die($conn->error);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Salida-Entrada Almacén – Expedidor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/styles.css">
<style>
/* ancho total + tabla con scroll horizontal si hace falta */
main{max-width:100%;padding:var(--spacing-large);}
.tabla-scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;margin-top:var(--spacing-base)}
th,td{border:1px solid #ccc;padding:.6rem .5rem;text-align:left;font-size:15px}
th{background:#f5f7fa}

/* botones (reutilizan colores globales) */
.btn{display:inline-block;padding:.55rem 1.1rem;border-radius:8px;font-weight:600;text-decoration:none;color:#fff}
.btn-green{background:#28a745} .btn-green:hover{filter:brightness(92%)}
.btn-blue {background:#007bff} .btn-blue:hover {filter:brightness(92%)}
.btn-gray {background:#6c757d} .btn-gray:hover{filter:brightness(92%)}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
  <h1 style="text-align:center;margin:0;">Salida-Entrada Almacén</h1>

  <!-- pestañas Expedidor / Destinatario -->
  <div style="text-align:center;margin:var(--spacing-large) 0;">
      <a href="listado_expedidor.php"    class="btn btn-green" style="margin-right:20px">Expedidor</a>
      <a href="listado_destinatario.php" class="btn btn-blue">Destinatario</a>
  </div>

  <h2 style="text-align:center;margin-bottom:var(--spacing-base);">
      Listado de Portes que Salen (Expedidor)
  </h2>

  <?php if ($result->num_rows > 0): ?>
    <div class="tabla-scroll">
      <table>
        <thead>
          <tr>
            <th>Descripción</th>
            <th>Fecha Recogida</th>
            <th>Local. Recogida</th>
            <th>Tren</th>
            <th>CIF Camionero</th>
            <th>Nombre Camionero</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['mercancia_descripcion']) ?></td>
            <td><?= htmlspecialchars($row['fecha_recogida']) ?></td>
            <td><?= htmlspecialchars($row['localizacion_recogida']) ?></td>
            <td><?= $row['tren_nombre'] ? htmlspecialchars($row['tren_nombre']) : 'Sin Tren' ?></td>
            <td><?= $row['cif_camionero'] ? htmlspecialchars($row['cif_camionero']) : 'Pendiente' ?></td>
            <td><?= $row['nombre_camionero'] ? htmlspecialchars($row['nombre_camionero']) : 'Pendiente' ?></td>
            <td class="acciones">
              <a href="recogida_entrega_vista.php?porte_id=<?= $row['id_porte'] ?>&tipo_evento=recogida"
                 class="btn btn-gray">Ver Recogida</a>
              <a href="detalle_porte.php?id=<?= $row['id_porte'] ?>"
                 class="btn btn-gray">Detalles</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p style="text-align:center;">No tienes portes como expedidor.</p>
  <?php endif; ?>
</main>

<?php $conn->close(); ?>
</body>
</html>
