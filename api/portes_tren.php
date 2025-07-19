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
    $sqlPortesTren = "SELECT pt.id, pt.porte_id, pt.usuario_id, pt.inicio_tren, pt.fin_tren
                      FROM porte_tren pt
                      JOIN portes p ON pt.porte_id = p.id
                      JOIN usuarios u ON p.usuario_creador_id = u.id
                      WHERE pt.tren_id = ?
                        AND (u.admin_id = ? OR u.id = ?)";
    $stmtPortesTren = $conn->prepare($sqlPortesTren);

    if (!$stmtPortesTren) {
        throw new Exception("Error al preparar la consulta de porte_tren: " . $conn->error);
    }

    $stmtPortesTren->bind_param("iii", $tren_id, $sessionAdmin, $sessionUser);
    $stmtPortesTren->execute();
    $resultPortesTren = $stmtPortesTren->get_result();
    $portes = $resultPortesTren->fetch_all(MYSQLI_ASSOC);
    foreach ($portes as &$p) {
        foreach (['id','porte_id','usuario_id'] as $f) {
            if (isset($p[$f])) {
                $p[$f] = (int)$p[$f];
            }
        }
    }

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
