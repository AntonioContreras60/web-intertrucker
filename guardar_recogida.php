<?php
// Conexión a la base de datos
include 'db_connection.php';

// Obtener el ID del porte
$porte_id = $_POST['porte_id'];

// Subida de fotos de recogida
if (isset($_FILES['foto_recogida'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES['foto_recogida']['name']);
    $target_file = $target_dir . $file_name;

    // Mover el archivo a la carpeta 'uploads'
    if (move_uploaded_file($_FILES['foto_recogida']['tmp_name'], $target_file)) {
        // Insertar la ruta del archivo en la base de datos
        $sql = "INSERT INTO archivos_entrega_recogida (porte_id, tipo, archivo_nombre) 
                VALUES ($porte_id, 'foto', '$file_name')";
        mysqli_query($conn, $sql);
        echo "Foto de la recogida subida y registrada correctamente.";
    } else {
        echo "Error al subir la foto de la recogida.";
    }
}

// Subida de videos de recogida
if (isset($_FILES['video_recogida'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES['video_recogida']['name']);
    $target_file = $target_dir . $file_name;

    // Mover el archivo a la carpeta 'uploads'
    if (move_uploaded_file($_FILES['video_recogida']['tmp_name'], $target_file)) {
        // Insertar la ruta del archivo en la base de datos
        $sql = "INSERT INTO archivos_entrega_recogida (porte_id, tipo, archivo_nombre) 
                VALUES ($porte_id, 'video', '$file_name')";
        mysqli_query($conn, $sql);
        echo "Video de la recogida subido y registrado correctamente.";
    } else {
        echo "Error al subir el video de la recogida.";
    }
}

// Guardar observaciones de la recogida
if (isset($_POST['observaciones_recogida'])) {
    $observaciones = $_POST['observaciones_recogida'];
    $sql = "UPDATE portes SET observaciones_recogida = '$observaciones' WHERE id = $porte_id";
    mysqli_query($conn, $sql);
}

// Registrar hora de llegada o salida
if (isset($_POST['registrar_hora'])) {
    $hora_actual = date('H:i:s');

    if ($_POST['registrar_hora'] == 'llegada') {
        $sql = "UPDATE portes SET hora_llegada_recogida = '$hora_actual' WHERE id = $porte_id";
    } elseif ($_POST['registrar_hora'] == 'salida') {
        $sql = "UPDATE portes SET hora_salida_recogida = '$hora_actual' WHERE id = $porte_id";
    }

    mysqli_query($conn, $sql);
}

echo "Recogida registrada correctamente.";
?>