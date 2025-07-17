<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(["administrador","gestor"]);
include 'conexion.php'; // Conexión a la base de datos
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener el ID del usuario actual
$usuario_id = $_SESSION['usuario_id'];

// Verificar que los datos fueron enviados correctamente desde el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vehiculo_id = isset($_POST['vehiculo_existente']) ? intval($_POST['vehiculo_existente']) : null;
    $semi_remolque_id = isset($_POST['semi_remolque_existente']) ? intval($_POST['semi_remolque_existente']) : null;
    $remolques = isset($_POST['remolque_existente']) ? $_POST['remolque_existente'] : [];
    $camionero_id = isset($_POST['camionero_existente']) ? intval($_POST['camionero_existente']) : null;
    $tren_existente_id = isset($_POST['tren_existente']) ? intval($_POST['tren_existente']) : null;

    $inicio_tren = date("Y-m-d H:i:s"); // Fecha y hora actuales

    // Si el tren ya existe
    if (!empty($tren_existente_id)) {
        // Actualizar el camionero asociado con el tren si es necesario
        actualizarTrenExistente($tren_existente_id, $camionero_id, $conn);

    } elseif ($vehiculo_id && $camionero_id) {
        // Crear o verificar tren
        $vehiculos_combinados = array_merge([$vehiculo_id], [$semi_remolque_id], $remolques);
        $vehiculos_combinados = array_filter($vehiculos_combinados); // Eliminar valores vacíos
        sort($vehiculos_combinados); // Ordenar los vehículos para que el orden no importe

        // Verificar si el tren ya existe
        $tren_id = verificarOcrearTren($vehiculos_combinados, $conn);

        if ($tren_id) {
            // Asignar camionero al tren
            $sql_insert_tren_camionero = "INSERT INTO tren_camionero (tren_id, camionero_id, inicio_tren_camionero) VALUES (?, ?, NOW())";
            $stmt_insert_tren_camionero = $conn->prepare($sql_insert_tren_camionero);
            $stmt_insert_tren_camionero->bind_param("ii", $tren_id, $camionero_id);
            $stmt_insert_tren_camionero->execute();
        } else {
            echo "<p style='color:red;'>Error al crear o verificar el tren.</p>";
        }
    } else {
        // Error por datos faltantes
        echo "<p style='color:red;'>Error: Faltan datos para procesar la solicitud.</p>";
        exit();
    }
} else {
    // Si no se envía por POST, redirigir a una página de error
    header("Location: error.php");
    exit();
}

// Función para verificar si el tren existe o crear uno nuevo
function verificarOcrearTren($vehiculos_combinados, $conn) {
    // Convertir el array de vehículos en una cadena separada por comas para verificar
    $vehiculos_str = implode(',', $vehiculos_combinados);

    // Contar el número de vehículos seleccionados
    $count_vehiculos = count($vehiculos_combinados);

    // Verificar si existe un tren con exactamente estos vehículos
    $sql_check_tren = "
        SELECT tren_id 
        FROM tren_vehiculos 
        WHERE vehiculo_id IN ($vehiculos_str) 
        GROUP BY tren_id 
        HAVING COUNT(DISTINCT vehiculo_id) = ?";
    $stmt_check = $conn->prepare($sql_check_tren);
    $stmt_check->bind_param("i", $count_vehiculos);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // El tren ya existe
        $row = $result_check->fetch_assoc();
        return $row['tren_id'];
    } else {
        // Crear un nuevo tren si no existe
        $tren_nombre = generarNombreTren($vehiculos_combinados, $conn);
        $sql_create_tren = "INSERT INTO tren (tren_nombre) VALUES (?)";
        $stmt_create_tren = $conn->prepare($sql_create_tren);
        $stmt_create_tren->bind_param("s", $tren_nombre);
        
        if ($stmt_create_tren->execute()) {
            $nuevo_tren_id = $conn->insert_id;

            // Insertar los vehículos en la tabla tren_vehiculos
            foreach ($vehiculos_combinados as $vehiculo_id) {
                $sql_insert_vehiculo = "INSERT INTO tren_vehiculos (tren_id, vehiculo_id) VALUES (?, ?)";
                $stmt_insert_vehiculo = $conn->prepare($sql_insert_vehiculo);
                $stmt_insert_vehiculo->bind_param("ii", $nuevo_tren_id, $vehiculo_id);
                $stmt_insert_vehiculo->execute();
            }

            return $nuevo_tren_id; // Retornar el ID del nuevo tren
        } else {
            return false; // Error al crear el tren
        }
    }
}

// Función para generar el nombre del tren
function generarNombreTren($vehiculos_combinados, $conn) {
    $nombres_vehiculos = [];

    foreach ($vehiculos_combinados as $vehiculo_id) {
        // Obtener la matrícula y la marca de cada vehículo
        $sql_vehiculo = "SELECT matricula, marca FROM vehiculos WHERE id = ?";
        $stmt_vehiculo = $conn->prepare($sql_vehiculo);
        $stmt_vehiculo->bind_param("i", $vehiculo_id);
        $stmt_vehiculo->execute();
        $result_vehiculo = $stmt_vehiculo->get_result();
        if ($result_vehiculo->num_rows > 0) {
            $vehiculo = $result_vehiculo->fetch_assoc();
            $nombres_vehiculos[] = $vehiculo['marca'] . " " . $vehiculo['matricula'];
        }
    }

    // Generar un nombre de tren con las marcas y matrículas
    return implode(' - ', $nombres_vehiculos);
}

// Función para actualizar el camionero de un tren existente
function actualizarTrenExistente($tren_id, $nuevo_camionero_id, $conn) {
    // Verificar si el tren tiene un camionero asignado y finalizar su asignación
    $sql_finalizar_camionero = "UPDATE tren_camionero SET fin_tren_camionero = NOW() WHERE tren_id = ? AND fin_tren_camionero IS NULL";
    $stmt_finalizar_camionero = $conn->prepare($sql_finalizar_camionero);
    $stmt_finalizar_camionero->bind_param("i", $tren_id);
    $stmt_finalizar_camionero->execute();

    // Asignar el nuevo camionero
    $sql_asignar_camionero = "INSERT INTO tren_camionero (tren_id, camionero_id, inicio_tren_camionero) VALUES (?, ?, NOW())";
    $stmt_asignar_camionero = $conn->prepare($sql_asignar_camionero);
    $stmt_asignar_camionero->bind_param("ii", $tren_id, $nuevo_camionero_id);
    $stmt_asignar_camionero->execute();
}
?>

// Mensaje de éxito
echo "<div style='text-align: center; margin-top: 20px;'>
        <h2 style='color: green;'>La asociación del tren se guardó con éxito.</h2>
        <a href='gestionar_tren_camionero.php' style='display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; font-size: 16px;'>Volver a Inicio</a>
      </div>";
