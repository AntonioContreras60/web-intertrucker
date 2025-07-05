<?php
session_start();
include('conexion.php'); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// El usuario actual actúa como 'destinatario'
$usuario_id = $_SESSION['usuario_id'];

/*
  Tablas asumidas:
   - portes p (id, mercancia_descripcion, fecha_entrega, localizacion_entrega, destinatario_usuario_id, ...)
   - porte_tren pt (porte_id, tren_id)
   - tren t (id, tren_nombre)
   - tren_camionero tc (tren_id, camionero_id)
   - camioneros c (id, usuario_id)
   - usuarios u (id, cif, nombre_usuario, etc.)
*/

$sql = "
    SELECT
        p.id AS id_porte,
        p.mercancia_descripcion,
        p.fecha_entrega,
        p.localizacion_entrega,

        t.tren_nombre,

        u.cif AS cif_camionero,
        u.nombre_usuario AS nombre_camionero

    FROM portes p
    LEFT JOIN porte_tren pt ON p.id = pt.porte_id
    LEFT JOIN tren t ON pt.tren_id = t.id
    LEFT JOIN tren_camionero tc ON pt.tren_id = tc.tren_id
    LEFT JOIN camioneros c ON tc.camionero_id = c.id
    LEFT JOIN usuarios u ON c.usuario_id = u.id

    WHERE p.destinatario_usuario_id = ?
    ORDER BY p.fecha_entrega DESC
";

$stmt = $conn->prepare($sql) or die("Error en prepare: " . $conn->error);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Portes (Destinatario)</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; max-width: 1200px; margin: 0 auto; }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 8px 12px; text-align: left; }
    th { background: #f2f2f2; }
    .acciones a {
      display: inline-block;
      margin: 4px 0;
      padding: 6px 12px;
      background: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 4px;
    }
    .acciones a:hover {
      opacity: 0.9;
    }
  </style>
  <link rel="stylesheet" href="/header.css">
  <script src="/header.js"></script>
</head>
<body>

<?php include 'header.php'; ?>

<h1 style="text-align:center;">Salida-Entrada Almacen</h1>
<div style="text-align:center; margin-bottom:20px;">
  <a href="listado_expedidor.php" 
     style="display:inline-block; margin-right:20px; padding:10px 20px; background-color:#007bff; color:#fff; text-decoration:none; border-radius:4px;">
    Expedidor
  </a>
  <a href="listado_destinatario.php" 
     style="display:inline-block; padding:10px 20px; background-color:#28a745; color:#fff; text-decoration:none; border-radius:4px;">
    Destinatario
  </a>
</div>

<h2 style="text-align:center;">Listado de Portes que Entran (Destinatario)</h2>

<?php if ($result->num_rows > 0): ?>
  <table>
    <tr>
      <th>Descripción</th>
      <th>Fecha Entrega</th>
      <th>Local. Entrega</th>
      <th>Nombre del Tren</th>
      <th>CIF Camionero</th>
      <th>Nombre Camionero</th>
      <th>Acciones</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
        <td><?php echo htmlspecialchars($row['fecha_entrega']); ?></td>
        <td><?php echo htmlspecialchars($row['localizacion_entrega']); ?></td>
        <td>
          <?php echo $row['tren_nombre']
               ? htmlspecialchars($row['tren_nombre'])
               : 'Sin Tren';
          ?>
        </td>
        <td>
          <?php echo $row['cif_camionero']
               ? htmlspecialchars($row['cif_camionero'])
               : 'Pendiente';
          ?>
        </td>
        <td>
          <?php echo $row['nombre_camionero']
               ? htmlspecialchars($row['nombre_camionero'])
               : 'Pendiente';
          ?>
        </td>
        <td class="acciones">
          <!-- Sólo "Ver Entrega" para el destinatario -->
          <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id_porte']; ?>&tipo_evento=entrega">
            Ver Entrega
          </a>
          <!-- Enlace a detalle_porte.php -->
          <a href="detalle_porte.php?id=<?php echo $row['id_porte']; ?>">
            Detalles
          </a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
<?php else: ?>
  <p style="text-align:center;">No tienes portes como destinatario.</p>
<?php endif; ?>
</body>
</html>

<?php
$conn->close();
?>
