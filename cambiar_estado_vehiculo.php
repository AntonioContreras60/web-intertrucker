<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = $_POST['vehiculo_id'];
    $estado_actual = $_POST['estado_actual'];
    $nuevo_estado = $estado_actual == 1 ? 0 : 1;

    $sql = "UPDATE vehiculos SET activo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $nuevo_estado, $vehiculo_id);
        if ($stmt->execute()) {
            // Redirigir de vuelta a la página principal de vehículos después de actualizar correctamente
            header('Location: my_trucks.php');
            exit();
        } else {
            echo "Error al actualizar el estado: " . $stmt->error;
        }
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
    }
}
?>
