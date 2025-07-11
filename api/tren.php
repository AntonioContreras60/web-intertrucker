<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "db5016197746.hosting-data.io";
$username   = "dbu4085097";
$password   = "123intertruckerya";
$dbname     = "dbs13181300";

$conn = new mysqli($servername, $username, $password, $dbname);

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
        WHERE tc.camionero_id = ?
    ";
    $stmtTrenes = $conn->prepare($sqlTrenes);
    if (!$stmtTrenes) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    $stmtTrenes->bind_param("i", $camionero_id);
    $stmtTrenes->execute();
    $resultTrenes = $stmtTrenes->get_result();
    $trenes = $resultTrenes->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["success" => true, "data" => $trenes]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    exit;
}

$conn->close();
