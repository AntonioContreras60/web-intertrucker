<?php
/* -----------------------------------------------------------------
 *  /s14_ctrl/salir_impersonar.php
 *  Abandona la impersonación y vuelve al panel Super-Admin
 *  • Completa la fila de log_impersonacion
 * ----------------------------------------------------------------- */
session_start();

/* Si no venimos de impersonar, vuelve al dashboard */
if (!isset($_SESSION['impersonador_id'])) {
    header('Location: dashboard.php');
    exit();
}

include '../conexion.php';

/* ── 1 ▸ Cierra el registro en log_impersonacion ───────────────── */
if (!empty($_SESSION['imp_log_id'])) {
    $stmt = $conn->prepare("
        UPDATE log_impersonacion
           SET fecha_fin = NOW()
         WHERE id = ? AND fecha_fin IS NULL
         LIMIT 1
    ");
    $stmt->bind_param('i', $_SESSION['imp_log_id']);
    $stmt->execute();
}

/* ── 2 ▸ Restaura la sesión original ───────────────────────────── */
$_SESSION['usuario_id']     = $_SESSION['usuario_id_original'];
$_SESSION['nombre_usuario'] = $_SESSION['nombre_usuario_original'];
$_SESSION['rol']            = 'superadmin';
/* opcional: restaura admin_id por si lo usas en tu backend */
if (isset($_SESSION['admin_id_original'])) {
    $_SESSION['admin_id'] = $_SESSION['admin_id_original'];
}

/* ── 3 ▸ Limpia variables auxiliares ───────────────────────────── */
unset($_SESSION['impersonador_id'],
      $_SESSION['usuario_id_original'],
      $_SESSION['nombre_usuario_original'],
      $_SESSION['admin_id_original'],
      $_SESSION['imp_log_id']);

/* ── 4 ▸ Vuelve a la lista de empresas ────────────────────────── */
header('Location: empresas.php');
exit;
