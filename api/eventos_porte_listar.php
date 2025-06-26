<?php
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
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Incluir la conexión (ajusta la ruta si es distinto tu árbol de directorios)
require_once(__DIR__ . '/../conexion.php');

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
    porte_id,
    tipo_evento,
    hora_llegada,
    geolocalizacion_llegada,
    hora_salida,
    geolocalizacion_salida,
    estado_mercancia,
    observaciones,
    fecha_observaciones,
    firma,
    fecha_firma,
    nombre_firmante,
    identificacion_firmante
  FROM eventos
  WHERE porte_id = $porte_id";

// Ejecutar la consulta
$result = $conn->query($sql);
if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Error en la consulta: " . $conn->error
    ]);
    exit;
}

// Recopilar los registros
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// Devolver la respuesta en formato JSON
echo json_encode([
    "success" => true,
    "data" => $rows
]);

// Cerrar conexión
$conn->close();
