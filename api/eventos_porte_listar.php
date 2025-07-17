<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
/**
 * eventos_porte_listar.php
 * 
 * Devuelve (en formato JSON) todas las filas relacionadas con un `porte_id`
 * en la tabla `eventos`, incluyendo hora_llegada, hora_salida, firma, etc.
 *
 * Usa tu `conexion.php` desde la raíz.
 */

// Mostrar errores en desarrollo (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeceras para JSON
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Incluir la conexión (ajusta la ruta si es distinto tu árbol de directorios)
require_once(__DIR__ . '/../conexion.php');

$sessionUser  = $_SESSION['usuario_id'] ?? 0;
$sessionAdmin = $_SESSION['admin_id']  ?? 0;

/**
 * Asumiendo que en `conexion.php` has algo así:
 *
 *   $servername = "...";
 *   $username   = "...";
 *   $password   = "...";
 *   $dbname     = "...";
 *   $conn = new mysqli($servername, $username, $password, $dbname);
 *
 * y que `$conn` es la variable de conexión a la BD.
 */

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión a la base de datos: " . $conn->connect_error
    ]);
    exit;
}

// Verificar si recibimos `?porte_id=...`
if (!isset($_GET["porte_id"]) || empty($_GET["porte_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Falta 'porte_id' en la URL"
    ]);
    exit;
}

$porte_id = intval($_GET["porte_id"]);

// Construir la consulta para traer datos de `eventos`
$sql = "SELECT
    e.porte_id,
    e.tipo_evento,
    e.hora_llegada,
    e.geolocalizacion_llegada,
    e.hora_salida,
    e.geolocalizacion_salida,
    e.estado_mercancia,
    e.observaciones,
    e.fecha_observaciones,
    e.firma,
    e.fecha_firma,
    e.nombre_firmante,
    e.identificacion_firmante
  FROM eventos e
  JOIN portes p ON e.porte_id = p.id
  JOIN usuarios u ON p.usuario_creador_id = u.id
  WHERE e.porte_id = ?
    AND (u.admin_id = ? OR u.id = ?)";

// Ejecutar la consulta
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error en la consulta: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param('iii', $porte_id, $sessionAdmin, $sessionUser);
$stmt->execute();
$result = $stmt->get_result();

// Recopilar los registros
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

// Devolver la respuesta en formato JSON
echo json_encode([
    "success" => true,
    "data" => $rows
]);

// Cerrar conexión
$conn->close();
