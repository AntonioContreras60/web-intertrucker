<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor','camionero','asociado']);
include 'db_connection.php';
header('Content-Type: application/json');

$porte_id = (int)$_POST['porte_id'];

$admin_id = $_SESSION['admin_id'] ?? 0;
$chk = $conn->prepare("SELECT p.id FROM portes p JOIN usuarios u ON p.usuario_creador_id=u.id WHERE p.id=? AND u.admin_id=? LIMIT 1");
$chk->bind_param('ii', $porte_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    echo json_encode(['success'=>false,'message'=>'acceso denegado']);
    exit;
}
$chk->close();

$messages = [];

if (isset($_FILES['foto_recogida'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES['foto_recogida']['name']);
    $target_file = $target_dir . $file_name;
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['foto_recogida']['tmp_name']);
    if (in_array($ext, ['pdf','jpg','jpeg','png']) && in_array($mime, ['application/pdf','image/jpeg','image/png']) && move_uploaded_file($_FILES['foto_recogida']['tmp_name'], $target_file)) {
        $sql = "INSERT INTO archivos_entrega_recogida (porte_id, tipo, archivo_nombre) VALUES ($porte_id, 'foto', '$file_name')";
        mysqli_query($conn, $sql);
        $messages[] = 'foto subida';
    } else {
        $messages[] = 'error al subir foto';
    }
}

if (isset($_FILES['video_recogida'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES['video_recogida']['name']);
    $target_file = $target_dir . $file_name;
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['video_recogida']['tmp_name']);
    if (in_array($ext, ['pdf','jpg','jpeg','png']) && in_array($mime, ['application/pdf','image/jpeg','image/png']) && move_uploaded_file($_FILES['video_recogida']['tmp_name'], $target_file)) {
        $sql = "INSERT INTO archivos_entrega_recogida (porte_id, tipo, archivo_nombre) VALUES ($porte_id, 'video', '$file_name')";
        mysqli_query($conn, $sql);
        $messages[] = 'video subido';
    } else {
        $messages[] = 'error al subir video';
    }
}

if (isset($_POST['observaciones_recogida'])) {
    $observaciones = $_POST['observaciones_recogida'];
    $sql = "UPDATE portes SET observaciones_recogida = '$observaciones' WHERE id = $porte_id";
    mysqli_query($conn, $sql);
}

if (isset($_POST['registrar_hora'])) {
    $hora_actual = date('H:i:s');
    if ($_POST['registrar_hora'] == 'llegada') {
        $sql = "UPDATE portes SET hora_llegada_recogida = '$hora_actual' WHERE id = $porte_id";
    } elseif ($_POST['registrar_hora'] == 'salida') {
        $sql = "UPDATE portes SET hora_salida_recogida = '$hora_actual' WHERE id = $porte_id";
    }
    mysqli_query($conn, $sql);
}

echo json_encode(['success'=>true,'messages'=>$messages]);
?>
