<?php
// eventos_firma.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// DATOS DE CONEXIÓN
$servername = "db5016197746.hosting-data.io";
$username   = "dbu4085097";
$password   = "123intertruckerya";
$dbname     = "dbs13181300";

// Conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

// Leemos los datos JSON
$data = json_decode(file_get_contents("php://input"), true);

// Campos obligatorios (quitamos hora_llegada/geolocalizacion_llegada)
$requiredFields = [
    'porte_id',
    'tipo_evento',
    'firma',
    'fecha_firma',
    'nombre_firmante',
    'identificacion_firmante'
];

// Si quieres que geolocalizacion_firma sea obligatoria, agrégala al array anterior.
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        echo json_encode([
            "success" => false, 
            "message" => "El campo $field es obligatorio."
        ]);
        exit;
    }
}

// Función para extraer base64 sin "data:image..."
function extraerBase64Puro($dataUri) {
    if (strpos($dataUri, 'data:image') === 0) {
        $parts = explode(',', $dataUri);
        return $parts[1] ?? '';
    }
    return $dataUri;
}

// Función para guardar el archivo de firma en /firmas
function guardarFirmaComoArchivo($base64String, $carpetaAbs) {
    $decoded = base64_decode($base64String);
    if (!$decoded) {
        return [false, "No se pudo decodificar la firma"];
    }
    // Asegurar carpeta /firmas
    if (!is_dir($carpetaAbs)) {
        @mkdir($carpetaAbs, 0777, true);
    }
    // Nombre único
    $filename = 'firma_' . uniqid() . '.png';
    $rutaCompleta = rtrim($carpetaAbs, '/') . '/' . $filename;
    $success = file_put_contents($rutaCompleta, $decoded);
    if ($success === false) {
        return [false, "No se pudo escribir el archivo en disco"];
    }
    // Ruta web final
    $rutaWeb = '/firmas/' . $filename;  
    return [true, $rutaWeb];
}

// Variables que recibimos
$porte_id                = intval($data['porte_id'] ?? 0);
$tipo_evento             = $conn->real_escape_string($data['tipo_evento'] ?? '');
$base64firma             = $data['firma'] ?? '';
$fecha_firma             = $conn->real_escape_string($data['fecha_firma'] ?? '');
$nombre_firmante         = $conn->real_escape_string($data['nombre_firmante'] ?? '');
$identificacion_firmante = $conn->real_escape_string($data['identificacion_firmante'] ?? '');
$geolocalizacion_firma   = $conn->real_escape_string($data['geolocalizacion_firma'] ?? ''); // <-- NUEVO

// 1) Limpiamos la base64
$base64_puro = extraerBase64Puro($base64firma);

// 2) Guardar archivo en /firmas
$carpetaFirmasAbsoluta = $_SERVER['DOCUMENT_ROOT'] . '/firmas';  
list($ok, $resultado) = guardarFirmaComoArchivo($base64_puro, $carpetaFirmasAbsoluta);
if (!$ok) {
    echo json_encode(["success" => false, "message" => $resultado]);
    exit;
}
$rutaFirma = $conn->real_escape_string($resultado);

// 3) Guardar en BD
//    Ya no exigimos hora_llegada ni geolocalizacion_llegada => las dejamos en blanco si no llegan
$sql = "INSERT INTO eventos (
    porte_id, 
    tipo_evento, 
    firma, 
    fecha_firma, 
    nombre_firmante, 
    identificacion_firmante,
    geolocalizacion_firma
) VALUES (?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    firma = VALUES(firma),
    fecha_firma = VALUES(fecha_firma),
    nombre_firmante = VALUES(nombre_firmante),
    identificacion_firmante = VALUES(identificacion_firmante),
    geolocalizacion_firma = VALUES(geolocalizacion_firma)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Error al preparar la consulta: " . $conn->error
    ]);
    exit;
}
$stmt->bind_param(
    "issssss", 
    $porte_id,
    $tipo_evento,
    $rutaFirma,
    $fecha_firma,
    $nombre_firmante,
    $identificacion_firmante,
    $geolocalizacion_firma
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true, 
        "message" => "Firma guardada exitosamente.", 
        "path" => $rutaFirma
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Error al ejecutar la consulta: " . $stmt->error
    ]);
}
$stmt->close();
$conn->close();
