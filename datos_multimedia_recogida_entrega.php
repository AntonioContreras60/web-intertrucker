<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor','camionero','asociado']);
include('conexion.php');
header('Content-Type: application/json');

if (isset($_POST['evento_id'], $_POST['tipo_archivo'], $_POST['url_archivo'], $_POST['tamano'])) {
    $evento_id     = (int)$_POST['evento_id'];
    $tipo_archivo  = $_POST['tipo_archivo'];
    $url_archivo   = $_POST['url_archivo'];
    $tamano        = $_POST['tamano'];
    $geolocalizacion = $_POST['geolocalizacion'] ?? null;

    $admin_id = $_SESSION['admin_id'] ?? 0;
    $chk = $conn->prepare("SELECT e.id FROM eventos e JOIN portes p ON e.porte_id=p.id JOIN usuarios u ON p.usuario_creador_id=u.id WHERE e.id=? AND u.admin_id=? LIMIT 1");
    $chk->bind_param('ii', $evento_id, $admin_id);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        echo json_encode(['status'=>'error','message'=>'acceso denegado']);
        exit;
    }
    $chk->close();

    $stmt = $conn->prepare("INSERT INTO multimedia_recogida_entrega (evento_id, tipo_archivo, url_archivo, geolocalizacion, tamano, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        echo json_encode(['status'=>'error','message'=>'prepare failed']);
        exit;
    }
    $stmt->bind_param('isssi', $evento_id, $tipo_archivo, $url_archivo, $geolocalizacion, $tamano);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Archivo registrado']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Error al insertar']);
    }
    $stmt->close();
} else {
    echo json_encode(['status'=>'error','message'=>'Datos incompletos']);
}

mysqli_close($conn);
?>
