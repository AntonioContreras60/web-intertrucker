<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor','camionero','asociado']);
include('conexion.php');

// Obtener datos del formulario
$porte_id = $_POST['porte_id'];
$tipo_evento = $_POST['tipo_evento'];
$nombre_firmante = $_POST['nombre_firmante'];
$identificacion_firmante = $_POST['identificacion_firmante'];
$firma = $_POST['firma'];  // Firma en base64
$fecha_firma = date('Y-m-d H:i:s');

// Validar que el porte pertenece al admin actual
$admin_id = $_SESSION['admin_id'] ?? 0;
$chk = $conn->prepare("SELECT p.id FROM portes p JOIN usuarios u ON p.usuario_creador_id=u.id WHERE p.id=? AND u.admin_id=? LIMIT 1");
$chk->bind_param('ii', $porte_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    header('Location: error.php?error=acceso_no_autorizado');
    exit;
}
$chk->close();

// Verificar si se recibió la firma
if (!empty($firma)) {
    // Extraer la parte de datos de la imagen en base64
    list($type, $firma) = explode(';', $firma);
    list(, $firma)      = explode(',', $firma);
    $firma = base64_decode($firma);

    // Establecer el nombre del archivo usando porte_id y tipo_evento
    $firma_dir = 'firmas/';
    $firma_filename = 'firma_' . $porte_id . '_' . $tipo_evento . '.png';
    $firma_path = $firma_dir . $firma_filename;

    // Verificar si la carpeta firmas existe, si no, crearla
    if (!file_exists($firma_dir)) {
        mkdir($firma_dir, 0777, true);
    }

    // Guardar la imagen en el servidor
    file_put_contents($firma_path, $firma);
}

// Actualizar los datos en la base de datos
$sql = "UPDATE eventos SET nombre_firmante = ?, identificacion_firmante = ?, firma = ?, fecha_firma = ? WHERE porte_id = ? AND tipo_evento = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $nombre_firmante, $identificacion_firmante, $firma_path, $fecha_firma, $porte_id, $tipo_evento);

if ($stmt->execute()) {
    // Redirigir de nuevo a la página de firma con éxito
    header("Location: recogida_recibidos.php?porte_id=$porte_id&tipo_evento=$tipo_evento&success=1");
    exit();
} else {
    header('Location: error.php?error=error_generico');
    exit();
}

$stmt->close();
$conn->close();
?>
