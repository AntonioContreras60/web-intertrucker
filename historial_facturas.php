<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Facturas</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php
    include 'conexion.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; // Incluir el encabezado con el menú

    $usuario_id = $_SESSION['usuario_id'];

    // Consulta para obtener el historial de facturas
    $sql = "SELECT * FROM facturas WHERE cliente_id=$usuario_id";
    $result = $conn->query($sql);

    echo "<h1>Historial de Facturas</h1>";

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "Factura ID: " . $row['id'] . " - Tipo: " . $row['tipo'] . " - Fecha: " . $row['fecha'] . "<br>";
        }
    } else {
        echo "No se encontraron facturas.";
    }

    $conn->close();

    include 'footer.php'; // Incluir el pie de página
    ?>
</body>
</html>
