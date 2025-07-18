<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
// Asegurar que se devuelve solo JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Desactivar salida de errores para evitar contaminación del JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Conexión a la base de datos
include_once __DIR__ . '/../conexion.php';

$sessionUser  = $_SESSION['usuario_id'] ?? 0;
$sessionAdmin = $_SESSION['admin_id']  ?? 0;
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión: " . $conn->connect_error
    ]);
    exit;
}

// Verificar si se recibió el usuario_id
if (!isset($_GET['usuario_id']) || empty($_GET['usuario_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "El ID del usuario es requerido."
    ]);
    exit;
}

$usuario_id = intval($_GET['usuario_id']);

// Consulta para obtener las facturas del usuario
$sqlFacturas = "SELECT
                    f.id,
                    f.usuario_id,
                    f.tren_id,
                    f.fecha,
                    f.tipo,
                    f.cantidad,
                    f.foto,
                    f.observaciones
                FROM facturas f
                JOIN usuarios u ON f.usuario_id = u.id
                WHERE f.usuario_id = ?
                  AND (u.admin_id = ? OR u.id = ?)";

$stmt = $conn->prepare($sqlFacturas);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("iii", $usuario_id, $sessionAdmin, $sessionUser);
$stmt->execute();
$resultFacturas = $stmt->get_result();
$facturas = [];

while ($row = $resultFacturas->fetch_assoc()) {
    $facturas[] = $row;
}

// Cerrar la conexión
$stmt->close();
$conn->close();

// Enviar solo JSON
echo json_encode([
    "success" => true,
    "data" => [
        "facturas" => $facturas
    ]
]);
