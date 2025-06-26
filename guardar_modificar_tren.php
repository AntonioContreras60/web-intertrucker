<?php
session_start();
include 'conexion.php'; // Incluir conexión a la base de datos
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener los datos del formulario
$cabeza_tractora = isset($_POST['vehiculo_existente']) ? $_POST['vehiculo_existente'] : null;
$semi_remolque = isset($_POST['semi_remolque_existente']) ? $_POST['semi_remolque_existente'] : null;
$remolques = isset($_POST['remolque_existente']) ? $_POST['remolque_existente'] : [];
$camionero_id = isset($_POST['camionero_existente']) ? $_POST['camionero_existente'] : null;
$tren_id_anterior = isset($_POST['tren_id_anterior']) ? $_POST['tren_id_anterior'] : null;

// Verificar si 'tren_id_anterior' está definido
if (!$tren_id_anterior) {
    echo "No se ha proporcionado un ID de tren anterior.";
    exit();
}

// Inicializar el array de vehículos
$vehiculos = [];
if (!empty($cabeza_tractora)) {
    $vehiculos[] = $cabeza_tractora;
}
if (!empty($semi_remolque)) {
    $vehiculos[] = $semi_remolque;
}
if (!empty($remolques)) {
    $vehiculos = array_merge($vehiculos, $remolques);
}

// Eliminar vehículos duplicados y validar
$vehiculos = array_unique(array_filter($vehiculos, function($vehiculo_id) {
    return $vehiculo_id !== null && $vehiculo_id != 0;
}));

// 1. Establecer la fecha de fin para los vehículos del tren anterior
$sql_actualizar_vehiculos_viejos = "UPDATE tren_vehiculos SET fin_vehiculo_tren = NOW() WHERE tren_id = ? AND fin_vehiculo_tren IS NULL";
$stmt_actualizar_vehiculos_viejos = $conn->prepare($sql_actualizar_vehiculos_viejos);
$stmt_actualizar_vehiculos_viejos->bind_param("i", $tren_id_anterior);
$stmt_actualizar_vehiculos_viejos->execute();

// 2. Finalizar la relación previa en tren_camionero para el tren anterior
$sql_finalizar_camionero_anterior = "UPDATE tren_camionero SET fin_tren_camionero = NOW() WHERE tren_id = ? AND fin_tren_camionero IS NULL";
$stmt_finalizar_camionero_anterior = $conn->prepare($sql_finalizar_camionero_anterior);
$stmt_finalizar_camionero_anterior->bind_param("i", $tren_id_anterior);
$stmt_finalizar_camionero_anterior->execute();

// Si se selecciona un tren existente
if (!empty($tren_existente)) {
    // Actualizar los portes para que usen el tren existente
    $sql_actualizar_portes = "UPDATE portes SET tren_id = ? WHERE tren_id = ? AND estado_recogida_entrega != 'entregado'";
    $stmt_actualizar_portes = $conn->prepare($sql_actualizar_portes);
    $stmt_actualizar_portes->bind_param("ii", $tren_existente, $tren_id_anterior);
    $stmt_actualizar_portes->execute();

    // Crear una nueva relación en tren_camionero para el tren existente con el camionero actual
    $sql_insertar_tren_camionero = "INSERT INTO tren_camionero (tren_id, camionero_id, inicio_tren_camionero) VALUES (?, ?, NOW())";
    $stmt_insertar_tren_camionero = $conn->prepare($sql_insertar_tren_camionero);
    $stmt_insertar_tren_camionero->bind_param("ii", $tren_existente, $camionero_id);
    $stmt_insertar_tren_camionero->execute();

    echo "<h3>Modificación guardada correctamente con el tren existente.</h3>";
} else {
    // Si no se selecciona un tren existente, crear un nuevo tren
    $nombre_tren_nuevo = generarNombreTren($vehiculos); // Función para generar el nombre del tren

    // Insertar nuevo tren en la tabla 'tren'
    $sql_insertar_tren = "INSERT INTO tren (tren_nombre) VALUES (?)";
    $stmt_insertar_tren = $conn->prepare($sql_insertar_tren);
    $stmt_insertar_tren->bind_param("s", $nombre_tren_nuevo);
    $stmt_insertar_tren->execute();
    $nuevo_tren_id = $conn->insert_id;

    // Insertar los vehículos seleccionados en la tabla 'tren_vehiculos' y actualizar fechas
    foreach ($vehiculos as $vehiculo_id) {
        $sql_insertar_tren_vehiculo = "
            INSERT INTO tren_vehiculos (tren_id, vehiculo_id, inicio_vehiculo_tren)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE inicio_vehiculo_tren = NOW(), fin_vehiculo_tren = NULL";
        $stmt_insertar_tren_vehiculo = $conn->prepare($sql_insertar_tren_vehiculo);
        $stmt_insertar_tren_vehiculo->bind_param("ii", $nuevo_tren_id, $vehiculo_id);
        $stmt_insertar_tren_vehiculo->execute();
    }

    // Crear una nueva relación en tren_camionero para el nuevo tren con el camionero actual
    $sql_insertar_tren_camionero = "INSERT INTO tren_camionero (tren_id, camionero_id, inicio_tren_camionero) VALUES (?, ?, NOW())";
    $stmt_insertar_tren_camionero = $conn->prepare($sql_insertar_tren_camionero);
    $stmt_insertar_tren_camionero->bind_param("ii", $nuevo_tren_id, $camionero_id);
    $stmt_insertar_tren_camionero->execute();

    // Actualizar los portes para que usen el nuevo tren
    $sql_actualizar_portes = "UPDATE portes SET tren_id = ? WHERE tren_id = ? AND estado_recogida_entrega != 'entregado'";
    $stmt_actualizar_portes = $conn->prepare($sql_actualizar_portes);
    $stmt_actualizar_portes->bind_param("ii", $nuevo_tren_id, $tren_id_anterior);
    $stmt_actualizar_portes->execute();

    echo "<h3>Nuevo tren creado y modificaciones guardadas correctamente.</h3>";
}

// Botón para volver a la página de Portes
echo '<br><a href="portes_trucks.php" class="btn btn-primary">Volver a Portes</a>';

// Función para generar el nombre del tren automáticamente
function generarNombreTren($vehiculos) {
    global $conn;
    $nombre_tren = '';
    foreach ($vehiculos as $vehiculo_id) {
        $sql_vehiculo = "SELECT matricula, marca FROM vehiculos WHERE id = ?";
        $stmt_vehiculo = $conn->prepare($sql_vehiculo);
        $stmt_vehiculo->bind_param("i", $vehiculo_id);
        $stmt_vehiculo->execute();
        $result = $stmt_vehiculo->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($nombre_tren)) {
                $nombre_tren .= ' - ';
            }
            $nombre_tren .= $row['marca'] . ' ' . $row['matricula'];
        }
    }
    return $nombre_tren;
}
?>
