<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = $_POST['vehiculo_id'];
    $observaciones = $_POST['observaciones'];

    $sql = "UPDATE vehiculos SET observaciones = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $observaciones, $vehiculo_id);
        if ($stmt->execute()) {
            echo "Observaciones actualizadas correctamente.";
        } else {
            echo "Error al actualizar las observaciones: " . $stmt->error;
        }
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
    }
    
    // Redirigir de vuelta a los detalles del vehículo
    header('Location: ver_detalles_vehiculo.php?vehiculo_id=' . $vehiculo_id);
    exit();
}
?>
