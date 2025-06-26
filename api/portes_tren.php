<?php
// Mostrar errores para depuración (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar headers para JSON y seguridad
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Credenciales de la base de datos
$servername = "db5016197746.hosting-data.io";
$username = "dbu4085097";
$password = "123intertruckerya";
$dbname = "dbs13181300";

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

// Verificar si se envió el ID del tren
if (!isset($_GET['tren_id']) || empty($_GET['tren_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "El ID del tren es requerido."
    ]);
    exit;
}

$tren_id = intval($_GET['tren_id']);

try {
    // Consultar portes asociados al tren
    $sqlPortesTren = "SELECT id, porte_id, usuario_id, inicio_tren, fin_tren 
                      FROM porte_tren 
                      WHERE tren_id = ?";
    $stmtPortesTren = $conn->prepare($sqlPortesTren);

    if (!$stmtPortesTren) {
        throw new Exception("Error al preparar la consulta de porte_tren: " . $conn->error);
    }

    $stmtPortesTren->bind_param("i", $tren_id);
    $stmtPortesTren->execute();
    $resultPortesTren = $stmtPortesTren->get_result();
    $portes = $resultPortesTren->fetch_all(MYSQLI_ASSOC);

    if (empty($portes)) {
        echo json_encode([
            "success" => true,
            "data" => [
                "portes" => [],
                "mensaje" => "No se encontraron portes para este tren."
            ]
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "data" => [
                "portes" => $portes
            ]
        ]);
    }
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
