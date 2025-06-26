<?php
session_start();

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
} else {
    echo "Conexión a la base de datos exitosa.<br>";
}

// Comprobar que las variables necesarias se envían correctamente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Depuración inicial para ver qué datos están llegando
    echo "<strong>Datos recibidos en POST:</strong><br>";
    echo "Porte ID: " . (isset($_POST['porte_id']) ? $_POST['porte_id'] : "No proporcionado") . "<br>";
    echo "Tipo de Evento: " . (isset($_POST['tipo_evento']) ? $_POST['tipo_evento'] : "No proporcionado") . "<br>";
    echo "Acción: " . (isset($_POST['accion']) ? $_POST['accion'] : "No proporcionado") . "<br>";

    $porte_id = isset($_POST['porte_id']) ? intval($_POST['porte_id']) : null;
    $tipo_evento = isset($_POST['tipo_evento']) ? $_POST['tipo_evento'] : null;

    // Validar que se recibió el porte_id y tipo_evento
    if (!$porte_id || !$tipo_evento) {
        die("Datos insuficientes proporcionados.");
    }

    // Registrar la llegada
    if (isset($_POST['registrar_llegada']) && $_POST['registrar_llegada'] == true) {
        $geolocalizacion_llegada = isset($_POST['geolocalizacion_llegada']) ? $_POST['geolocalizacion_llegada'] : '';
        $hora_llegada = isset($_POST['hora_llegada']) ? $_POST['hora_llegada'] : '';

        // Verificar que se recibieron las variables correctamente
        if (empty($geolocalizacion_llegada) || empty($hora_llegada)) {
            die("Datos de llegada incompletos: geolocalización o hora de llegada no proporcionada.");
        }

        // Consulta SQL para actualizar la llegada
        $sql_llegada = "UPDATE eventos SET geolocalizacion_llegada = ?, hora_llegada = ? WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_llegada = $conn->prepare($sql_llegada);
        if (!$stmt_llegada) {
            die("Error al preparar la consulta de llegada: " . $conn->error);
        }
        $stmt_llegada->bind_param("ssis", $geolocalizacion_llegada, $hora_llegada, $porte_id, $tipo_evento);

        // Ejecutar la consulta y verificar el resultado
        if ($stmt_llegada->execute()) {
            echo "Llegada registrada correctamente.<br>";
        } else {
            echo "Error al registrar la llegada: " . $stmt_llegada->error . "<br>";
        }
        $stmt_llegada->close();
    }

    // Registrar la salida
    if (isset($_POST['registrar_salida']) && $_POST['registrar_salida'] == true) {
        $geolocalizacion_salida = isset($_POST['geolocalizacion_salida']) ? $_POST['geolocalizacion_salida'] : '';
        $hora_salida = isset($_POST['hora_salida']) ? $_POST['hora_salida'] : '';

        // Verificar que los datos no estén vacíos
        if (!empty($geolocalizacion_salida) && !empty($hora_salida)) {
            // Consulta SQL para actualizar la salida
            $sql_salida = "UPDATE eventos SET geolocalizacion_salida = ?, hora_salida = ? WHERE porte_id = ? AND tipo_evento = ?";
            $stmt_salida = $conn->prepare($sql_salida);
            if (!$stmt_salida) {
                die("Error al preparar la consulta de salida: " . $conn->error);
            }
            $stmt_salida->bind_param("ssis", $geolocalizacion_salida, $hora_salida, $porte_id, $tipo_evento);

            if ($stmt_salida->execute()) {
                echo "Salida registrada correctamente.<br>";

                // Actualizar el estado en la tabla portes dependiendo del tipo de evento
                $estado_recogida_entrega = '';
                if ($tipo_evento === 'recogida') {
                    $estado_recogida_entrega = 'Recogido';
                } elseif ($tipo_evento === 'entrega') {
                    $estado_recogida_entrega = 'Entregado';
                }

                // Solo actualizar el estado si el tipo de evento es recogida o entrega
                if (!empty($estado_recogida_entrega)) {
                    $sql_update_estado = "UPDATE portes SET estado_recogida_entrega = ? WHERE id = ?";
                    $stmt_estado = $conn->prepare($sql_update_estado);
                    if (!$stmt_estado) {
                        die("Error al preparar la consulta de actualización de estado: " . $conn->error);
                    }
                    $stmt_estado->bind_param("si", $estado_recogida_entrega, $porte_id);

                    if ($stmt_estado->execute()) {
                        echo "Estado actualizado correctamente a: " . $estado_recogida_entrega . "<br>";
                    } else {
                        echo "Error al actualizar el estado: " . $stmt_estado->error . "<br>";
                    }
                    $stmt_estado->close();
                }

            } else {
                echo "Error al registrar la salida: " . $stmt_salida->error . "<br>";
            }
            $stmt_salida->close();
        } else {
            echo "Datos incompletos para registrar la salida.<br>";
        }
    }

    // Eliminar registro de llegada
    if (isset($_POST['eliminar_llegada']) && $_POST['eliminar_llegada'] == true) {
        $sql_eliminar_llegada = "UPDATE eventos SET geolocalizacion_llegada = '', hora_llegada = '' WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_eliminar_llegada = $conn->prepare($sql_eliminar_llegada);
        if (!$stmt_eliminar_llegada) {
            die("Error al preparar la consulta de eliminación de llegada: " . $conn->error);
        }
        $stmt_eliminar_llegada->bind_param("is", $porte_id, $tipo_evento);

        if ($stmt_eliminar_llegada->execute()) {
            echo "Registro de llegada eliminado correctamente.<br>";
        } else {
            echo "Error al eliminar el registro de llegada: " . $stmt_eliminar_llegada->error . "<br>";
        }
        $stmt_eliminar_llegada->close();
    }

    // Eliminar registro de salida
    if (isset($_POST['eliminar_salida']) && $_POST['eliminar_salida'] == true) {
        $sql_eliminar_salida = "UPDATE eventos SET geolocalizacion_salida = '', hora_salida = '' WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_eliminar_salida = $conn->prepare($sql_eliminar_salida);
        if (!$stmt_eliminar_salida) {
            die("Error al preparar la consulta de eliminación de salida: " . $conn->error);
        }
        $stmt_eliminar_salida->bind_param("is", $porte_id, $tipo_evento);

        if ($stmt_eliminar_salida->execute()) {
            echo "Registro de salida eliminado correctamente.<br>";
        } else {
            echo "Error al eliminar el registro de salida: " . $stmt_eliminar_salida->error . "<br>";
        }
        $stmt_eliminar_salida->close();
    }
}

// Cerrar la conexión a la base de datos
mysqli_close($conn);
?>
