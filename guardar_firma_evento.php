<?php
// Conexión a la base de datos
include('conexion.php');

// Obtener datos del formulario
$porte_id = $_POST['porte_id'];
$tipo_evento = $_POST['tipo_evento'];
$nombre_firmante = $_POST['nombre_firmante'];
$identificacion_firmante = $_POST['identificacion_firmante'];
$firma = $_POST['firma'];  // Firma en base64
$fecha_firma = date('Y-m-d H:i:s');

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
    if (file_put_contents($firma_path, $firma) !== false) {
        echo "Firma guardada correctamente en: $firma_path";
    } else {
        echo "Error al guardar la firma en el servidor.";
    }
} else {
    echo "No se recibió la firma.";
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
    echo "Error al actualizar los datos: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
