<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

if (isset($_GET['grupo_id']) && isset($_GET['contacto_id'])) {
    $grupo_id = $_GET['grupo_id'];
    $contacto_id = $_GET['contacto_id'];

    // Eliminar el contacto del grupo
    $sql = "DELETE FROM grupo_contactos WHERE grupo_id = ? AND contacto_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $grupo_id, $contacto_id);

    if ($stmt->execute()) {
        echo "Contacto eliminado correctamente.";
    } else {
        echo "Error al eliminar el contacto.";
    }

    // Redirigir de vuelta a la página del grupo
    header("Location: ver_grupo.php?grupo_id=" . $grupo_id);
    exit();
}
?>
