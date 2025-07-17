<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
// Habilitar errores para depurar (en producción se apaga)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Encabezados HTTP
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Credenciales de la base de datos obtenidas de variables de entorno
$servername = getenv('DB_HOST');
$username   = getenv('DB_USER');
$password   = getenv('DB_PASS');
$dbname     = getenv('DB_NAME');

// Conectar al servidor
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión: " . $conn->connect_error
    ]);
    exit;
}

// Revisar usuario_id
if (!isset($_GET['usuario_id']) || empty($_GET['usuario_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "El ID del usuario es requerido."
    ]);
    exit;
}
$usuario_id = intval($_GET['usuario_id']);

// Fecha límite 7 días atrás
$fechaLimite = date('Y-m-d', strtotime('-7 days'));

try {
    // 1) Obtener camionero.id a partir de usuario_id
    $sqlCamionero = "SELECT id FROM camioneros WHERE usuario_id = ?";
    $stmtCamionero = $conn->prepare($sqlCamionero);
    if (!$stmtCamionero) {
        throw new Exception("Error al preparar consulta de camionero: " . $conn->error);
    }
    $stmtCamionero->bind_param("i", $usuario_id);
    $stmtCamionero->execute();
    $resultCamionero = $stmtCamionero->get_result();
    $rowCamionero = $resultCamionero->fetch_assoc();

    if (!$rowCamionero) {
        // No es camionero
        echo json_encode([
            "success" => true,
            "data" => [
                "tren" => null,
                "portes" => [],
                "mensaje" => "Este usuario no es camionero o no tiene tren."
            ]
        ]);
        exit;
    }
    $camionero_id = intval($rowCamionero['id']);

    // 2) Buscar tren (1 a 1)
    $sqlTren = "
       SELECT t.id AS tren_id, t.tren_nombre
         FROM tren_camionero AS tc
         INNER JOIN tren AS t ON tc.tren_id = t.id
        WHERE tc.camionero_id = ?
        LIMIT 1
    ";
    $stmtTren = $conn->prepare($sqlTren);
    if (!$stmtTren) {
        throw new Exception("Error al preparar consulta de tren: " . $conn->error);
    }
    $stmtTren->bind_param("i", $camionero_id);
    $stmtTren->execute();
    $resultTren = $stmtTren->get_result();
    $rowTren = $resultTren->fetch_assoc();

    if (!$rowTren) {
        // Camionero no tiene tren
        echo json_encode([
            "success" => true,
            "data" => [
                "tren" => null,
                "portes" => [],
                "mensaje" => "El camionero no tiene tren asignado."
            ]
        ]);
        exit;
    }
    $tren_id = intval($rowTren['tren_id']);

    // 3) Buscar portes de ese tren con filtro de últimos 7 días o futuros
    $sqlPortes = "
       SELECT p.id, p.usuario_creador_id, p.mercancia_descripcion,
              p.mercancia_conservacion, p.mercancia_temperatura,
              p.tipo_camion, p.cantidad, p.peso_total, p.volumen_total,
              p.se_puede_remontar, p.tipo_carga, p.observaciones,
              p.localizacion_recogida, p.fecha_recogida,
              p.horario_llegada_recogida, p.recogida_hora_inicio,
              p.observaciones_recogida, p.localizacion_entrega,
              p.fecha_entrega, p.horario_llegada_entrega,
              p.entrega_hora_inicio, p.observaciones_entrega,
              p.no_transbordos, p.no_delegacion_transporte,
              p.fecha_creacion, p.conservacion, p.adr,
              p.paletizado, p.intercambio_palets, p.dimensiones_maximas,
              p.recogida_hora_fin, p.entrega_hora_fin,
              p.temperatura_minima, p.temperatura_maxima, p.cadena_frio,
              p.destinatario_usuario_id, p.destinatario_entidad_id,
              p.nombre_destinatario, p.expedidor_usuario_id,
              p.expedidor_entidad_id, p.nombre_expedidor,
              p.cliente_usuario_id, p.cliente_entidad_id,
              p.tipo_palet, p.estado_recogida_entrega
         FROM portes AS p
         INNER JOIN porte_tren AS pt ON p.id = pt.porte_id
        WHERE pt.tren_id = ?
          AND (
            p.fecha_recogida >= ? OR
            p.fecha_entrega >= ?
          )
    ";
    $stmtPortes = $conn->prepare($sqlPortes);
    if (!$stmtPortes) {
        throw new Exception("Error al preparar consulta de portes: " . $conn->error);
    }
    // tren_id es entero, fechaLimite es string
    $stmtPortes->bind_param("iss", $tren_id, $fechaLimite, $fechaLimite);
    $stmtPortes->execute();
    $resultPortes = $stmtPortes->get_result();
    $portes = $resultPortes->fetch_all(MYSQLI_ASSOC);

    // Respuesta final
    echo json_encode([
        "success" => true,
        "data" => [
            "tren" => [
                "tren_id" => $tren_id,
                "tren_nombre" => $rowTren['tren_nombre']
            ],
            "portes" => $portes
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
    exit;
}

// Cerrar conexión
$conn->close();
