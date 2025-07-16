<?php
session_start();
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die('Debe iniciar sesión.');
}

if (!isset($_GET['id'], $_GET['tipo']) || !is_numeric($_GET['id'])) {
    die('Parámetros inválidos.');
}

$id = intval($_GET['id']);
$tipo = $_GET['tipo'];

switch ($tipo) {
    case 'usuario':
        $sql = "SELECT ruta_archivo, usuario_id AS owner
                FROM documentos_usuarios
                WHERE id = ?";
        break;
    case 'camionero':
        $sql = "SELECT d.ruta_archivo, c.usuario_id AS owner
                FROM documentos_camioneros d
                JOIN camioneros c ON d.camionero_id = c.id
                WHERE d.id = ?";
        break;
    case 'vehiculo':
        $sql = "SELECT d.ruta_archivo, v.usuario_id AS owner
                FROM documentos_vehiculos d
                JOIN vehiculos v ON d.vehiculo_id = v.id
                WHERE d.id = ?";
        break;
    case 'porte':
        $sql = "SELECT d.ruta_archivo, p.usuario_creador_id AS owner
                FROM documentos_portes d
                JOIN portes p ON d.porte_id = p.id
                WHERE d.id = ?";
        break;
    default:
        die('Tipo de documento no válido.');
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    die('Documento no encontrado.');
}

$ownerId = (int)$result['owner'];
$usuarioId = (int)$_SESSION['usuario_id'];
$esAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador';

if ($usuarioId !== $ownerId && !$esAdmin) {
    die('No tiene permiso para acceder a este documento.');
}

$ruta = __DIR__ . '/' . $result['ruta_archivo'];
if (!is_file($ruta)) {
    die('Archivo no encontrado.');
}

$nombre = basename($ruta);
$mime = mime_content_type($ruta);
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $nombre . '"');
header('Content-Length: ' . filesize($ruta));
readfile($ruta);
exit;
?>
