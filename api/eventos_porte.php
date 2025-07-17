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

// Verificar si se envió el ID del porte
if (!isset($_GET['porte_id']) || empty($_GET['porte_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "El ID del porte es requerido."
    ]);
    exit;
}

$porte_id = intval($_GET['porte_id']);

try {
    // Consultar eventos relacionados con el porte
    $sqlEventos = "SELECT 
                        id,
                        porte_id,
                        tipo_evento,
                        hora_llegada,
                        geolocalizacion_llegada,
                        hora_salida,
                        geolocalizacion_salida,
                        estado_mercancia,
                        firma,
                        observaciones,
                        nombre_firmante,
                        identificacion_firmante,
                        fecha_observaciones,
                        fecha_firma
                    FROM eventos
                    WHERE porte_id = ?";
    
    $stmtEventos = $conn->prepare($sqlEventos);

    if (!$stmtEventos) {
        throw new Exception("Error al preparar la consulta de eventos: " . $conn->error);
    }

    $stmtEventos->bind_param("i", $porte_id);
    $stmtEventos->execute();
    $resultEventos = $stmtEventos->get_result();
    $eventos = $resultEventos->fetch_all(MYSQLI_ASSOC);

    if (empty($eventos)) {
        echo json_encode([
            "success" => true,
            "data" => [],
            "mensaje" => "No se encontraron eventos relacionados con este porte."
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "data" => $eventos
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
