<?php
include 'conexion.php'; // Conexión a la base de datos

$oferta_id = intval($_POST['oferta_id']); // Oferta seleccionada
$porte_id = intval($_POST['porte_id']);    // Porte ID
$ofertas_aceptadas = $_POST['ofertas_aceptadas']; // IDs de las ofertas en aceptado

// Ejecutar la consulta para actualizar el estado de la oferta seleccionada
$sql_update = "
UPDATE ofertas_varios
SET estado_oferta = CASE 
    WHEN id = $oferta_id THEN 'seleccionado'
    WHEN id IN (" . implode(',', $ofertas_aceptadas) . ") AND id != $oferta_id THEN 'no_seleccionado'
    ELSE estado_oferta
END
WHERE porte_id = $porte_id;
";

// Ejecutar la consulta
$conn->query($sql_update);

// Cerrar la conexión
$conn->close();
?>
