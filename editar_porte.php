<?php
session_start();
include('conexion.php');

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}

// Procesar solicitudes basadas en los parámetros enviados por AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $porte_id = isset($_POST['porte_id']) ? intval($_POST['porte_id']) : null;
    $tipo_evento = isset($_POST['tipo_evento']) ? $_POST['tipo_evento'] : null;

    // Verificar que se proporcionaron los datos necesarios
    if (!$porte_id || !$tipo_evento) {
        die("Datos insuficientes proporcionados.");
    }

    // Registrar la llegada
    if (isset($_POST['registrar_llegada']) && $_POST['registrar_llegada'] == true) {
        $geolocalizacion_llegada = isset($_POST['geolocalizacion_llegada']) ? $_POST['geolocalizacion_llegada'] : '';
        $hora_llegada = isset($_POST['hora_llegada']) ? $_POST['hora_llegada'] : '';

        // Actualizar la información de llegada en el evento correspondiente
        $sql_llegada = "UPDATE eventos SET geolocalizacion_llegada = ?, hora_llegada = ? WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_llegada = $conn->prepare($sql_llegada);
        $stmt_llegada->bind_param("ssis", $geolocalizacion_llegada, $hora_llegada, $porte_id, $tipo_evento);

        if ($stmt_llegada->execute()) {
            echo "Llegada registrada correctamente.";
        } else {
            echo "Error al registrar la llegada: " . $stmt_llegada->error;
        }
        $stmt_llegada->close();
    }

    // Registrar la salida
    if (isset($_POST['registrar_salida']) && $_POST['registrar_salida'] == true) {
        $geolocalizacion_salida = isset($_POST['geolocalizacion_salida']) ? $_POST['geolocalizacion_salida'] : '';
        $hora_salida = isset($_POST['hora_salida']) ? $_POST['hora_salida'] : '';

        // Actualizar la información de salida en el evento correspondiente
        $sql_salida = "UPDATE eventos SET geolocalizacion_salida = ?, hora_salida = ? WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_salida = $conn->prepare($sql_salida);
        $stmt_salida->bind_param("ssis", $geolocalizacion_salida, $hora_salida, $porte_id, $tipo_evento);

        if ($stmt_salida->execute()) {
            echo "Salida registrada correctamente.";
        } else {
            echo "Error al registrar la salida: " . $stmt_salida->error;
        }
        $stmt_salida->close();
    }

    // Eliminar registro de llegada
    if (isset($_POST['eliminar_llegada']) && $_POST['eliminar_llegada'] == true) {
        // Eliminar la información de llegada
        $sql_eliminar_llegada = "UPDATE eventos SET geolocalizacion_llegada = '', hora_llegada = '' WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_eliminar_llegada = $conn->prepare($sql_eliminar_llegada);
        $stmt_eliminar_llegada->bind_param("is", $porte_id, $tipo_evento);

        if ($stmt_eliminar_llegada->execute()) {
            echo "Registro de llegada eliminado correctamente.";
        } else {
            echo "Error al eliminar el registro de llegada: " . $stmt_eliminar_llegada->error;
        }
        $stmt_eliminar_llegada->close();
    }

    // Eliminar registro de salida
    if (isset($_POST['eliminar_salida']) && $_POST['eliminar_salida'] == true) {
        // Eliminar la información de salida
        $sql_eliminar_salida = "UPDATE eventos SET geolocalizacion_salida = '', hora_salida = '' WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_eliminar_salida = $conn->prepare($sql_eliminar_salida);
        $stmt_eliminar_salida->bind_param("is", $porte_id, $tipo_evento);

        if ($stmt_eliminar_salida->execute()) {
            echo "Registro de salida eliminado correctamente.";
        } else {
            echo "Error al eliminar el registro de salida: " . $stmt_eliminar_salida->error;
        }
        $stmt_eliminar_salida->close();
    }
}

// Cerrar la conexión a la base de datos
mysqli_close($conn);
?>
