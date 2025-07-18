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

// Conectar a la base de datos
include_once __DIR__ . '/../conexion.php';

$sessionUser  = $_SESSION['usuario_id'] ?? 0;
$sessionAdmin = $_SESSION['admin_id']  ?? 0;

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
    $sqlUsuario = "SELECT id AS usuario_id, email, nombre_usuario AS nombre, rol, estado
                   FROM usuarios
                   WHERE id = ? AND (admin_id = ? OR id = ?)";
    $stmtUsuario = $conn->prepare($sqlUsuario);

    if (!$stmtUsuario) {
        throw new Exception("Error al preparar la consulta del usuario: " . $conn->error);
    }

    $stmtUsuario->bind_param("iii", $usuario_id, $sessionAdmin, $sessionUser);
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
    $sqlCamionero = "SELECT c.id, c.usuario_id, c.tipo_carnet, c.num_licencia, c.fecha_caducidad, c.caducidad_profesional, c.fecha_nacimiento, c.fecha_contratacion, c.activo
                     FROM camioneros c
                     JOIN usuarios u ON c.usuario_id = u.id
                     WHERE c.usuario_id = ? AND (u.admin_id = ? OR u.id = ?)";
    $stmtCamionero = $conn->prepare($sqlCamionero);

    if (!$stmtCamionero) {
        throw new Exception("Error al preparar la consulta del camionero: " . $conn->error);
    }

    $stmtCamionero->bind_param("iii", $usuario_id, $sessionAdmin, $sessionUser);
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
