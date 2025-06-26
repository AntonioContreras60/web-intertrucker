<?php
include '../conexion.php';

// Mostrar errores (para depuración; quita en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Recibir datos del formulario
$email = isset($_POST['email']) ? $_POST['email'] : '';
$nueva_contrasena = isset($_POST['nueva_contrasena']) ? $_POST['nueva_contrasena'] : '';
$confirmar_contrasena = isset($_POST['confirmar_contrasena']) ? $_POST['confirmar_contrasena'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($nueva_contrasena === $confirmar_contrasena) {
        $hashed_password = password_hash($nueva_contrasena, PASSWORD_BCRYPT);

        // UPDATE con placeholders
        $sql = "UPDATE usuarios SET contrasena = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Error al preparar la consulta: " . $conn->error);
        }

        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            echo "Contraseña restablecida con éxito.<br>";
            echo "<a href='perfil.php'>Volver al Perfil</a>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Las contraseñas no coinciden.<br>";
        echo "<a href='reset_password.php'>Volver a intentarlo</a>";
    }
} else {
    echo "Método no permitido.";
}

$conn->close();
?>
</body>
</html>
