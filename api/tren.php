<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once __DIR__ . '/../conexion.php';

$sessionUser  = $_SESSION['usuario_id'] ?? 0;
$sessionAdmin = $_SESSION['admin_id']  ?? 0;

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

if (!isset($_GET['usuario_id']) || empty($_GET['usuario_id'])) {
    echo json_encode(["success" => false, "message" => "El ID del camionero es requerido."]);
    exit;
}

$camionero_id = intval($_GET['usuario_id']); // <-- Se asume que 'usuario_id' es en realidad el ID del camionero

try {
    $sqlTrenes = "
        SELECT t.id, t.tren_nombre
        FROM tren AS t
        INNER JOIN tren_camionero AS tc ON t.id = tc.tren_id
        INNER JOIN camioneros c ON tc.camionero_id = c.id
        INNER JOIN usuarios u ON c.usuario_id = u.id
        WHERE tc.camionero_id = ?
          AND (u.admin_id = ? OR u.id = ?)
    ";
    $stmtTrenes = $conn->prepare($sqlTrenes);
    if (!$stmtTrenes) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    $stmtTrenes->bind_param("iii", $camionero_id, $sessionAdmin, $sessionUser);
    $stmtTrenes->execute();
    $resultTrenes = $stmtTrenes->get_result();
    $trenes = $resultTrenes->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["success" => true, "data" => $trenes]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    exit;
}

$conn->close();
