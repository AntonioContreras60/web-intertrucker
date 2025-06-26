<?php
// Asegurar que se devuelve solo JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Desactivar salida de errores para evitar contaminación del JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Conexión a la base de datos
$servername = "db5016197746.hosting-data.io";
$username   = "dbu4085097";
$password   = "123intertruckerya";
$dbname     = "dbs13181300";

$conn = new mysqli($servername, $username, $password, $dbname);
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
                    id,
                    usuario_id,
                    tren_id,
                    fecha,
                    tipo,
                    cantidad,
                    foto,
                    observaciones
                FROM facturas
                WHERE usuario_id = ?";

$stmt = $conn->prepare($sqlFacturas);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $usuario_id);
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
