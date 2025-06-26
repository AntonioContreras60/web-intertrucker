<?php
/* -----------------------------------------------------------------
 *  /s14_ctrl/impersonar.php
 *  Entrar en la cuenta de un cliente (impersonación)
 *  • Sólo accesible para rol = superadmin
 *  • Registra inicio en log_impersonacion
 * ----------------------------------------------------------------- */
session_start();

/* ── 1 ▸ Verificación de súper-admin ──────────────────────────── */
if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    die('Acceso denegado');
}

/* ── 2 ▸ ID destino ───────────────────────────────────────────── */
$destino = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
if ($destino <= 0) {
    die('Usuario no válido');
}

require_once __DIR__ . '/../conexion.php';

/* ── 3 ▸ Usuario destino existe ───────────────────────────────── */
$stmt = $conn->prepare("
    SELECT id, rol, nombre_usuario, admin_id
    FROM   usuarios
    WHERE  id = ?
    LIMIT  1
");
$stmt->bind_param('i', $destino);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
if (!$u) {
    die('Usuario no encontrado');
}

/* ── 4 ▸ Guarda sesión original (solo la primera vez) ─────────── */
if (empty($_SESSION['impersonador_id'])) {
    $_SESSION['impersonador_id']          = $_SESSION['usuario_id'];
    $_SESSION['usuario_id_original']      = $_SESSION['usuario_id'];
    $_SESSION['nombre_usuario_original']  = $_SESSION['nombre_usuario'] ?? '';
}

/* ── 5 ▸ Registra inicio en log_impersonacion ─────────────────── */
$stmtLog = $conn->prepare("
    INSERT INTO log_impersonacion (super_id, usuario_id, fecha_ini)
    VALUES (?, ?, NOW())
");
$stmtLog->bind_param('ii', $_SESSION['impersonador_id'], $u['id']);
$stmtLog->execute();
$_SESSION['imp_log_id'] = $stmtLog->insert_id;

/* ── 6 ▸ Sobrescribe la sesión con la del cliente ─────────────── */
$_SESSION['usuario_id']     = $u['id'];
$_SESSION['nombre_usuario'] = $u['nombre_usuario'];
$_SESSION['rol']            = $u['rol'];
/* admin_id = su propio id si es administrador; si es gestor/camionero,
   usamos el admin_id que ya tiene almacenado */
$_SESSION['admin_id']       = ($u['rol'] === 'administrador') ? $u['id'] : $u['admin_id'];

/* ── 7 ▸ Redirige a la portada interna del sistema ────────────── */
header('Location: ../Perfil/perfil_usuario.php?observer=1');
exit;
