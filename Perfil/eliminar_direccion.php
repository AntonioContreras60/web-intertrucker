<?php
session_start();
include '../conexion.php';

// Mostrar errores (solo para depuración; quítalo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar que el usuario esté en sesión y que llegue un id
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    echo "Acceso no autorizado.";
    exit();
}

$usuario_id   = (int)$_SESSION['usuario_id'];
$direccion_id = (int)$_GET['id'];

// 1) Verificar que la dirección pertenece al usuario
$sql_verificar = "SELECT id FROM direcciones WHERE id = ? AND usuario_id = ?";
$stmt_ver = $conn->prepare($sql_verificar);
if (!$stmt_ver) {
    die("Error al preparar verificación: " . $conn->error);
}
$stmt_ver->bind_param("ii", $direccion_id, $usuario_id);
$stmt_ver->execute();
$resultado = $stmt_ver->get_result();

if ($resultado->num_rows > 0) {
    // 2) Eliminar la dirección con placeholders
    $sql_eliminar = "DELETE FROM direcciones WHERE id = ? AND usuario_id = ?";
    $stmt_del = $conn->prepare($sql_eliminar);
    if (!$stmt_del) {
        die("Error al preparar el borrado: " . $conn->error);
    }
    $stmt_del->bind_param("ii", $direccion_id, $usuario_id);

    if ($stmt_del->execute()) {
        echo "Dirección eliminada correctamente.";
    } else {
        echo "Error al eliminar la dirección: " . $stmt_del->error;
    }
    $stmt_del->close();
} else {
    echo "Acceso no autorizado o dirección no encontrada.";
}

$stmt_ver->close();
$conn->close();
?>
<!-- Enlace para regresar a la gestión de direcciones -->
<a href="gestionar_direcciones_usuario.php" style="display: block; margin-top: 20px;">
    Volver a Gestionar Direcciones
</a>
