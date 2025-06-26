<?php
include('conexion.php'); // Incluir la conexión a la base de datos

// Definir las variables necesarias para la inserción
$evento_id = 6; // ID del evento que existe en la tabla `eventos`
$tipo_archivo = "Foto"; // Definir si es Foto o Video
$url_archivo = "uploads/multimedia/prueba.jpg"; // Ruta de prueba para el archivo en el servidor
$geolocalizacion = NULL; // Geolocalización opcional
$tamano = 2048; // Tamaño del archivo en bytes
$timestamp = date("Y-m-d H:i:s"); // Timestamp actual

// Consulta para insertar los valores en la tabla multimedia_recogida_entrega
$sql_insertar = "INSERT INTO multimedia_recogida_entrega (evento_id, tipo_archivo, url_archivo, geolocalizacion, tamano, timestamp)
                 VALUES ('$evento_id', '$tipo_archivo', '$url_archivo', '$geolocalizacion', '$tamano', '$timestamp')";

// Ejecutar la consulta
if (mysqli_query($conn, $sql_insertar)) {
    echo "Registro insertado exitosamente en la tabla multimedia_recogida_entrega.";
} else {
    echo "Error al insertar en la base de datos: " . mysqli_error($conn);
}

// Cerrar la conexión
mysqli_close($conn);
?>
