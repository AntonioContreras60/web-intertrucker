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

// Conectar a la base de datos
include_once __DIR__ . '/../conexion.php';

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
    // Consultar eventos relacionados con el porte
    $sqlEventos = "SELECT
                        e.id,
                        e.porte_id,
                        e.tipo_evento,
                        e.hora_llegada,
                        e.geolocalizacion_llegada,
                        e.hora_salida,
                        e.geolocalizacion_salida,
                        e.estado_mercancia,
                        e.firma,
                        e.observaciones,
                        e.nombre_firmante,
                        e.identificacion_firmante,
                        e.fecha_observaciones,
                        e.fecha_firma
                    FROM eventos e
                    JOIN portes p ON e.porte_id = p.id
                    JOIN usuarios u ON p.usuario_creador_id = u.id
                    WHERE e.porte_id = ?
                      AND (u.admin_id = ? OR u.id = ?)";
    
    $stmtEventos = $conn->prepare($sqlEventos);

    if (!$stmtEventos) {
        throw new Exception("Error al preparar la consulta de eventos: " . $conn->error);
    }

    $stmtEventos->bind_param("iii", $porte_id, $sessionAdmin, $sessionUser);
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
