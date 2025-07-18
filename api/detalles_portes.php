<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once __DIR__ . '/../conexion.php';

$sessionUser  = $_SESSION['usuario_id'] ?? 0;
$sessionAdmin = $_SESSION['admin_id']  ?? 0;

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

if (!isset($_GET['tren_id']) || empty($_GET['tren_id'])) {
    echo json_encode(["success" => false, "message" => "El ID del tren es requerido."]);
    exit;
}

$tren_id = intval($_GET['tren_id']);

try {
    $sql = "
        SELECT
            portes.id AS id,
            portes.usuario_creador_id,
            portes.mercancia_descripcion,
            portes.mercancia_conservacion,
            portes.mercancia_temperatura,
            portes.tipo_camion,
            portes.cantidad,
            portes.peso_total,
            portes.volumen_total,
            portes.se_puede_remontar,
            portes.tipo_carga,
            portes.observaciones,
            portes.localizacion_recogida,
            portes.fecha_recogida,
            portes.recogida_hora_inicio,
            portes.observaciones_recogida,
            portes.localizacion_entrega,
            portes.fecha_entrega,
            portes.entrega_hora_inicio,
            portes.observaciones_entrega,
            portes.no_transbordos,
            portes.no_delegacion_transporte,
            portes.fecha_creacion,
            portes.adr,
            portes.paletizado,
            portes.intercambio_palets,
            portes.dimensiones_maximas,
            portes.recogida_hora_fin,
            portes.entrega_hora_fin,
            portes.temperatura_minima,
            portes.temperatura_maxima,
            portes.cadena_frio,
            portes.destinatario_usuario_id,
            portes.destinatario_entidad_id,
            portes.nombre_destinatario,
            portes.expedidor_usuario_id,
            portes.expedidor_entidad_id,
            portes.nombre_expedidor,
            portes.cliente_usuario_id,
            portes.cliente_entidad_id,
            portes.tipo_palet,
            portes.estado_recogida_entrega
        FROM portes
        INNER JOIN porte_tren ON portes.id = porte_tren.porte_id
        INNER JOIN usuarios u ON portes.usuario_creador_id = u.id
        WHERE porte_tren.tren_id = ?
          AND (u.admin_id = ? OR u.id = ?)
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("iii", $tren_id, $sessionAdmin, $sessionUser);
    $stmt->execute();
    $result = $stmt->get_result();
    $portes = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($portes)) {
        echo json_encode([
            "success" => true,
            "data" => [
                "portes" => [],
                "mensaje" => "No se encontraron portes asociados a este tren."
            ]
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "data" => ["portes" => $portes]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    exit;
}

$conn->close();
