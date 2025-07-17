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
    // Consultar archivos multimedia relacionados con el porte
    $sqlMultimedia = "SELECT
                        m.id,
                        m.nombre_archivo,
                        m.tipo_archivo,
                        m.url_archivo,
                        m.geolocalizacion,
                        m.timestamp,
                        m.tamano,
                        m.categoria,
                        m.tipo_evento
                      FROM multimedia_recogida_entrega m
                      JOIN portes p ON m.porte_id = p.id
                      JOIN usuarios u ON p.usuario_creador_id = u.id
                      WHERE m.porte_id = ?
                        AND (u.admin_id = ? OR u.id = ?)";
    
    $stmtMultimedia = $conn->prepare($sqlMultimedia);

    if (!$stmtMultimedia) {
        throw new Exception("Error al preparar la consulta de multimedia: " . $conn->error);
    }

    $stmtMultimedia->bind_param("iii", $porte_id, $sessionAdmin, $sessionUser);
    $stmtMultimedia->execute();
    $resultMultimedia = $stmtMultimedia->get_result();
    $archivos = $resultMultimedia->fetch_all(MYSQLI_ASSOC);

    if (empty($archivos)) {
        echo json_encode([
            "success" => true,
            "data" => [
                "archivos" => [],
                "mensaje" => "No se encontraron archivos multimedia para este porte."
            ]
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "data" => [
                "archivos" => $archivos
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
