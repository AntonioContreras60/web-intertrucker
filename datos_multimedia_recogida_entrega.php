<?php
include('conexion.php'); // Incluir la conexión a la base de datos

// Verificar que se han recibido los datos necesarios
if (isset($_POST['evento_id'], $_POST['tipo_archivo'], $_POST['url_archivo'], $_POST['tamano'])) {
    $evento_id = $_POST['evento_id']; 
    $tipo_archivo = $_POST['tipo_archivo'];
    $url_archivo = $_POST['url_archivo'];
    $tamano = $_POST['tamano'];
    $geolocalizacion = isset($_POST['geolocalizacion']) ? $_POST['geolocalizacion'] : NULL;

    // Consulta para insertar los valores en la tabla multimedia_recogida_entrega
    $sql_insertar = "INSERT INTO multimedia_recogida_entrega (evento_id, tipo_archivo, url_archivo, geolocalizacion, tamano, timestamp)
                     VALUES ('$evento_id', '$tipo_archivo', '$url_archivo', '$geolocalizacion', '$tamano', NOW())";

    // Ejecutar la consulta
    if (mysqli_query($conn, $sql_insertar)) {
        echo json_encode(array("status" => "success", "message" => "Archivo registrado correctamente en la base de datos."));
    } else {
        echo json_encode(array("status" => "error", "message" => "Error al insertar en la base de datos: " . mysqli_error($conn)));
    }
} else {
    echo json_encode(array("status" => "error", "message" => "Datos incompletos."));
}

// Cerrar la conexión
mysqli_close($conn);
?>
<?php
include('conexion.php'); // Incluir la conexión a la base de datos

// Definir las variables necesarias para la inserción
$evento_id = $_POST['evento_id']; // Asegúrate de que este valor sea pasado desde el formulario
$tipo_archivo = $_POST['tipo_archivo']; // Foto o Video
$url_archivo = $_POST['url_archivo']; // Ruta del archivo en el servidor
$geolocalizacion = isset($_POST['geolocalizacion']) ? $_POST['geolocalizacion'] : NULL;
$tamano = $_POST['tamano']; // Tamaño del archivo en bytes
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
