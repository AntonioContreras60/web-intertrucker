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
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Conectar a la base de datos
include_once __DIR__ . '/../conexion.php';
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

// Leer y decodificar datos JSON enviados
$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$requiredFields = ['porte_id', 'tipo_evento', 'estado_mercancia', 'observaciones', 'fecha_observaciones'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(["success" => false, "message" => "El campo $field es obligatorio."]);
        exit;
    }
}

// Preparar datos
$porte_id = intval($data['porte_id']);
$tipo_evento = $conn->real_escape_string($data['tipo_evento']);
$estado_mercancia = $conn->real_escape_string($data['estado_mercancia']);
$observaciones = $conn->real_escape_string($data['observaciones']);
$fecha_observaciones = $conn->real_escape_string($data['fecha_observaciones']);

// Verificar si el evento ya existe
$sql_check = "SELECT COUNT(*) as total FROM eventos WHERE porte_id = ? AND tipo_evento = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("is", $porte_id, $tipo_evento);
$stmt_check->execute();
$result = $stmt_check->get_result();
$row = $result->fetch_assoc();
$stmt_check->close();

if ($row['total'] > 0) {
    // Si el evento ya existe, actualizarlo
    $sql = "UPDATE eventos
            SET estado_mercancia = ?, observaciones = ?, fecha_observaciones = ?
            WHERE porte_id = ? AND tipo_evento = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error al preparar la consulta: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("sssis", $estado_mercancia, $observaciones, $fecha_observaciones, $porte_id, $tipo_evento);
} else {
    echo json_encode(["success" => false, "message" => "No existe un evento registrado con porte_id=$porte_id y tipo_evento=$tipo_evento"]);
    exit;
}

// Ejecutar la consulta
if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Estado de la mercancía actualizado correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
