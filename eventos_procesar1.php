<?php
session_start();
include('conexion.php');

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}

// Comprobar que las variables necesarias se envían correctamente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $porte_id = isset($_POST['porte_id']) ? intval($_POST['porte_id']) : null;
    $tipo_evento = isset($_POST['tipo_evento']) ? $_POST['tipo_evento'] : null;

    // Validar que se recibió el porte_id y tipo_evento
    if (!$porte_id || !$tipo_evento) {
        die("Datos insuficientes proporcionados.");
    }

    // Variable para el resultado de la operación
    $resultado = false;

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
        $stmt_llegada->bind_param("ssis", $geolocalizacion_llegada, $hora_llegada, $porte_id, $tipo_evento);

        // Ejecutar la consulta y verificar el resultado
        if ($stmt_llegada->execute()) {
            $resultado = true;
            echo "Llegada registrada correctamente.";
        } else {
            echo "Error al registrar la llegada: " . $stmt_llegada->error;
        }
        $stmt_llegada->close();
    }

if (isset($_POST['registrar_salida']) && $_POST['registrar_salida'] == true) {
    $geolocalizacion_salida = isset($_POST['geolocalizacion_salida']) ? $_POST['geolocalizacion_salida'] : '';
    $hora_salida = isset($_POST['hora_salida']) ? $_POST['hora_salida'] : '';

    // Mostrar los valores recibidos para depurar
    echo "Geolocalización Salida: " . $geolocalizacion_salida . " - Hora Salida: " . $hora_salida;

    // Verificar que los datos no estén vacíos
    if (!empty($geolocalizacion_salida) && !empty($hora_salida)) {
        // Consulta SQL para actualizar la salida
        $sql_salida = "UPDATE eventos SET geolocalizacion_salida = ?, hora_salida = ? WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_salida = $conn->prepare($sql_salida);
        $stmt_salida->bind_param("ssis", $geolocalizacion_salida, $hora_salida, $porte_id, $tipo_evento);

        if ($stmt_salida->execute()) {
            echo "Salida registrada correctamente.";
        } else {
            echo "Error al registrar la salida: " . $stmt_salida->error;
        }
        $stmt_salida->close();
    } else {
        echo "Datos incompletos para registrar la salida.";
    }
}


    // Eliminar registro de llegada
    if (isset($_POST['eliminar_llegada']) && $_POST['eliminar_llegada'] == true) {
        $sql_eliminar_llegada = "UPDATE eventos SET geolocalizacion_llegada = '', hora_llegada = '' WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_eliminar_llegada = $conn->prepare($sql_eliminar_llegada);
        $stmt_eliminar_llegada->bind_param("is", $porte_id, $tipo_evento);

        if ($stmt_eliminar_llegada->execute()) {
            $resultado = true;
            echo "Registro de llegada eliminado correctamente.";
        } else {
            echo "Error al eliminar el registro de llegada: " . $stmt_eliminar_llegada->error;
        }
        $stmt_eliminar_llegada->close();
    }

    // Eliminar registro de salida
    if (isset($_POST['eliminar_salida']) && $_POST['eliminar_salida'] == true) {
        $sql_eliminar_salida = "UPDATE eventos SET geolocalizacion_salida = '', hora_salida = '' WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_eliminar_salida = $conn->prepare($sql_eliminar_salida);
        $stmt_eliminar_salida->bind_param("is", $porte_id, $tipo_evento);

        if ($stmt_eliminar_salida->execute()) {
            $resultado = true;
            echo "Registro de salida eliminado correctamente.";
        } else {
            echo "Error al eliminar el registro de salida: " . $stmt_eliminar_salida->error;
        }
        $stmt_eliminar_salida->close();
    }

    if (!$resultado) {
        echo "No se realizó ninguna operación.";
    }
} else {
    echo "Método no permitido";
}

// Cerrar la conexión a la base de datos
mysqli_close($conn);
?>
<?php
session_start();
include('conexion.php');

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}

// Comprobar el método POST y que las variables existen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $porte_id = isset($_POST['porte_id']) ? intval($_POST['porte_id']) : null;
    $tipo_evento = isset($_POST['tipo_evento']) ? $_POST['tipo_evento'] : null;

    // Registrar llegada
    if (isset($_POST['registrar_llegada'])) {
        echo "Registrando llegada..."; // Mensaje de prueba
        // Aquí procesar la llegada
    }

    // Registrar salida
    if (isset($_POST['registrar_salida'])) {
        echo "Registrando salida..."; // Mensaje de prueba
        // Aquí procesar la salida
    }

    // Eliminar llegada
    if (isset($_POST['eliminar_llegada'])) {
        echo "Eliminando llegada..."; // Mensaje de prueba
        // Aquí procesar la eliminación de llegada
    }

    // Eliminar salida
    if (isset($_POST['eliminar_salida'])) {
        echo "Eliminando salida..."; // Mensaje de prueba
        // Aquí procesar la eliminación de salida
    }
} else {
    echo "Método no permitido";
}
?>
