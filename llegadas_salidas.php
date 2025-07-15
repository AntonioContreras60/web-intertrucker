<?php
session_start();
// Mostrar errores para depuración (debe desactivarse en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

// Ajustar la zona horaria a la de España (o la que corresponda)
date_default_timezone_set('Europe/Madrid');

// Obtener el ID del usuario desde la sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Usuario no autenticado.");
}
$usuario_id = $_SESSION['usuario_id'];

// Obtener el ID del porte desde la URL
$porte_id = isset($_GET['porte_id']) ? intval($_GET['porte_id']) : null;
$tipo_evento = 'recogida'; // Forzar siempre el tipo de evento como 'recogida'

if (!$porte_id) {
    die("ID de porte no proporcionado.");
}

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}

// Información básica del porte
$porte_info = [];

// Consulta para obtener la información del porte específico
$sql_porte = "
    SELECT p.id AS porte_id, p.mercancia_descripcion AS mercancia, p.cantidad, 
           p.localizacion_recogida AS origen, p.localizacion_entrega AS destino, 
           p.fecha_recogida, p.fecha_entrega, v.matricula, v.marca, v.modelo, 
           c.nombre AS nombre_camionero, c.apellidos AS apellidos_camionero
    FROM tren_vehiculos tv
    JOIN portes p ON tv.porte_id = p.id
    JOIN vehiculos v ON tv.vehiculo_id = v.id
    JOIN tren_camionero tc ON tc.tren_id = tv.tren_id
    JOIN camioneros c ON tc.camionero_id = c.id
    WHERE tc.camionero_id = ? AND p.id = ?
";

$stmt_porte = $conn->prepare($sql_porte);
$stmt_porte->bind_param("ii", $usuario_id, $porte_id); // Reemplaza $usuario_id por el $camionero_id si es necesario
$stmt_porte->execute();
$result_porte = $stmt_porte->get_result();

if ($result_porte && $result_porte->num_rows > 0) {
    $porte_info = $result_porte->fetch_assoc();
} else {
    echo "<p>No se encontró información del porte.</p>";
}


// Consulta para verificar si ya existe un evento de tipo 'recogida'
$sql_evento = "SELECT id FROM eventos WHERE porte_id = ? AND tipo_evento = ? LIMIT 1";
$stmt_evento = $conn->prepare($sql_evento);
$stmt_evento->bind_param("is", $porte_id, $tipo_evento);
$stmt_evento->execute();
$result_evento = $stmt_evento->get_result();

if ($result_evento && $result_evento->num_rows > 0) {
    // Evento de recogida ya existe, no hacemos nada adicional
} else {
    // Si no existe el evento de tipo 'recogida', crearlo.
    $sql_insert_evento = "INSERT INTO eventos (porte_id, tipo_evento, estado_mercancia, observaciones, hora_llegada) 
                          VALUES (?, ?, '', '', NULL)";  // Aquí, NULL en lugar de NOW()
    $stmt_insert = $conn->prepare($sql_insert_evento);
    $stmt_insert->bind_param("is", $porte_id, $tipo_evento);
    $stmt_insert->execute();
    $stmt_insert->close();
}
// Ejemplo de hora guardada en UTC (recuperada de la base de datos)
$hora_guardada_utc = '2024-10-12 12:00:00'; // Esto sería el valor que obtienes desde la base de datos

// Crear un objeto DateTime con la hora en UTC
$datetime = new DateTime($hora_guardada_utc, new DateTimeZone('UTC'));

// Convertir la hora a la zona horaria del usuario (en este caso, "Europe/Madrid")
$datetime->setTimezone(new DateTimeZone('Europe/Madrid')); 

// Mostrar la hora en la zona horaria local del usuario
echo $datetime->format('Y-m-d H:i:s');  // Ejemplo de salida: 2024-10-12 14:00:00


// Consulta para obtener la información de llegada
$sql_llegada = "SELECT geolocalizacion_llegada, hora_llegada FROM eventos WHERE porte_id = ? AND tipo_evento = ?";
$stmt_llegada = $conn->prepare($sql_llegada);
$stmt_llegada->bind_param("is", $porte_id, $tipo_evento);
$stmt_llegada->execute();
$result_llegada = $stmt_llegada->get_result();

$geolocalizacion_llegada = "";
$hora_llegada = "";

// Si se encontró un evento de llegada, asignar valores
if ($result_llegada && $result_llegada->num_rows > 0) {
    $llegada_info = $result_llegada->fetch_assoc();
    $geolocalizacion_llegada = $llegada_info['geolocalizacion_llegada'];

    // Convertir la hora de UTC a la hora local del usuario
    if (!empty($llegada_info['hora_llegada'])) {
        $datetime = new DateTime($llegada_info['hora_llegada'], new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone('Europe/Madrid')); // Cambiar por la zona horaria adecuada
        $hora_llegada = $datetime->format('d/m/Y, H:i:s');
    } else {
        $hora_llegada = "Pendiente";
    }
}
$stmt_llegada->close();


// Consulta para obtener la información de salida
$sql_salida = "SELECT geolocalizacion_salida, hora_salida FROM eventos WHERE porte_id = ? AND tipo_evento = ?";
$stmt_salida = $conn->prepare($sql_salida);
$stmt_salida->bind_param("is", $porte_id, $tipo_evento);
$stmt_salida->execute();
$result_salida = $stmt_salida->get_result();

$geolocalizacion_salida = "";
$hora_salida = "";

// Si se encontró un evento de salida, asignar valores
if ($result_salida && $result_salida->num_rows > 0) {
    $salida_info = $result_salida->fetch_assoc();
    $geolocalizacion_salida = $salida_info['geolocalizacion_salida'];

    // Convertir la hora de UTC a la hora local del usuario
    if (!empty($salida_info['hora_salida'])) {
        $datetime = new DateTime($salida_info['hora_salida'], new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone('Europe/Madrid')); // Cambiar por la zona horaria adecuada
        $hora_salida = $datetime->format('d/m/Y, H:i:s');
    } else {
        $hora_salida = "Pendiente"; // Mostrar 'Pendiente' si no hay hora registrada
    }
}
$stmt_salida->close();


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Recogida - Porte ID: <?php echo $porte_id; ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const TIPO_EVENTO = 'recogida'; // Definir tipo de evento como constante

        // Función para registrar la llegada con geolocalización
        function registrarLlegada() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    let latitud = position.coords.latitude;
                    let longitud = position.coords.longitude;
                    let coordenadas = latitud + ", " + longitud;
                    let fechaHoraActual = new Date().toISOString().slice(0, 19).replace('T', ' '); // Formato "YYYY-MM-DD HH:MM:SS"

                    // Enviar los datos al servidor
                    $.ajax({
                        url: 'eventos_procesar.php',
                        type: 'POST',
                        data: {
                            registrar_llegada: true,
                            porte_id: <?php echo json_encode($porte_id); ?>,
                            tipo_evento: TIPO_EVENTO,
                            geolocalizacion_llegada: coordenadas,
                            hora_llegada: fechaHoraActual
                        },
                        success: function(response) {
                            alert("Llegada registrada correctamente.");
                            document.getElementById("geolocalizacion_llegada").value = coordenadas;
                            document.getElementById("hora_llegada").value = fechaHoraActual;
                        },
                        error: function(xhr) {
                            alert("Error al registrar la llegada: " + xhr.responseText);
                        }
                    });
                }, function (error) {
                    alert("Error al obtener la geolocalización: " + error.message);
                });
            } else {
                alert("La geolocalización no es soportada por este navegador.");
            }
        }

        // Función para registrar la salida con geolocalización
        function registrarSalida() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    let latitud = position.coords.latitude;
                    let longitud = position.coords.longitude;
                    let coordenadas = latitud + ", " + longitud;
                    let fechaHoraActual = new Date().toISOString().slice(0, 19).replace('T', ' '); // Mismo formato que en registrarLlegada

                    // Enviar datos al servidor
                    $.ajax({
                        url: 'eventos_procesar.php',
                        type: 'POST',
                        data: {
                            registrar_salida: true,
                            porte_id: <?php echo json_encode($porte_id); ?>,
                            tipo_evento: TIPO_EVENTO, // Asegurar consistencia en tipo_evento
                            geolocalizacion_salida: coordenadas,
                            hora_salida: fechaHoraActual
                        },
                        success: function(response) {
                            alert("Salida registrada correctamente.");
                            document.getElementById("geolocalizacion_salida").value = coordenadas;
                            document.getElementById("hora_salida").value = fechaHoraActual;
                        },
                        error: function(xhr) {
                            alert("Error al registrar la salida: " + xhr.responseText);
                        }
                    });
                }, function (error) {
                    alert("Error al obtener la geolocalización: " + error.message);
                });
            } else {
                alert("La geolocalización no es soportada por este navegador.");
            }
        }

        // Función para eliminar la llegada
        function eliminarLlegada() {
            if (confirm("¿Estás seguro de que deseas eliminar este registro?")) {
                $.ajax({
                    url: 'eventos_procesar.php',
                    type: 'POST',
                    data: {
                        eliminar_llegada: true,
                        porte_id: <?php echo json_encode($porte_id); ?>,
                        tipo_evento: TIPO_EVENTO
                    },
                    success: function(response) {
                        alert("Registro de llegada eliminado.");
                        document.getElementById("geolocalizacion_llegada").value = "";
                        document.getElementById("hora_llegada").value = "";
                    },
                    error: function(xhr) {
                        alert("Error al eliminar el registro: " + xhr.responseText);
                    }
                });
            }
        }

        // Función para eliminar la salida
        function eliminarSalida() {
            if (confirm("¿Estás seguro de que deseas eliminar el registro de salida?")) {
                $.ajax({
                    url: 'eventos_procesar.php',
                    type: 'POST',
                    data: {
                        eliminar_salida: true,
                        porte_id: <?php echo json_encode($porte_id); ?>,
                        tipo_evento: TIPO_EVENTO
                    },
                    success: function(response) {
                        alert("Salida eliminada correctamente.");
                        document.getElementById("geolocalizacion_salida").value = "";
                        document.getElementById("hora_salida").value = "";
                    },
                    error: function(xhr) {
                        alert("Error al eliminar la salida: " + xhr.responseText);
                    }
                });
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Gestión de Recogida - Porte ID: <?php echo $porte_id; ?></h1>
        <?php if ($porte_info): ?>
            <p><strong>Mercancía:</strong> <?php echo $porte_info['mercancia']; ?></p>
            <p><strong>Nº Palets:</strong> <?php echo $porte_info['cantidad']; ?></p>
            <p><strong>Origen:</strong> <?php echo $porte_info['origen']; ?></p>
            <p><strong>Destino:</strong> <?php echo $porte_info['destino']; ?></p>
            <p><strong>Fecha de Recogida:</strong> <?php echo $porte_info['fecha_recogida']; ?></p>
            <p><strong>Fecha de Entrega:</strong> <?php echo $porte_info['fecha_entrega']; ?></p>
        <?php endif; ?>

        <h2>Registrar Llegada</h2>
        <label>Geolocalización de Llegada:</label>
        <input type="text" id="geolocalizacion_llegada" value="<?php echo $geolocalizacion_llegada; ?>" readonly><br><br>
        <label>Hora de Llegada:</label>
        <input type="text" id="hora_llegada" value="<?php echo $hora_llegada; ?>" readonly><br><br>
        <button type="button" onclick="registrarLlegada()">Capturar y Registrar Llegada</button><br><br>
        
        <h2>Eliminar Llegada</h2>
        <button type="button" onclick="eliminarLlegada()">Eliminar Registro de Llegada</button>

        
         <h2>Registrar Salida</h2>
        <label>Geolocalización de Salida:</label>
        <input type="text" id="geolocalizacion_salida" value="<?php echo htmlspecialchars($geolocalizacion_salida); ?>" readonly><br><br>
        <label>Hora de Salida:</label>
        <input type="text" id="hora_salida" value="<?php echo htmlspecialchars($hora_salida); ?>" readonly><br><br>
        <button type="button" onclick="registrarSalida()">Capturar y Registrar Salida</button><br><br>

        <h2>Eliminar Salida</h2>
        <button type="button" onclick="eliminarSalida()">Eliminar Registro de Salida</button>
    </div>
    <br>
</body>
</html>