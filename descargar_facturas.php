<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['facturas']) && isset($_POST['formato'])) {
    $facturas = $_POST['facturas'];
    $formato = $_POST['formato'];
    $campos = $_POST['campos']; // Nombres personalizados de los campos

    // Validar y filtrar IDs
    $facturas = array_filter($facturas, 'is_numeric');

    if (count($facturas) > 0) {
        // Consulta para obtener las facturas seleccionadas
        $placeholders = implode(',', array_fill(0, count($facturas), '?'));
        $sql = "SELECT * FROM facturas WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($facturas)), ...$facturas);
        $stmt->execute();
        $result = $stmt->get_result();

        // Ruta base de las imágenes
        $ruta_base_imagenes = "https://intertrucker.net/uploads/facturas/";

        // Generar el archivo según el formato seleccionado
        switch ($formato) {
            case 'csv':
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="facturas.csv"');
                $output = fopen('php://output', 'w');
                // Encabezados personalizados
                fputcsv($output, array_values($campos));
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['fecha'],
                        $row['tipo'],
                        $row['cantidad'],
                        !empty($row['foto']) ? $ruta_base_imagenes . $row['foto'] : 'Sin Foto',
                        $row['hecho_por']
                    ]);
                }
                fclose($output);
                exit;

            case 'xml':
                header('Content-Type: application/xml');
                header('Content-Disposition: attachment; filename="facturas.xml"');
                echo "<?xml version=\"1.0\"?>\n<facturas>\n";
                while ($row = $result->fetch_assoc()) {
                    echo "<factura>\n";
                    foreach ($campos as $campo => $nombre) {
                        $valor = ($campo === 'foto' && !empty($row['foto'])) 
                            ? "<![CDATA[" . $ruta_base_imagenes . $row['foto'] . "]]>" 
                            : htmlspecialchars($row[$campo]);
                        echo "<$nombre>$valor</$nombre>\n";
                    }
                    echo "</factura>\n";
                }
                echo "</facturas>";
                exit;

            case 'json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="facturas.json"');
                $facturas = [];
                while ($row = $result->fetch_assoc()) {
                    $factura = [];
                    foreach ($campos as $campo => $nombre) {
                        $factura[$nombre] = ($campo === 'foto' && !empty($row['foto'])) 
                            ? $ruta_base_imagenes . $row['foto'] 
                            : $row[$campo];
                    }
                    $facturas[] = $factura;
                }
                echo json_encode($facturas, JSON_PRETTY_PRINT);
                exit;

            default:
                echo "Formato no válido.";
                exit;
        }
    } else {
        echo "No se seleccionaron facturas válidas.";
    }
} else {
    echo "Acceso no permitido.";
}
?>
