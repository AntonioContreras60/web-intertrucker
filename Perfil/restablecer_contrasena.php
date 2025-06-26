<?php
include '../conexion.php';
session_start();

// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$exito = false; // Variable para indicar si el restablecimiento fue exitoso

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verifica el token y la fecha de expiración en la base de datos
    $stmt = $conn->prepare("SELECT email FROM usuarios WHERE token_verificacion = ? AND expiracion_token > NOW()");
    if (!$stmt) {
        die("Error en la consulta: " . $conn->error);
    }
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<p style='color: red;'>El enlace de recuperación es inválido o ha expirado.</p>";
        exit;
    }

    // El token es válido, obtenemos el email del usuario
    $row = $result->fetch_assoc();
    $email = $row['email']; // Guardamos el email para la actualización

    // Procesa el formulario de nueva contraseña
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nueva_contrasena = $_POST['nueva_contrasena'];
        $confirmar_contrasena = $_POST['confirmar_contrasena'];

        // Verificar que las contraseñas coincidan
        if ($nueva_contrasena !== $confirmar_contrasena) {
            echo "<p style='color: red;'>Las contraseñas no coinciden. Inténtalo de nuevo.</p>";
        } elseif (strlen($nueva_contrasena) < 8) { // Agrega más condiciones aquí si es necesario
            echo "<p style='color: red;'>La contraseña debe tener al menos 8 caracteres.</p>";
        } else {
            // Encripta la nueva contraseña
            $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

            // Actualizar la contraseña y eliminar el token
            $stmt = $conn->prepare("UPDATE usuarios SET contrasena = ?, token_verificacion = NULL, expiracion_token = NULL WHERE email = ?");
            if (!$stmt) {
                die("Error en la actualización: " . $conn->error);
            }
            $stmt->bind_param("ss", $nueva_contrasena_hash, $email);
            if ($stmt->execute()) {
                $exito = true; // Marcar el éxito en la actualización
            } else {
                echo "<p style='color: red;'>Error al restablecer la contraseña. Por favor, inténtalo de nuevo.</p>";
            }
        }
    }
} else {
    echo "Token no proporcionado.";
    exit;
}
?>

<?php if ($exito): ?>
    <p>Tu contraseña ha sido restablecida con éxito.</p>
    <a href="inicio_sesion.php">Iniciar sesión</a>
<?php else: ?>
    <form method="post">
        <label for="nueva_contrasena">Nueva contraseña (mínimo 8 caracteres):</label>
        <input type="password" id="nueva_contrasena" name="nueva_contrasena" required>
        <input type="checkbox" onclick="togglePassword('nueva_contrasena')"> Mostrar contraseña
        
        <br><label for="confirmar_contrasena">Confirma la nueva contraseña:</label>
        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
        <input type="checkbox" onclick="togglePassword('confirmar_contrasena')"> Mostrar contraseña
        
        <input type="submit" value="Restablecer contraseña">
    </form>
<?php endif; ?>

<script>
// Función para alternar la visibilidad de la contraseña
function togglePassword(fieldId) {
    var field = document.getElementById(fieldId);
    if (field.type === "password") {
        field.type = "text";
    } else {
        field.type = "password";
    }
}
</script>
