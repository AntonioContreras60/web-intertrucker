<?php
session_start();
include 'conexion.php'; // Asegúrate de tener tu archivo de conexión correcto

// Verificar si se ha recibido el ID de la oferta a seleccionar
if (isset($_POST['oferta_id']) && isset($_POST['porte_id'])) {
    $oferta_id = $_POST['oferta_id'];
    $porte_id = $_POST['porte_id'];

    // Actualizar el estado de la oferta seleccionada a "seleccionado"
    $sql_seleccionar = "UPDATE ofertas_varios SET estado_oferta = 'seleccionado' WHERE id = ?";
    $stmt_seleccionar = $conn->prepare($sql_seleccionar);
    $stmt_seleccionar->bind_param("i", $oferta_id);
    $stmt_seleccionar->execute();

    // Actualizar el estado de todas las demás ofertas del mismo porte a "no seleccionado"
    $sql_no_seleccionado = "UPDATE ofertas_varios SET estado_oferta = 'no seleccionado' WHERE porte_id = ? AND id != ?";
    $stmt_no_seleccionado = $conn->prepare($sql_no_seleccionado);
    $stmt_no_seleccionado->bind_param("ii", $porte_id, $oferta_id);
    $stmt_no_seleccionado->execute();

    echo "Oferta seleccionada correctamente y el resto marcadas como no seleccionadas.";
} else {
    echo "Error: No se recibió el ID de la oferta o del porte.";
}
?>

<!-- Botón para volver a la página anterior -->
<button onclick="window.history.back()">Volver</button>
