<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

// Ajustar la zona horaria a la de España
date_default_timezone_set('Europe/Madrid');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener los valores pasados en la URL
$porte_id = isset($_GET['porte_id']) ? intval($_GET['porte_id']) : null;
$tren_id = isset($_GET['tren_id']) ? intval($_GET['tren_id']) : null;
$camionero_id = isset($_GET['camionero_id']) ? intval($_GET['camionero_id']) : null;
$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : null;
$tipo_evento = isset($_GET['tipo_evento']) ? $_GET['tipo_evento'] : null;

// Mostrar los valores obtenidos para verificar
echo "Porte ID: " . $porte_id . "<br>";
echo "Tren ID: " . $tren_id . "<br>";
echo "Camionero ID: " . $camionero_id . "<br>";
echo "Usuario ID: " . $usuario_id . "<br>";
echo "Tipo de evento: " . $tipo_evento . "<br>";

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}

// Consulta SQL para verificar si el usuario es el creador del porte o si el porte está relacionado con el tren asignado
$sql_porte = "
    SELECT p.*
    FROM portes p
    JOIN tren_vehiculos tv ON tv.porte_id = p.id
    WHERE p.id = ? 
    AND (p.usuario_creador_id = ? OR ? IN (
        SELECT tc.camionero_id
        FROM tren_camionero tc
        WHERE tc.tren_id = ? AND tc.camionero_id = ?
    ))
";

// Preparar la consulta
$stmt_porte = $conn->prepare($sql_porte);

// Verificar si la consulta fue preparada correctamente
if (!$stmt_porte) {
    die("Error en la preparación de la consulta SQL: " . $conn->error);
}

// Asignar los parámetros a la consulta preparada
$stmt_porte->bind_param("iiiii", $porte_id, $usuario_id, $camionero_id, $tren_id, $camionero_id);

// Ejecutar la consulta
$stmt_porte->execute();
$result_porte = $stmt_porte->get_result();

// Verificar si el usuario tiene acceso al porte
if ($result_porte->num_rows > 0) {
    echo "Acceso permitido al porte.";
} else {
    die("Acceso no permitido para este porte.");
}


// Verificar si ya existe un evento de tipo "recogida" o "entrega" para este porte
$sql_evento = "SELECT id FROM eventos WHERE porte_id = ? AND tipo_evento = ? LIMIT 1";
$stmt_evento = $conn->prepare($sql_evento);

// Verificar si la consulta fue preparada correctamente
if (!$stmt_evento) {
    die("Error en la preparación de la consulta SQL de eventos: " . $conn->error);
}

// Asignar los parámetros a la consulta preparada
$stmt_evento->bind_param("is", $porte_id, $tipo_evento);
$stmt_evento->execute();
$result_evento = $stmt_evento->get_result();

if ($result_evento->num_rows > 0) {
    // Ya existe un evento de este tipo
    echo "<p>El evento de $tipo_evento ya existe.</p>";
} else {
    // No existe el evento, se puede crear uno nuevo
    echo "<p>Este evento aún no existe. Se puede crear uno nuevo.</p>";
}

$stmt_evento->close();
// Mostrar información del porte
if (!empty($porte_info)) {
    echo "<h3>Detalles del Porte</h3>";
    echo "<p><strong>Mercancía:</strong> " . $porte_info['mercancia'] . "</p>";
    echo "<p><strong>Cantidad:</strong> " . $porte_info['cantidad'] . "</p>";
    echo "<p><strong>Origen:</strong> " . $porte_info['origen'] . "</p>";
    echo "<p><strong>Destino:</strong> " . $porte_info['destino'] . "</p>";
    echo "<p><strong>Fecha de recogida:</strong> " . $porte_info['fecha_recogida'] . "</p>";
    echo "<p><strong>Fecha de entrega:</strong> " . $porte_info['fecha_entrega'] . "</p>";
} else {
    echo "<p>No se encontró información del porte.</p>";
}
// Mostrar formulario para actualizar detalles si es recogida
if ($tipo_evento === 'recogida') {
    echo "<h3>Actualizar detalles de la recogida</h3>";
    echo "<form action='guardar_recogida.php' method='POST'>";
    echo "<label for='hora_recogida'>Hora de Recogida:</label>";
    echo "<input type='time' id='hora_recogida' name='hora_recogida' required>";
    echo "<input type='hidden' name='porte_id' value='$porte_id'>";
    echo "<input type='hidden' name='tipo_evento' value='recogida'>";
    echo "<input type='submit' value='Actualizar'>";
    echo "</form>";
}

?>