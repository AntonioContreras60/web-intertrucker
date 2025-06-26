<?php
session_start();
include 'conexion.php';

// Solo permitir acceso si el usuario es administrador
if ($_SESSION['rol'] !== 'administrador') {
    die("No tiene permiso para realizar esta acción.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $nuevo_estado = $_POST['nuevo_estado'];

    // Validar entrada
    if (!in_array($nuevo_estado, ['activo', 'inactivo'])) {
        die("Estado no válido.");
    }

    // Actualizar estado en la base de datos
    $sql = "UPDATE usuarios SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la consulta: " . $conn->error);
    }

    $stmt->bind_param("si", $nuevo_estado, $usuario_id);

    if ($stmt->execute()) {
        header("Location: detalles_colaborador.php?id=$usuario_id");
        exit();
    } else {
        die("Error al actualizar el estado: " . $stmt->error);
    }
}
?>
