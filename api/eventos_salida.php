<?php
// eventos_salida.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$servername = "db5016197746.hosting-data.io";
$username   = "dbu4085097";
$password   = "123intertruckerya";
$dbname     = "dbs13181300";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$requiredFields = ['porte_id', 'tipo_evento', 'hora_salida', 'geolocalizacion_salida'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(["success" => false, "message" => "El campo $field es obligatorio."]);
        exit;
    }
}

$porte_id = intval($data['porte_id']);
$tipo_evento = $conn->real_escape_string($data['tipo_evento']);
$hora_salida = $conn->real_escape_string($data['hora_salida']);
$geolocalizacion_salida = $conn->real_escape_string($data['geolocalizacion_salida']);

$sql = "INSERT INTO eventos (
    porte_id, tipo_evento, hora_salida, geolocalizacion_salida
) VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    hora_salida = VALUES(hora_salida),
    geolocalizacion_salida = VALUES(geolocalizacion_salida)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Error al preparar la consulta: " . $conn->error]);
    exit;
}
$stmt->bind_param("isss", $porte_id, $tipo_evento, $hora_salida, $geolocalizacion_salida);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Evento de salida guardado exitosamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al ejecutar la consulta: " . $stmt->error]);
}
$stmt->close();
$conn->close();
?>
