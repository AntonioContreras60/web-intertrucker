<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener el token de la URL
$token = $_GET['token'] ?? null;

if (!$token) {
    die("<p style='font-size: 2rem; font-weight: bold;'>Token inválido o inexistente.</p>");
}

// Verificar el token en la base de datos
$sql_verificacion = "SELECT id, email, expiracion_token FROM usuarios WHERE token_verificacion = ?";
$stmt_verificacion = $conn->prepare($sql_verificacion);
$stmt_verificacion->bind_param("s", $token);
$stmt_verificacion->execute();
$result = $stmt_verificacion->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario || strtotime($usuario['expiracion_token']) < time()) {
    die("<p style='font-size: 2rem; font-weight: bold;'>El token es inválido o ha expirado.</p>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];

    if ($contrasena !== $confirmar_contrasena) {
        echo "<p style='font-size: 2rem; font-weight: bold; color: red;'>Las contraseñas no coinciden. Inténtalo de nuevo.</p>";
    } else {
        $contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

        // Actualizar la contraseña y eliminar el token
        $sql_update = "UPDATE usuarios SET contrasena = ?, token_verificacion = NULL, expiracion_token = NULL WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $contrasena_hashed, $usuario['id']);

        if ($stmt_update->execute()) {
            echo "<p style='font-size: 2rem; font-weight: bold;'>Registro completado. Ya puedes iniciar sesión.</p>";
            echo "<a href='/Perfil/inicio_sesion.php'>Iniciar sesión</a>";
            exit();
        } else {
            echo "<p style='font-size: 2rem; font-weight: bold;'>Error al completar el registro: " . $conn->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Registro</title>
</head>
<body>
<h1>Establece tu Contraseña</h1>
<form method="POST">
    <label for="contrasena">Nueva Contraseña:</label>
    <input type="password" name="contrasena" required><br>
    <label for="confirmar_contrasena">Confirmar Contraseña:</label>
    <input type="password" name="confirmar_contrasena" required><br>
    <button type="submit">Completar Registro</button>
</form>
</body>
</html>
