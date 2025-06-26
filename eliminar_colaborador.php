<?php
session_start();
include 'conexion.php';

$colaborador_id = $_POST['colaborador_id'];

// Eliminar el colaborador de la base de datos
$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $colaborador_id);

if ($stmt->execute()) {
    header("Location: gestionar_colaboradores.php");
} else {
    echo "Error al eliminar el colaborador: " . $conn->error;
}
?>
