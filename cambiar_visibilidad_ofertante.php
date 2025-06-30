<?php
// ───────────────── CONFIG/BOOT ─────────────────
session_start();
include 'conexion.php';               // ← tu conexión MySQL

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ─────────── VALIDACIÓN BÁSICA ───────────
$admin_receptor_id = isset($_POST['admin_receptor_id']) ? (int)$_POST['admin_receptor_id'] : 0;
$ofertante_id      = isset($_POST['ofertante_id'])      ? (int)$_POST['ofertante_id']      : 0;

if ($admin_receptor_id <= 0 || $ofertante_id <= 0) {
    http_response_code(400);
    exit('Parámetros inválidos');
}

// Evita que alguien se ponga a sí mismo con visibilidad completa
if ($admin_receptor_id === $ofertante_id) {
    http_response_code(400);
    exit('IDs idénticos: nada que hacer');
}

// ─────────── 1. INTENTAR UPDATE ───────────
$sqlUpd = "UPDATE contactos
              SET visibilidad = 'completo'
            WHERE usuario_id = ?
              AND contacto_usuario_id = ?
              AND visibilidad <> 'completo'";

$stmt = $conn->prepare($sqlUpd);
$stmt->bind_param('ii', $admin_receptor_id, $ofertante_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    // ─────────── 2. INSERTAR SI NO EXISTE ───────────
    $sqlIns = "INSERT IGNORE INTO contactos
                  (usuario_id, contacto_usuario_id, visibilidad)
               VALUES (?, ?, 'completo')";
    $ins = $conn->prepare($sqlIns);
    $ins->bind_param('ii', $admin_receptor_id, $ofertante_id);
    $ins->execute();
    $ins->close();
}

$stmt->close();
$conn->close();

// Respuesta simple (puedes cambiarla por JSON o redirección)
echo 'OK';
?>
