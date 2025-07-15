//subir_facturas.php
<?php
/** ==============================================================
 *  API · InterTrucker
 *  --------------------------------------------------------------
 *  Endpoint: subir_facturas.php
 *  Sube una factura (JSON) al servidor y guarda la foto si viene
 *  --------------------------------------------------------------
 *  Cambios en esta revisión
 *    • Valida que tren_id sea > 0 cuando el tipo es combustible
 *      o mantenimiento (antes aceptaba 0 y provocaba error FK)
 *    • Mensajes de error más claros y corta la ejecución antes
 *      de intentar el INSERT cuando los datos no son válidos
 * ============================================================= */

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

/* --------------------------------------------------------------
 * 0) Conexión a la base de datos
 * ------------------------------------------------------------*/
$servername = getenv('DB_HOST');
$username   = getenv('DB_USER');
$password   = getenv('DB_PASS');
$dbname     = getenv('DB_NAME');

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: '.$conn->connect_error
    ]);
    exit;
}

/* --------------------------------------------------------------
 * 1) Leer y decodificar JSON
 * ------------------------------------------------------------*/
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'message' => 'Petición JSON mal formada.'
    ]);
    exit;
}

/* --------------------------------------------------------------
 * 2) Validar campos obligatorios
 * ------------------------------------------------------------*/
$required = ['usuario_id', 'fecha', 'tipo', 'cantidad'];
$tipoDato = $data['tipo'] ?? '';

$isComb = $tipoDato === 'combustible';
$isMant = $tipoDato === 'mantenimiento';

if ($isComb || $isMant) {
    $required[] = 'tren_id';
}

foreach ($required as $f) {
    if (!isset($data[$f]) || $data[$f] === '' || $data[$f] === null) {
        echo json_encode([
            'success' => false,
            'message' => "El campo «$f» es obligatorio."
        ]);
        exit;
    }
}

/* 🔄 NEW: tren_id debe ser > 0 cuando es obligatorio */
if (($isComb || $isMant) && intval($data['tren_id']) <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'tren_id debe ser mayor que 0.'
    ]);
    exit;
}

/* --------------------------------------------------------------
 * 3) Sanitizar / convertir datos
 * ------------------------------------------------------------*/
$usuario_id  = intval($data['usuario_id']);
$tren_id     = ($isComb || $isMant) ? intval($data['tren_id']) : null;

$timestampMs = intval($data['fecha']);          // enviado en ms
$timestamp   = intval($timestampMs / 1000);     // a segundos
$fecha       = date('Y-m-d H:i:s', $timestamp); // DATETIME MySQL

$tipo        = $conn->real_escape_string($tipoDato);
$cantidad    = floatval($data['cantidad']);

$observaciones = isset($data['observaciones'])
               ? $conn->real_escape_string($data['observaciones'])
               : null;

/* --------------------------------------------------------------
 * 4) Procesar imagen (opcional)
 * ------------------------------------------------------------*/
$fotoBase64 = $data['foto'] ?? null;
$fotoUrl    = null;

if (!empty($fotoBase64)) {
    $fotoDir = 'uploads/facturas/';
    if (!is_dir($fotoDir) && !mkdir($fotoDir, 0777, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo crear el directorio de imágenes.'
        ]);
        exit;
    }

    $filename = 'factura_' . uniqid() . '.jpg';
    $filePath = $fotoDir . $filename;

    $fotoBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $fotoBase64);
    $decoded    = base64_decode($fotoBase64);

    if (file_put_contents($filePath, $decoded) === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la imagen en el servidor.'
        ]);
        exit;
    }
    $fotoUrl = 'https://www.intertrucker.net/api/' . $filePath;
}

/* --------------------------------------------------------------
 * 5) Insertar factura en la base de datos
 * ------------------------------------------------------------*/
$sql = 'INSERT INTO facturas
        (usuario_id, tren_id, fecha, tipo, cantidad, foto, observaciones)
        VALUES (?, ?, ?, ?, ?, ?, ?)';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar statement: '.$conn->error
    ]);
    exit;
}

$stmt->bind_param(
    'iissdss',
    $usuario_id,
    $tren_id,          // puede ser null
    $fecha,
    $tipo,
    $cantidad,
    $fotoUrl,
    $observaciones
);

if ($stmt->execute()) {
    echo json_encode([
        'success'  => true,
        'message'  => 'Factura guardada exitosamente.',
        'foto_url' => $fotoUrl
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al insertar: '.$stmt->error
    ]);
}

$stmt->close();
$conn->close();
