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
                        id,
                        nombre_archivo,
                        tipo_archivo,
                        url_archivo,
                        geolocalizacion,
                        timestamp,
                        tamano,
                        categoria,
                        tipo_evento
                      FROM multimedia_recogida_entrega
                      WHERE porte_id = ?";
    
    $stmtMultimedia = $conn->prepare($sqlMultimedia);

    if (!$stmtMultimedia) {
        throw new Exception("Error al preparar la consulta de multimedia: " . $conn->error);
    }

    $stmtMultimedia->bind_param("i", $porte_id);
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
