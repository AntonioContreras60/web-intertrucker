<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
// eventos_llegada.php

// Mostrar errores para depuración (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar headers para JSON y seguridad
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Incluir el archivo de conexión que se encuentra en la raíz
require_once("../conexion.php");  // Ajusta la ruta según tu estructura

// Verificar conexión (asumiendo que $conn se establece en conexion.php)
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión a la base de datos: " . $conn->connect_error
    ]);
    exit;
}

// Leer y decodificar datos JSON enviados en el cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$requiredFields = ['porte_id', 'tipo_evento', 'hora_llegada', 'geolocalizacion_llegada'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode([
            "success" => false,
            "message" => "El campo $field es obligatorio."
        ]);
        exit;
    }
}

// Preparar datos
$porte_id = intval($data['porte_id']);
$tipo_evento = $conn->real_escape_string($data['tipo_evento']);
$hora_llegada = $conn->real_escape_string($data['hora_llegada']);
$geolocalizacion_llegada = $conn->real_escape_string($data['geolocalizacion_llegada']);
$hora_salida = isset($data['hora_salida']) ? $conn->real_escape_string($data['hora_salida']) : null;
$geolocalizacion_salida = isset($data['geolocalizacion_salida']) ? $conn->real_escape_string($data['geolocalizacion_salida']) : null;

// Insertar o actualizar el registro (usando clave única en porte_id y tipo_evento)
$sql = "INSERT INTO eventos (
    porte_id, tipo_evento, hora_llegada, geolocalizacion_llegada, hora_salida, geolocalizacion_salida
) VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    hora_llegada = VALUES(hora_llegada),
    geolocalizacion_llegada = VALUES(geolocalizacion_llegada),
    hora_salida = VALUES(hora_salida),
    geolocalizacion_salida = VALUES(geolocalizacion_salida)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Error al preparar la consulta: " . $conn->error]);
    exit;
}
$stmt->bind_param("isssss", $porte_id, $tipo_evento, $hora_llegada, $geolocalizacion_llegada, $hora_salida, $geolocalizacion_salida);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Evento de llegada guardado exitosamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al ejecutar la consulta: " . $stmt->error]);
}
$stmt->close();
$conn->close();
?>
