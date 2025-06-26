<?php
session_start();
include 'conexion.php';

// Mostrar errores (para depuración; quita en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Recibir $porte_id
$porte_id = isset($_POST['porte_id']) ? (int)$_POST['porte_id'] : 0;
if ($porte_id <= 0) {
    die("Error: porte_id inválido o ausente.");
}

// Subida de fotos de entrega
if (isset($_FILES['foto_entrega'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES['foto_entrega']['name']);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES['foto_entrega']['tmp_name'], $target_file)) {
        // Usar prepared statement en lugar de concatenación
        $sql = "INSERT INTO archivos_entrega_recogida (porte_id, tipo, archivo_nombre)
                VALUES (?, 'foto', ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Error al preparar inserción de foto: " . $conn->error);
        }
        $stmt->bind_param("is", $porte_id, $file_name);
        $stmt->execute();
        $stmt->close();

        echo "Foto de la entrega subida y registrada correctamente.";
    } else {
        echo "Error al subir la foto de la entrega.";
    }
}

// Subida de videos de entrega
if (isset($_FILES['video_entrega'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES['video_entrega']['name']);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES['video_entrega']['tmp_name'], $target_file)) {
        $sql = "INSERT INTO archivos_entrega_recogida (porte_id, tipo, archivo_nombre)
                VALUES (?, 'video', ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Error al preparar inserción de video: " . $conn->error);
        }
        $stmt->bind_param("is", $porte_id, $file_name);
        $stmt->execute();
        $stmt->close();

        echo "Video de la entrega subido y registrado correctamente.";
    } else {
        echo "Error al subir el video de la entrega.";
    }
}

// Guardar observaciones de la entrega
if (isset($_POST['observaciones_entrega'])) {
    $observaciones = $_POST['observaciones_entrega'];

    $sql = "UPDATE portes SET observaciones_entrega = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error al preparar actualización de observaciones: " . $conn->error);
    }
    $stmt->bind_param("si", $observaciones, $porte_id);
    $stmt->execute();
    $stmt->close();
}

// Registrar hora de llegada o salida
if (isset($_POST['registrar_hora'])) {
    $hora_actual = date('H:i:s');

    if ($_POST['registrar_hora'] == 'llegada') {
        $sql = "UPDATE portes SET hora_llegada_entrega = ? WHERE id = ?";
    } elseif ($_POST['registrar_hora'] == 'salida') {
        $sql = "UPDATE portes SET hora_salida_entrega = ? WHERE id = ?";
    } else {
        $sql = null;
    }

    if ($sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Error al preparar actualización de hora: " . $conn->error);
        }
        $stmt->bind_param("si", $hora_actual, $porte_id);
        $stmt->execute();
        $stmt->close();
    }
}

echo "Entrega registrada correctamente.";
?>
