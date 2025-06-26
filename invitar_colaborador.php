<?php
session_start();
include 'conexion.php';

// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_colaborador = trim($_POST['nombre_colaborador']);
    $email_colaborador = trim($_POST['email_colaborador']);

    // Validar que el email no esté ya registrado
    $sql_verificacion = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql_verificacion);
    if (!$stmt) {
        die("Error en la consulta SQL de verificación de email: " . $conn->error);
    }
    $stmt->bind_param("s", $email_colaborador);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<p style='font-size: 2rem; font-weight: bold;'>El correo ya está registrado.</p>";
    } else {
        // Generar token de invitación
        $token_invitacion = bin2hex(random_bytes(32));
        $admin_id = $_SESSION['usuario_id'];

        // Guardar invitación en la base de datos
        $sql_invitacion = "INSERT INTO invitaciones (email, token, admin_id, fecha_invitacion) VALUES (?, ?, ?, NOW())";
        $stmt_invitacion = $conn->prepare($sql_invitacion);
        $stmt_invitacion->bind_param("ssi", $email_colaborador, $token_invitacion, $admin_id);

        if ($stmt_invitacion->execute()) {
            // Enviar correo de invitación
            $link = "https://intertrucker.net/registro_colaborador.php?token=$token_invitacion";
            $subject = "Invitación para unirse a InterTrucker como colaborador";
            $message = "Hola $nombre_colaborador,\n\nHas sido invitado a unirte a InterTrucker. Haz clic en el siguiente enlace para registrarte: $link\n\nSaludos,\nEl equipo de InterTrucker";
            $headers = "From: info@intertrucker.net\r\n";
            $headers .= "Content-Type: text/plain; charset=utf-8\r\n";

            if (mail($email_colaborador, $subject, $message, $headers)) {
                echo "<p style='font-size: 3rem; font-weight: bold;'>Invitación enviada exitosamente.</p>";
            } else {
                echo "<p style='font-size: 3rem; font-weight: bold;'>Error al enviar el correo de invitación.</p>";
            }
        } else {
            echo "<p style='font-size: 3rem; font-weight: bold;'>Error al guardar la invitación: " . $conn->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitar Colaborador</title>
</head>
<body>
    <!-- Botón para volver a gestionar colaboradores -->
    <a href="gestionar_colaboradores.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; font-size: 2.5rem; font-weight: bold; text-decoration: none; background-color: #007bff; color: white; border-radius: 5px;">
        Volver a Gestionar Colaboradores
    </a>
</body>
</html>
