<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'conexion.php';

    $directorio_subida = '/uploads/multimedia/';

    // Obtener los datos enviados desde el formulario
    $porte_id = $_POST['porte_id'];
    $tipo_evento = $_POST['tipo_evento'];
    $categoria = $_POST['categoria'];
    $geolocalizacion = $_POST['geolocalizacion'] ?? ''; // Geolocalización si está disponible

    // Obtener los archivos subidos
    $archivo_foto = $_FILES['archivo_foto'] ?? null;
    $archivo_video = $_FILES['archivo_video'] ?? null;

    // Verificar si es una foto o un video
    if ($archivo_foto) {
        $archivo = $archivo_foto;
        $tipo_archivo = 'foto';
    } elseif ($archivo_video) {
        $archivo = $archivo_video;
        $tipo_archivo = 'video';
    } else {
        die("No se ha seleccionado ni foto ni video.");
    }

    // Generar nuevo nombre del archivo basado en porte_id, tipo_evento, fecha y hora
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION); // Obtener la extensión original
    $nuevo_nombre_archivo = $porte_id . "_" . $tipo_evento . "_" . date("Ymd_His") . "." . $extension;

    // Definir la ruta completa donde se guardará el archivo
    $ruta_archivo = $directorio_subida . $nuevo_nombre_archivo;

    // Mover el archivo al servidor con el nuevo nombre
    if (move_uploaded_file($archivo['tmp_name'], __DIR__ . $ruta_archivo)) {
        // Insertar los detalles en la base de datos utilizando prepared statements
        $stmt = $conn->prepare(
            "INSERT INTO multimedia_recogida_entrega (
                nombre_archivo,
                tipo_archivo,
                url_archivo,
                geolocalizacion,
                timestamp,
                tamano,
                categoria,
                porte_id,
                tipo_evento
            ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param(
                'ssssisis',
                $nuevo_nombre_archivo,
                $tipo_archivo,
                $ruta_archivo,
                $geolocalizacion,
                $archivo['size'],
                $categoria,
                $porte_id,
                $tipo_evento
            );

            if ($stmt->execute()) {
                echo "El archivo ($tipo_archivo) se ha subido correctamente y se ha registrado.";
            } else {
                echo "Error al registrar los detalles del archivo: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Error al preparar la consulta: " . $conn->error;
        }
    } else {
        echo "Error al subir el archivo.";
    }

    // Cerrar conexión a la base de datos
    mysqli_close($conn);
}
?>
