<?php
include '../conexion.php';
session_start();

// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$enlace_enviado = false; // Variable para controlar si el enlace fue enviado con éxito

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar el email
    $email = trim($_POST['email']);

    // Verificar si el email existe en la base de datos
    $stmt = $conn->prepare("SELECT nombre_usuario FROM usuarios WHERE email = ?");
    
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<p style='color: red;'>El correo electrónico no está registrado.</p>";
    } else {
        // Obtener el nombre del usuario
        $row = $result->fetch_assoc();
        $nombre_usuario = $row['nombre_usuario'];

        // Generar un token de recuperación y fecha de expiración
        $token = bin2hex(random_bytes(32));
        $expiration = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Actualizar el token y la expiración en la base de datos
        $stmt = $conn->prepare("UPDATE usuarios SET token_verificacion = ?, expiracion_token = ? WHERE email = ?");
        if (!$stmt) {
            die("Error en la preparación de la consulta de actualización: " . $conn->error);
        }

        $stmt->bind_param("sss", $token, $expiration, $email);
        
        if ($stmt->execute()) {
            // Enviar el email de recuperación de contraseña
            $resetLink = "https://intertrucker.net/Perfil/restablecer_contrasena.php?token=" . $token;
            $subject = "Recuperación de contraseña en InterTrucker";
            $message = "Hola $nombre_usuario,\n\nHemos recibido una solicitud para restablecer tu contraseña en InterTrucker.net. Haz clic en el siguiente enlace para restablecerla:\n$resetLink\n\nEste enlace expirará en una hora.\n\nSi no solicitaste este cambio, puedes ignorar este mensaje.\n\nGracias.";
            $headers = "From: info@intertrucker.net\r\n";

            if (mail($email, $subject, $message, $headers)) {
                $enlace_enviado = true; // El email fue enviado con éxito
                echo "<p>Te hemos enviado un enlace de recuperación a tu correo.</p>";
            } else {
                echo "<p style='color: red;'>Error al enviar el correo de recuperación. Por favor, inténtalo de nuevo.</p>";
            }
        } else {
            echo "<p style='color: red;'>Error al guardar el token de recuperación.</p>";
        }
    }

    // Cerrar el statement y la conexión
    $stmt->close();
    $conn->close();
}
?>

<?php if (!$enlace_enviado): ?>
<form method="post">
    <label for="email">Introduce tu email para recuperar la contraseña:</label>
    <input type="email" name="email" required>
    <input type="submit" value="Enviar enlace de recuperación">
</form>
<?php endif; ?>
