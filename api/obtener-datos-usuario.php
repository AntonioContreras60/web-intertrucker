<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
// Mostrar errores para depuración (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar headers para JSON y seguridad
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Credenciales de la base de datos obtenidas de variables de entorno
$servername = getenv('DB_HOST');
$username   = getenv('DB_USER');
$password   = getenv('DB_PASS');
$dbname     = getenv('DB_NAME');

// Conectar a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión a la base de datos: " . $conn->connect_error
    ]);
    exit;
}

// Verificar si se envió el ID del usuario
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "El ID del usuario es requerido."
    ]);
    exit;
}

$usuario_id = intval($_GET['id']);

try {
    // Consultar los datos del usuario
    $sqlUsuario = "SELECT id AS usuario_id, email, nombre_usuario AS nombre, rol, estado FROM usuarios WHERE id = ?";
    $stmtUsuario = $conn->prepare($sqlUsuario);

    if (!$stmtUsuario) {
        throw new Exception("Error al preparar la consulta del usuario: " . $conn->error);
    }

    $stmtUsuario->bind_param("i", $usuario_id);
    $stmtUsuario->execute();
    $resultUsuario = $stmtUsuario->get_result();
    $usuario = $resultUsuario->fetch_assoc();

    if (!$usuario) {
        echo json_encode([
            "success" => false,
            "message" => "El usuario no existe."
        ]);
        exit;
    }

    // Consultar si el usuario está en la tabla camioneros
    $sqlCamionero = "SELECT id, usuario_id, tipo_carnet, num_licencia, fecha_caducidad, caducidad_profesional, fecha_nacimiento, fecha_contratacion, activo 
                     FROM camioneros WHERE usuario_id = ?";
    $stmtCamionero = $conn->prepare($sqlCamionero);

    if (!$stmtCamionero) {
        throw new Exception("Error al preparar la consulta del camionero: " . $conn->error);
    }

    $stmtCamionero->bind_param("i", $usuario_id);
    $stmtCamionero->execute();
    $resultCamionero = $stmtCamionero->get_result();
    $camionero = $resultCamionero->fetch_assoc();

    // Respuesta con los datos del usuario y camionero (si aplica)
    echo json_encode([
        "success" => true,
        "data" => [
            "usuario" => $usuario,
            "camionero" => $camionero // Esto será null si el usuario no está en la tabla camioneros
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
    exit;
}

// Cerrar la conexión
$conn->close();
?>
