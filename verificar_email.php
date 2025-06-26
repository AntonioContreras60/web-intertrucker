<?php 
include 'conexion.php';
session_start();

// Habilitar la visualización de errores para desarrollo (recuerda desactivarlo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Generar un token CSRF para proteger la solicitud POST
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verificar si existe el token en la URL solo en la solicitud GET
    $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
    if (!$token) {
        die("Enlace de verificación no válido.");
    }

    // Buscar el usuario con el token de verificación
    $sql = "SELECT id, email, email_verificado FROM usuarios WHERE token_verificacion = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error interno en la base de datos.");
    }
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Enlace de verificación no válido o correo ya verificado.");
    }

    $usuario = $result->fetch_assoc();

    // Verificar si el correo ya ha sido verificado
    if ($usuario['email_verificado'] == 1) {
        echo "<h1>¡Correo ya verificado!</h1>";
        exit;
    }

    // Marcar el correo como verificado
    $sql_update = "UPDATE usuarios SET email_verificado = 1 WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $usuario['id']);
    $stmt_update->execute();

    // Guardar el usuario verificado en la sesión para la siguiente solicitud POST
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_email'] = $usuario['email'];

    // Mostrar el mensaje de éxito y botón para asociar entidades
    echo "<h1>¡Correo verificado con éxito!</h1>";
    echo "<p>Haz clic aquí para permitir que otros contactos puedan enviarle ofertas de portes.</p>";
    echo "<button id='aceptarButton'>Aceptar</button>";

?>

<script>
// JavaScript para manejar el botón de aceptar y hacer la solicitud POST
document.addEventListener("DOMContentLoaded", function() {
    const aceptarButton = document.getElementById('aceptarButton');
    if (aceptarButton) {
        aceptarButton.addEventListener('click', function() {
            // Obtener el token CSRF desde la sesión
            const csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";

            fetch('verificar_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=asociar&csrf_token=${csrfToken}`
            })
            .then(response => response.text())
            .then(data => {
                alert(data.trim()); // Remover espacios o saltos de línea
                window.location.replace('Perfil/inicio_sesion.php'); // Redirigir a la página de inicio de sesión
            })
            .catch(error => console.error('Error:', error));
        });
    }
});
</script>

<?php
} // cerramos el if de token


// Procesar la acción de asociación de entidades en la solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'asociar') {
    // Validar el token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Content-Type: text/plain');
        die("Solicitud no válida.");
    }

    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_email'])) {
        header('Content-Type: text/plain');
        die("Error: sesión no válida.");
    }

    // Conexión a la base de datos (asegúrate de que $conn está definido)
    // $conn = new mysqli('host', 'usuario', 'contraseña', 'base_de_datos');

    // Variables del usuario actual
    $usuario_id = $_SESSION['usuario_id'];       // ID del usuario que se está registrando (la entidad convertida en usuario)
    $usuario_email = $_SESSION['usuario_email'];

    // Cambiar el tipo de contenido a texto plano
    header('Content-Type: text/plain');
    // ob_clean(); // Limpia el buffer de salida para asegurarse de que no hay contenido adicional

    // Paso 1: Asociar las entidades propias al usuario con el mismo email
    $sql_update_entidades = "UPDATE entidades SET entidad_usuario_id = ? WHERE email = ?";
    $stmt_entidades = $conn->prepare($sql_update_entidades);
    $stmt_entidades->bind_param("is", $usuario_id, $usuario_email);

    if ($stmt_entidades->execute()) {
        echo "Entidades propias asociadas correctamente.\n";

        // Paso 2: Obtener todas las entidades que tienen el mismo email, pero pertenecen a otros usuarios
        $sql_select_otras_entidades = "SELECT * FROM entidades WHERE email = ? AND entidad_usuario_id = ?";
        $stmt_otras_entidades = $conn->prepare($sql_select_otras_entidades);
        $stmt_otras_entidades->bind_param("si", $usuario_email, $usuario_id);
        $stmt_otras_entidades->execute();
        $resultado_otras_entidades = $stmt_otras_entidades->get_result();

        if ($resultado_otras_entidades->num_rows > 0) {
            while ($entidad = $resultado_otras_entidades->fetch_assoc()) {
                // Obtener el usuario propietario de la entidad original
                $propietario_usuario_id = $entidad['usuario_id']; // Usuario que tenía registrada la entidad
                $observaciones_contacto = $entidad['Observaciones']; // Observaciones de la entidad original

                // Crear el contacto en la cuenta del propietario original
                $sql_insert_contacto = "INSERT INTO contactos (usuario_id, contacto_usuario_id, fecha_agregado, observaciones) VALUES (?, ?, NOW(), ?)";
                $stmt_contacto = $conn->prepare($sql_insert_contacto);
                $stmt_contacto->bind_param("iis", $propietario_usuario_id, $usuario_id, $observaciones_contacto);

                if ($stmt_contacto->execute()) {
                    echo "Contacto creado correctamente para el usuario propietario con ID: $propietario_usuario_id.\n";
                } else {
                    echo "Error al crear el contacto para el usuario propietario con ID: $propietario_usuario_id.\n";
                }
            }
        } else {
            echo "No se encontraron entidades de otros usuarios con el mismo email.\n";
        }
    } else {
        echo "Error al asociar entidades.\n";
    }

    // Limpiar las variables de sesión para no repetir la asociación
    unset($_SESSION['usuario_id']);
    unset($_SESSION['usuario_email']);
    unset($_SESSION['csrf_token']);
    exit;
}
?>



