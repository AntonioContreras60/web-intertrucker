<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor']);
include 'conexion.php'; // Conexión a la base de datos

$oferta_id = intval($_POST['oferta_id'] ?? 0); // Oferta seleccionada
$porte_id = intval($_POST['porte_id'] ?? 0);    // Porte ID
$ofertas_aceptadas = isset($_POST['ofertas_aceptadas']) ? array_map('intval', $_POST['ofertas_aceptadas']) : [];

// Comprobar que el porte pertenece a la empresa del usuario
$admin_id = $_SESSION['admin_id'] ?? 0;
$chk = $conn->prepare(
    "SELECT p.id
       FROM portes p
       JOIN usuarios u ON p.usuario_creador_id = u.id
      WHERE p.id = ? AND u.admin_id = ?"
);
$chk->bind_param('ii', $porte_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    http_response_code(403);
    exit('Acceso denegado');
}
$chk->close();

// Verificar que la oferta seleccionada pertenece al mismo porte
$chk = $conn->prepare(
    "SELECT id FROM ofertas_varios WHERE id = ? AND porte_id = ?"
);
$chk->bind_param('ii', $oferta_id, $porte_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    http_response_code(400);
    exit('Oferta o porte no válidos');
}
$chk->close();

// Marcar la oferta seleccionada
$stmt = $conn->prepare(
    "UPDATE ofertas_varios SET estado_oferta='seleccionado' WHERE id=? AND porte_id=?"
);
$stmt->bind_param('ii', $oferta_id, $porte_id);
$stmt->execute();
$stmt->close();

// Marcar el resto como no seleccionado
if (!empty($ofertas_aceptadas)) {
    $stmt = $conn->prepare(
        "UPDATE ofertas_varios SET estado_oferta='no_seleccionado' WHERE id=? AND porte_id=?"
    );
    foreach ($ofertas_aceptadas as $oid) {
        if ($oid != $oferta_id) {
            $stmt->bind_param('ii', $oid, $porte_id);
            $stmt->execute();
        }
    }
    $stmt->close();
}

$conn->close();
?>
