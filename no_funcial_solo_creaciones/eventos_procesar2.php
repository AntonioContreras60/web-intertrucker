<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    if ($accion == 'registrar_llegada') {
        // Capturar y registrar la llegada con geolocalización y hora
        $porte_id = $_POST['porte_id'];
        $geolocalizacion_llegada = $_POST['geolocalizacion_llegada'];
        $hora_llegada = date('Y-m-d H:i:s', strtotime($_POST['hora_llegada']));  // Formatear a `DATETIME`

        // Insertar o actualizar llegada en la tabla de eventos
        $query = "
            INSERT INTO eventos (porte_id, tipo_evento, geolocalizacion_llegada, hora_llegada)
            VALUES (?, 'recogida', ?, ?)
            ON DUPLICATE KEY UPDATE geolocalizacion_llegada = ?, hora_llegada = ?
        ";

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die("Error al preparar la consulta: " . $conn->error);
        }


        // Enlazar los parámetros y ejecutar
        $stmt->bind_param("issss", $porte_id, $geolocalizacion_llegada, $hora_llegada, $geolocalizacion_llegada, $hora_llegada);

        if ($stmt->execute()) {
            echo "Llegada registrada con éxito.";
        } else {
            echo "Error al registrar la llegada: " . $stmt->error;
        }
        $stmt->close();
    }
        
           <?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Manejador para registrar la salida usando `evento_id`
    if (isset($_POST['registrar_salida'])) {
        $evento_id = $_POST['evento_id'];  // Obtener el ID del evento específico
        $geolocalizacion_salida = $_POST['geolocalizacion_salida'];
        $hora_salida = $_POST['hora_salida'];

        // Verificar que los valores se están recibiendo correctamente
        echo "<h3>Depuración de valores recibidos para registrar salida:</h3>";
        echo "Evento ID: " . htmlspecialchars($evento_id) . "<br>";
        echo "Geolocalización de salida: " . htmlspecialchars($geolocalizacion_salida) . "<br>";
        echo "Hora de salida: " . htmlspecialchars($hora_salida) . "<br>";

        // Validar que los campos no estén vacíos
        if (empty($evento_id) || empty($geolocalizacion_salida) || empty($hora_salida)) {
            echo "<p style='color:red;'>Error: Uno o más campos están vacíos. No se realizará el registro de salida.<br></p>";
            return;
        }

        // Escapar las variables para evitar inyecciones SQL (mejora de seguridad)
        $evento_id = mysqli_real_escape_string($conn, $evento_id);
        $geolocalizacion_salida = mysqli_real_escape_string($conn, $geolocalizacion_salida);
        $hora_salida = mysqli_real_escape_string($conn, $hora_salida);

        // Construir la consulta de actualización con los datos escapados
        $sql_actualizar = "UPDATE eventos 
                           SET geolocalizacion_salida = '$geolocalizacion_salida', 
                               hora_salida = '$hora_salida' 
                           WHERE id = '$evento_id' LIMIT 1";

        // Imprimir la consulta para revisar qué se está ejecutando exactamente
        echo "<h3>Consulta SQL generada:</h3>";
        echo "<pre>$sql_actualizar</pre>";

        // Ejecutar la consulta
        if (mysqli_query($conn, $sql_actualizar)) {
            // Verificar si se afectó alguna fila
            if (mysqli_affected_rows($conn) > 0) {
                echo "<p style='color:green;'>Salida registrada correctamente.</p>";
            } else {
                echo "<p style='color:orange;'>Consulta ejecutada, pero no se actualizó ninguna fila. Verifica el ID del evento.</p>";
            }
        } else {
            echo "<p style='color:red;'>Error al registrar la salida: " . mysqli_error($conn) . "</p>";
        }
    }

    // Manejador para eliminar la salida usando `evento_id`
    elseif (isset($_POST['eliminar_salida'])) {
        $evento_id = $_POST['evento_id'];  // Obtener el ID del evento específico

        // Actualizar el evento existente poniendo los campos de salida en NULL
        $sql_eliminar_salida = "UPDATE eventos SET geolocalizacion_salida = NULL, hora_salida = NULL WHERE id = '$evento_id' LIMIT 1";
        if (mysqli_query($conn, $sql_eliminar_salida)) {
            echo 'Registro de salida eliminado correctamente.';
        } else {
            echo 'Error al eliminar el registro de salida: ' . mysqli_error($conn);
        }
    }
}     
         // Manejador para multimedia
    
    elseif ($accion == 'subir_multimedia') {
        // Captura y subida de fotos y videos
        $evento_id = $_POST['evento_id'];
        $tipo_archivo = $_POST['tipo_archivo'];
        $proposito = $_POST['proposito'];  // Carga o CMR

        // Obtener el nombre y tamaño del archivo
        $nombre_archivo = $_FILES['archivo']['name'];
        $tamano_archivo = $_FILES['archivo']['size'] / 1024;  // Convertir a KB
        $fecha_hora = date('Y-m-d H:i:s');  // Registrar la fecha actual

        // Definir la ruta donde se subirá el archivo
        $directorio_subida = '/uploads/multimedia/';
        $ruta_archivo = $directorio_subida . basename($nombre_archivo);

        // Mover el archivo al servidor
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], __DIR__ . $ruta_archivo)) {
            // Insertar los detalles del archivo en la base de datos
            $sql_multimedia = "
                INSERT INTO multimedia_recogida_entrega (evento_id, nombre_archivo, tipo_archivo, url_archivo, tamano, timestamp, `carga o documento`)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt_multimedia = $conn->prepare($sql_multimedia);
            if ($stmt_multimedia === false) {
                die("Error al preparar la consulta: " . $conn->error);
            }

            $stmt_multimedia->bind_param("isssdss", $porte_id, $nombre_archivo, $tipo_archivo, $ruta_archivo, $tamano_archivo, $fecha_hora, $proposito);

            if ($stmt_multimedia->execute()) {
                echo "Archivo subido y registrado en la base de datos correctamente.";
            } else {
                echo "Error al registrar el archivo en la base de datos: " . $stmt_multimedia->error;
            }

            $stmt_multimedia->close();
        } else {
            echo "Error al subir el archivo al servidor.";
        }
    }

    elseif ($accion == 'guardar_observaciones') {
        // Guardar observaciones y estado de la mercancía
        $evento_id = $_POST['evento_id'];
        $estado_mercancia = $_POST['estado_mercancia'];
        $observaciones = $_POST['observaciones'];
        $fecha_observaciones = date('Y-m-d H:i:s');  // Registrar la fecha actual

        // Actualizar las observaciones y el estado de la mercancía en la tabla de eventos
        $sql_estado = "
            UPDATE eventos 
            SET estado_mercancia = ?, observaciones = ?, fecha_observaciones = ? 
            WHERE porte_id = ? AND tipo_evento = 'recogida'
        ";

        $stmt_estado = $conn->prepare($sql_estado);
        if ($stmt_estado === false) {
            die("Error al preparar la consulta: " . $conn->error);
        }

        $stmt_estado->bind_param("sssi", $estado_mercancia, $observaciones, $fecha_observaciones, $porte_id);

        if ($stmt_estado->execute()) {
            echo "Observaciones y estado de la mercancía actualizados correctamente.";
        } else {
            echo "Error al actualizar el estado y observaciones: " . $stmt_estado->error;
        }

        $stmt_estado->close();
    }

    else {
        echo "Acción no reconocida.";
    }

    // Cerrar la conexión
    mysqli_close($conn);
}
?>
