<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(["administrador","gestor","camionero","asociado"]);
// Conexión a la base de datos
include('conexion.php');

// Obtener datos del formulario
$porte_id = $_POST['porte_id'];
$tipo_evento = $_POST['tipo_evento'];
$estado_mercancia = $_POST['estado_mercancia'];
$observaciones = $_POST['observaciones'];
$fecha_observaciones = date('Y-m-d H:i:s'); // Registrar la fecha y hora actuales

// Validar que no haya valores nulos
if (empty($porte_id) || empty($tipo_evento) || empty($estado_mercancia)) {
    echo "Error: falta información.";
    exit;
}
$admin_id = $_SESSION["admin_id"] ?? 0;
$chk = $conn->prepare("SELECT p.id FROM portes p JOIN usuarios u ON p.usuario_creador_id=u.id WHERE p.id=? AND u.admin_id=? LIMIT 1");
$chk->bind_param("ii", $porte_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    http_response_code(403);
    exit("Acceso denegado");
}
$chk->close();

// Preparar la consulta SQL para actualizar
$sql = "UPDATE eventos SET estado_mercancia = ?, observaciones = ?, fecha_observaciones = ? WHERE porte_id = ? AND tipo_evento = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo "Error en la preparación de la consulta: " . $conn->error;
    exit;
}

// Enlazar los parámetros (los tipos 's' indican cadenas, 'i' indica enteros)
$stmt->bind_param("sssis", $estado_mercancia, $observaciones, $fecha_observaciones, $porte_id, $tipo_evento);

// Ejecutar la consulta
if ($stmt->execute()) {
    // Redirigir a la página anterior
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    echo "Error al actualizar los datos: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
