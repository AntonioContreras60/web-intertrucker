<?php
session_start();
include 'conexion.php'; // Ajusta el nombre de tu archivo de conexión
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar que exista usuario_id en la sesión:
if (!isset($_SESSION['usuario_id'])) {
    die("Error: Falta usuario_id en la sesión.");
}
$usuarioSesion = $_SESSION['usuario_id'];

// Construimos la query para extraer:
// - empresa_id = so.usuario_id (a quién le asignaste el porte)
// - u.nombre_empresa = nombre de la empresa
// - la cuenta de portes (COUNT(*)) que tengan fecha_entrega >= hoy
$sql = "
    SELECT 
      so.usuario_id AS empresa_id,
      u.nombre_empresa AS empresa_nombre,
      COUNT(*) AS total_portes
    FROM seleccionados_oferta so
    JOIN portes p ON so.porte_id = p.id
    JOIN usuarios u ON so.usuario_id = u.id
    WHERE so.ofertante_id = ?
      AND p.fecha_entrega >= CURDATE()
    GROUP BY so.usuario_id
    ORDER BY u.nombre_empresa ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Error preparando la consulta: '.$conn->error);
}
$stmt->bind_param("i", $usuarioSesion);
$stmt->execute();
$res = $stmt->get_result();

$empresas = [];
while ($row = $res->fetch_assoc()) {
    $empresas[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <!-- 1. META VIEWPORT PARA HACERLO RESPONSIVE -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Empresas Asignadas</title>
  <style>
    body {
      font-family: Arial, sans-serif; 
      margin: 0;
      padding: 16px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 16px;
    }
    th, td {
      border: 1px solid #ccc; 
      padding: 8px;
    }
    th {
      background-color: #f2f2f2;
    }
    .btn {
      display: inline-block;
      padding: 8px 12px;
      background: #007bff;
      color: #fff;
      text-decoration: none;
      border-radius: 4px;
    }

    /* 3. MEDIA QUERY PARA MÓVILES */
    @media (max-width: 600px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }
      thead tr {
        display: none;
      }
      td {
        position: relative;
        padding-left: 50%;
        text-align: left;
      }
      td::before {
        position: absolute;
        top: 8px;
        left: 8px;
        white-space: nowrap;
        font-weight: bold;
      }
      td:nth-of-type(1)::before { content: "ID"; }
      td:nth-of-type(2)::before { content: "Empresa"; }
      td:nth-of-type(3)::before { content: "Nº Portes sin finalizar"; }
      td:nth-of-type(4)::before { content: "Acción"; }
    }
  </style>
</head>
<body>

<!-- Header -->
<?php include 'header.php'; ?>

<h1>Portes Transferidos a Empresas</h1>

<?php if (count($empresas) > 0): ?>

  <!-- 2. CONTENEDOR CON DESPLAZAMIENTO HORIZONTAL PARA PANTALLAS PEQUEÑAS -->
  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Empresa / Transportista</th>
          <th>Nº Portes sin finalizar</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($empresas as $e): ?>
        <tr>
          <td><?php echo htmlspecialchars($e['empresa_id']); ?></td>
          <td><?php echo htmlspecialchars($e['empresa_nombre']); ?></td>
          <td><?php echo htmlspecialchars($e['total_portes']); ?></td>
          <td>
            <a href="portes_cedidos_empresa.php?usuario_id=<?php echo $e['empresa_id']; ?>" 
               class="btn">
              Ver portes
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php else: ?>
  <p>No hay empresas con portes asignados que tengan fecha de entrega pendiente (>= hoy).</p>
<?php endif; ?>

<!-- Footer -->

</body>
</html>
