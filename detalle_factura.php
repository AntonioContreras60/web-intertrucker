<?php
session_start();
include 'conexion.php';

// Verificar si se ha pasado un ID de factura
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Asegurarse de que el ID sea un número

    // Consulta para obtener los detalles de la factura
    $sql = "SELECT fecha, tipo, cantidad, foto, observaciones FROM facturas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Obtener los datos de la factura
        $factura = $result->fetch_assoc();
    } else {
        echo "Factura no encontrada.";
        exit;
    }

    $stmt->close();
} else {
    echo "No se ha proporcionado un ID de factura.";
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de la Factura - InterTrucker</title>
    <link rel="stylesheet" href="styles.css">
<link rel='stylesheet' href='/header.css'>
<script src='/header.js'></script>
</head>
<body>
<?php require_once $_SERVER["DOCUMENT_ROOT"]."/header.php"; ?>
    <!-- Incluir el menú de navegación -->

    <main>
        <h1>Detalles de la Factura</h1>

        <!-- Mostrar los detalles de la factura -->
        <table>
            <tr>
                <th>Fecha:</th>
                <td><?php echo htmlspecialchars($factura['fecha']); ?></td>
            </tr>
            <tr>
                <th>Tipo de Gasto:</th>
                <td><?php echo htmlspecialchars($factura['tipo']); ?></td>
            </tr>
            <tr>
                <th>Cantidad:</th>
                <td><?php echo number_format($factura['cantidad'], 2) . ' €'; ?></td>
            </tr>
            <tr>
                <th>Observaciones:</th>
                <td><?php echo nl2br(htmlspecialchars($factura['observaciones'])); ?></td>
            </tr>
            <?php if (!empty($factura['foto'])): ?>
            <tr>
                <th>Foto:</th>
                <td><img src="<?php echo htmlspecialchars($factura['foto']); ?>" alt="Foto de la factura" style="max-width: 300px;"></td>
            </tr>
            <?php endif; ?>
        </table>

        <a href="facturas.php" class="button">Volver al Listado de Facturas</a>
    </main>

    <!-- Incluir el pie de página -->
    <?php include 'footer.php'; ?>
</body>
</html>
