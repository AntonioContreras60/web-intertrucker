<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php'; // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tren_id = $_POST['tren_id'] ?? null;

    if ($tren_id) {
        // Finalizar la asignación actual del tren
        $sql_finalizar = "UPDATE tren_camionero SET fin_tren_camionero = NOW() WHERE tren_id = ? AND fin_tren_camionero IS NULL";
        $stmt_finalizar = $conn->prepare($sql_finalizar);
        $stmt_finalizar->bind_param("i", $tren_id);

        if ($stmt_finalizar->execute()) {
            // Redirigir a la página de gestión tras finalizar
            header("Location: gestionar_tren_camionero.php?status=finalizado");
        } else {
            echo "Error al finalizar el tren.";
        }
    } else {
        echo "ID de tren inválido.";
    }
}
?>
