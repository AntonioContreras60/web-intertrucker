<?php
/**
 * eventos_resultado.php  –  Vers. 2025-05-20
 *
 * Guarda (o actualiza) el resultado de la operación de recogida
 * y devuelve la fila resultante.
 *
 * Espera un JSON:
 * {
 *   "porte_id"            : 123,          // int   – obligatorio
 *   "tipo_evento"         : "recogida",   // texto – obligatorio
 *   "resultado_operacion" : "no_recogida",// texto – obligatorio
 *   "motivo_no_recogida"  : "ABSENT",     // opcional
 *   "obs_no_recogida"     : "..."         // opcional
 * }
 */

ini_set('display_errors', 1);          // ❌ quítalo en prod.
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../conexion.php';   // ajusta si es necesario

/* ───── Conexión ─────────────────────────────────────────────────────── */
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Conexión fallida: " . $conn->connect_error
    ]);
    exit;
}

/* ───── Leer + validar obligatorios ──────────────────────────────────── */
$payload = json_decode(file_get_contents('php://input'), true);

foreach (['porte_id','tipo_evento','resultado_operacion'] as $k) {
    if (!isset($payload[$k]) || $payload[$k]==='') {
        http_response_code(400);
        echo json_encode(["success"=>false,"message"=>"Falta: $k"]);
        exit;
    }
}

$porte_id            = (int) $payload['porte_id'];
$tipo_evento         = $payload['tipo_evento'];
$resultado_op        = $payload['resultado_operacion'];
$motivo_no_recogida  = $payload['motivo_no_recogida'] ?? null;
$obs_no_recogida     = $payload['obs_no_recogida']    ?? null;

/* ───── Insert / Update SIN pisar datos con NULL ───────────────────────
 *  Si algún opcional viene como NULL o '', conservamos el valor previo.
 */
$sql = "
INSERT INTO eventos
  (porte_id, tipo_evento, resultado_operacion,
   motivo_no_recogida, obs_no_recogida, subida_resultado)
VALUES
  (?,?,?,?,?,1)
ON DUPLICATE KEY UPDATE
  resultado_operacion = VALUES(resultado_operacion),
  motivo_no_recogida  = IF(VALUES(motivo_no_recogida) IS NULL OR VALUES(motivo_no_recogida)='',
                           motivo_no_recogida,
                           VALUES(motivo_no_recogida)),
  obs_no_recogida     = IF(VALUES(obs_no_recogida) IS NULL OR VALUES(obs_no_recogida)='',
                           obs_no_recogida,
                           VALUES(obs_no_recogida)),
  subida_resultado    = 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success"=>false,"message"=>"Prepare failed: ".$conn->error]);
    exit;
}

$stmt->bind_param(
    'issss',
    $porte_id,
    $tipo_evento,
    $resultado_op,
    $motivo_no_recogida,
    $obs_no_recogida
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["success"=>false,"message"=>"Execute failed: ".$stmt->error]);
    exit;
}
$stmt->close();

/* ───── Devolvemos la fila resultante ────────────────────────────────── */
$res = $conn->query("
  SELECT porte_id, tipo_evento, resultado_operacion,
         motivo_no_recogida, obs_no_recogida, subida_resultado
  FROM   eventos
  WHERE  porte_id = {$porte_id} AND tipo_evento = '{$conn->real_escape_string($tipo_evento)}'
  LIMIT  1
");

$row = $res ? $res->fetch_assoc() : null;

echo json_encode([
    "success" => true,
    "message" => "Resultado guardado correctamente.",
    "evento"  => $row          //  ← la app ya podrá mergear con esto
]);

$conn->close();
?>
