<?php
session_start();
include '../conexion.php';

// Mostrar errores (solo para depuración; quítalo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    die("No tiene permiso para realizar esta acción (no hay sesión).");
}

$usuario_id = (int)$_SESSION['usuario_id'];

// Obtener las contraseñas del formulario
$contrasena_actual    = $_POST['contrasena_actual']    ?? '';
$nueva_contrasena     = $_POST['nueva_contrasena']     ?? '';
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

// 1) Verificar la contraseña actual con una consulta preparada
$sql_select = "SELECT contrasena FROM usuarios WHERE id = ? LIMIT 1";
$stmt_sel = $conn->prepare($sql_select);
if (!$stmt_sel) {
    die("Error al preparar la consulta SELECT: " . $conn->error);
}
$stmt_sel->bind_param("i", $usuario_id);
$stmt_sel->execute();
$res_sel = $stmt_sel->get_result();

if ($res_sel->num_rows > 0) {
    $row = $res_sel->fetch_assoc();
    $hash_actual = $row['contrasena'];

    // Comprobar la contraseña actual
    if (password_verify($contrasena_actual, $hash_actual)) {
        // Verificar que la nueva contraseña coincide con la confirmación
        if ($nueva_contrasena === $confirmar_contrasena) {
            // Encriptar la nueva contraseña
            $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT);

            // 2) Hacer UPDATE con placeholders
            $sql_update = "UPDATE usuarios SET contrasena = ? WHERE id = ?";
            $stmt_up = $conn->prepare($sql_update);
            if (!$stmt_up) {
                die("Error al preparar el UPDATE: " . $conn->error);
            }
            $stmt_up->bind_param("si", $nueva_contrasena_hash, $usuario_id);

            if ($stmt_up->execute()) {
                echo "Contraseña cambiada con éxito.<br>";
                echo "<a href='perfil_usuario.php'>Volver a mi perfil</a>";
            } else {
                echo "Error al actualizar la contraseña: " . $stmt_up->error;
            }
            $stmt_up->close();
        } else {
            echo "Las nuevas contraseñas no coinciden.";
        }
    } else {
        echo "La contraseña actual es incorrecta.";
    }
} else {
    echo "Usuario no encontrado o ID inválido.";
}

$stmt_sel->close();
$conn->close();
?>
