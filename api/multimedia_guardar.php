<?php
/**
 * -------------------------------------------------------------
 * multimedia_guardar.php   –   v2 (mayo 2025)
 * Sube un archivo (foto / vídeo) y lo registra en la BD
 * -------------------------------------------------------------
 * • Guarda los ficheros en  /api/uploads/
 * • Devuelve la URL **absoluta**  https://intertrucker.net/api/uploads/<archivo>
 * • Respeta el campo  categoria  recibido desde la app
 * -------------------------------------------------------------
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// La respuesta será JSON
header('Content-Type: application/json; charset=UTF-8');

// --------------------------------------------------
// 1. Conexión a la base de datos
// --------------------------------------------------
require_once __DIR__ . '/../conexion.php';

if (!$conn) {
    echo json_encode([
        "success" => false,
        "message" => "Error en la conexión a la base de datos"
    ]);
    exit;
}

// --------------------------------------------------
// 2. Preparar carpeta de destino
// --------------------------------------------------
$uploadsDir = __DIR__ . '/uploads';                // Ruta física: …/api/uploads/
if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo crear la carpeta uploads"
    ]);
    exit;
}
if (!is_writable($uploadsDir)) {
    echo json_encode([
        "success" => false,
        "message" => "No hay permisos de escritura en la carpeta uploads"
    ]);
    exit;
}

// --------------------------------------------------
// 3. Validar el archivo recibido
// --------------------------------------------------
if (
    !isset($_FILES['archivo']) ||
    $_FILES['archivo']['error'] !== UPLOAD_ERR_OK
) {
    echo json_encode([
        "success" => false,
        "message" => "Error al recibir archivo",
        "error_code" => $_FILES['archivo']['error'] ?? "No se envió ningún archivo"
    ]);
    exit;
}

$nombreOriginal = basename($_FILES['archivo']['name']);
$extension      = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

$tipoArchivo = in_array($extension, ['jpg', 'jpeg', 'png'])
             ? 'foto'
             : ($extension === 'mp4' ? 'video' : 'desconocido');

if ($tipoArchivo === 'desconocido') {
    echo json_encode([
        "success" => false,
        "message" => "Tipo de archivo no permitido"
    ]);
    exit;
}

// --------------------------------------------------
// 4. Mover el archivo a /uploads
// --------------------------------------------------
$nombreArchivo = uniqid('', true) . '.' . $extension;
$destino       = $uploadsDir . '/' . $nombreArchivo;

if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
    echo json_encode([
        "success" => false,
        "message" => "Error al mover el archivo al servidor"
    ]);
    exit;
}

$tamanoKB = round(filesize($destino) / 1024, 2);   // Tamaño con dos decimales

// URL pública absoluta (ajusta el subdominio si usas “www.” en tus demás APIs)
$baseURL    = 'https://intertrucker.net/api/uploads/';
$urlArchivo = $baseURL . $nombreArchivo;

// --------------------------------------------------
// 5. Datos recibidos por POST
// --------------------------------------------------
$porte_id        = isset($_POST['porte_id']) ? intval($_POST['porte_id']) : null;
$tipo_evento     = $_POST['tipo_evento']     ?? null;
$geolocalizacion = $_POST['geolocalizacion'] ?? null;
$categoria       = $_POST['categoria']       ?? 'carga';   // respeta la categoría enviada

if (empty($porte_id) || empty($tipo_evento)) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan parámetros requeridos (porte_id, tipo_evento)"
    ]);
    exit;
}

// --------------------------------------------------
// 6. Insertar registro en la base de datos
// --------------------------------------------------
$sql = "
    INSERT INTO multimedia_recogida_entrega
        (nombre_archivo, tipo_archivo, url_archivo, geolocalizacion,
         timestamp, tamano, categoria, porte_id, tipo_evento)
    VALUES
        (?, ?, ?, ?, NOW(), ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta",
        "error"   => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "sssssdis",
    $nombreArchivo,
    $tipoArchivo,
    $urlArchivo,
    $geolocalizacion,
    $tamanoKB,
    $categoria,
    $porte_id,
    $tipo_evento
);

if ($stmt->execute()) {
    echo json_encode([
        "success"     => true,
        "message"     => "Archivo subido y registrado",
        "file_name"   => $nombreArchivo,
        "url"         => $urlArchivo
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al guardar en base de datos",
        "error"   => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
