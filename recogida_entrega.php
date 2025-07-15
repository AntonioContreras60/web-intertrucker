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

// Depuración: mostrar valores obtenidos
//echo "Porte ID: " . $porte_id . "<br>";
//echo "Tren ID: " . $tren_id . "<br>";
//echo "Camionero ID: " . $camionero_id . "<br>";
//echo "Usuario ID: " . $usuario_id . "<br>";
//echo "Tipo Evento: " . $tipo_evento . "<br>";

// Verificar que todos los datos necesarios están presentes
// Validación de los datos requeridos
if (!$porte_id || !$tren_id || !$camionero_id || !$usuario_id || !$tipo_evento) {
    // Registra el error en un log
    error_log("Error: Datos faltantes. porte_id: $porte_id, tren_id: $tren_id, camionero_id: $camionero_id, usuario_id: $usuario_id, tipo_evento: $tipo_evento");
    // Redirige a una página de error genérica
    header("Location: error.php?error=datos_faltantes");
    exit();
}

// Verificar la conexión a la base de datos
if (!$conn) {
    // Registra el error en un log
    error_log("Error en la conexión a la base de datos: " . mysqli_connect_error());
    // Redirige a una página de error genérica
    header("Location: error.php?error=conexion_bd");
    exit();
}


// Comprobar si el usuario tiene relación con el porte (creador, asignado en oferta o compañero)
$sql_acceso = "
    SELECT p.id
    FROM portes p
    LEFT JOIN seleccionados_oferta so ON so.porte_id = p.id
    LEFT JOIN usuarios u_creador ON u_creador.id = p.usuario_creador_id
    LEFT JOIN usuarios u_asignado ON u_asignado.id = so.usuario_id
    WHERE p.id = ?
      AND (
          u_creador.admin_id = ?
          OR u_asignado.admin_id = ?
      )
";
$stmt_acceso = $conn->prepare($sql_acceso);
$stmt_acceso->bind_param("iii", $porte_id, $_SESSION['admin_id'], $_SESSION['admin_id']);
$stmt_acceso->execute();
$result_acceso = $stmt_acceso->get_result();

if ($result_acceso->num_rows === 0) {
    // Acceso denegado: el usuario no está relacionado con el porte
    header("Location: error.php?error=acceso_no_autorizado");
    exit();
}

// Comprobar si el usuario es el camionero asignado al porte
$sql_modificar = "
    SELECT pt.id 
    FROM porte_tren pt
    INNER JOIN tren_camionero tc ON tc.tren_id = pt.tren_id
    INNER JOIN camioneros c ON c.id = tc.camionero_id
    WHERE pt.porte_id = ? AND c.usuario_id = ?
";
$stmt_modificar = $conn->prepare($sql_modificar);
$stmt_modificar->bind_param("ii", $porte_id, $_SESSION['usuario_id']);
$stmt_modificar->execute();
$result_modificar = $stmt_modificar->get_result();

if ($result_modificar->num_rows === 0) {
    // No es el camionero asignado, redirigir a la página de solo vista
    header("Location: recogida_entrega_vista.php?porte_id=$porte_id&tipo_evento=$tipo_evento");
    exit();
}


// Comprobación simplificada para verificar si el porte está en la tabla 'portes'
$sql_simple_porte = "SELECT * FROM portes WHERE id = ?";
$stmt_simple_porte = $conn->prepare($sql_simple_porte);
$stmt_simple_porte->bind_param("i", $porte_id);
$stmt_simple_porte->execute();
$result_simple_porte = $stmt_simple_porte->get_result();

if ($result_simple_porte && $result_simple_porte->num_rows > 0) {
    $porte_info_simple = $result_simple_porte->fetch_assoc();
    echo "<h2>Información básica del porte:</h2>";
    echo "<strong>Mercancía:</strong> " . $porte_info_simple['mercancia_descripcion'] . "<br>";
    echo "<strong>Tipo de palet:</strong> " . $porte_info_simple['tipo_palet'] . "<br>"; // Añadido tipo de porte
    echo "<strong>Nº Palets:</strong> " . $porte_info_simple['cantidad'] . "<br>";
    echo "<strong>Origen:</strong> " . $porte_info_simple['localizacion_recogida'] . "<br>";
    echo "<strong>Fecha de Recogida:</strong> " . $porte_info_simple['fecha_recogida'] . "<br>";
    echo "<strong>Destino:</strong> " . $porte_info_simple['localizacion_entrega'] . "<br>";
    echo "<strong>Fecha de Entrega:</strong> " . $porte_info_simple['fecha_entrega'] . "<br>";
} else {
    echo "No se encontró información básica del porte.";
}

// Verificación de evento existente
$sql_evento = "SELECT id FROM eventos WHERE porte_id = ? AND tipo_evento = ? LIMIT 1";
$stmt_evento = $conn->prepare($sql_evento);
$stmt_evento->bind_param("is", $porte_id, $tipo_evento);
$stmt_evento->execute();
$result_evento = $stmt_evento->get_result();

if ($result_evento && $result_evento->num_rows > 0) {
    // Evento de recogida ya existe
    //echo "<p>El evento de $tipo_evento ya existe.</p>";
} else {
    // Si no existe el evento de tipo 'recogida', crearlo.
    $sql_insert_evento = "INSERT INTO eventos (porte_id, tipo_evento, estado_mercancia, observaciones, hora_llegada) 
                          VALUES (?, ?, '', '', NULL)";  // NULL en lugar de NOW() si es opcional
    $stmt_insert = $conn->prepare($sql_insert_evento);
    $stmt_insert->bind_param("is", $porte_id, $tipo_evento);
    $stmt_insert->execute();
    $stmt_insert->close();
    echo "<p>Evento de $tipo_evento creado correctamente.</p>";
}
// Ejemplo de hora guardada en UTC
$hora_guardada_utc = '2024-10-12 12:00:00'; // Ejemplo de valor desde la base de datos

// Crear un objeto DateTime con la hora en UTC
$datetime = new DateTime($hora_guardada_utc, new DateTimeZone('UTC'));

// Convertir la hora a la zona horaria del usuario (en este caso, "Europe/Madrid")
$datetime->setTimezone(new DateTimeZone('Europe/Madrid')); 

// Función para obtener información de llegada o salida
function obtenerInfoEvento($conn, $porte_id, $tipo_evento, $evento_tipo) {
    $sql_evento = "SELECT geolocalizacion_$evento_tipo, hora_$evento_tipo FROM eventos WHERE porte_id = ? AND tipo_evento = ?";
    $stmt_evento = $conn->prepare($sql_evento);
    $stmt_evento->bind_param("is", $porte_id, $tipo_evento);
    $stmt_evento->execute();
    $result_evento = $stmt_evento->get_result();
    
    $geolocalizacion = "";
    $hora = "";

    if ($result_evento && $result_evento->num_rows > 0) {
        $evento_info = $result_evento->fetch_assoc();
        $geolocalizacion = $evento_info["geolocalizacion_$evento_tipo"];

        if (!empty($evento_info["hora_$evento_tipo"])) {
            $datetime = new DateTime($evento_info["hora_$evento_tipo"], new DateTimeZone('UTC'));
            $datetime->setTimezone(new DateTimeZone('Europe/Madrid')); // Ajusta según sea necesario
            $hora = $datetime->format('d/m/Y, H:i:s');
        } else {
            $hora = "Pendiente";
        }
    }

    $stmt_evento->close();
    return ['geolocalizacion' => $geolocalizacion, 'hora' => $hora];
}

// Obtener la información de llegada
$llegada_info = obtenerInfoEvento($conn, $porte_id, $tipo_evento, 'llegada');
$geolocalizacion_llegada = $llegada_info['geolocalizacion'];
$hora_llegada = $llegada_info['hora'];

// Obtener la información de salida
$salida_info = obtenerInfoEvento($conn, $porte_id, $tipo_evento, 'salida');
$geolocalizacion_salida = $salida_info['geolocalizacion'];
$hora_salida = $salida_info['hora'];

// Consulta para obtener el estado de la mercancía y observaciones
$sql_mercancia = "SELECT estado_mercancia, observaciones, fecha_observaciones FROM eventos WHERE porte_id = ? AND tipo_evento = ?";
$stmt_mercancia = $conn->prepare($sql_mercancia);
$stmt_mercancia->bind_param("is", $porte_id, $tipo_evento);
$stmt_mercancia->execute();
$result_mercancia = $stmt_mercancia->get_result();

if ($result_mercancia->num_rows > 0) {
    $mercancia_info = $result_mercancia->fetch_assoc();
    $estado_mercancia = $mercancia_info['estado_mercancia'];
    $observaciones = $mercancia_info['observaciones'];
    $fecha_observaciones = $mercancia_info['fecha_observaciones'];
    
    // Mapeo de los estados de la mercancía
    $estado_descripciones = [
        0 => 'Sin daños',
        1 => 'Daños leves',
        2 => 'Daños graves'
    ];

    // Convertir el valor numérico del estado a su descripción en texto
    $estado_mercancia_texto = $estado_descripciones[$estado_mercancia];
} else {
    echo "No se encontró el evento.";
    exit;
}

// Verificar el tipo de evento
$tipo_evento = isset($_GET['tipo_evento']) ? $_GET['tipo_evento'] : null;

if (!$tipo_evento) {
    // Registra el error en los logs para auditoría
    error_log("Error: Tipo de evento no proporcionado. Usuario ID: $usuario_id, Porte ID: $porte_id");

    // Redirige al usuario a una página de error amigable
    header("Location: error.php?error=tipo_evento_no_proporcionado");
    exit();
}


// Consulta para obtener los datos de la firma si ya existen, solo para el tipo de evento actual (recogida o entrega)
$sql_firma = "SELECT firma, nombre_firmante, identificacion_firmante, fecha_firma FROM eventos WHERE porte_id = ? AND tipo_evento = ?";
$stmt_firma = $conn->prepare($sql_firma);
$stmt_firma->bind_param("is", $porte_id, $tipo_evento);  // El segundo parámetro asegura que es para el tipo de evento correcto
$stmt_firma->execute();
$result_firma = $stmt_firma->get_result();

if ($result_firma->num_rows > 0) {
    // Firma existente, cargar datos
    $firma_info = $result_firma->fetch_assoc();
    $firma = $firma_info['firma'];  // Ruta de la imagen de la firma guardada
    $nombre_firmante = $firma_info['nombre_firmante'];
    $identificacion_firmante = $firma_info['identificacion_firmante'];
    $fecha_firma = $firma_info['fecha_firma'];
} else {
    // No hay firma, variables vacías
    $firma = "";
    $nombre_firmante = "";
    $identificacion_firmante = "";
    $fecha_firma = "";
}


// Verificación: muestra el estado de la mercancía en texto antes del formulario (por ejemplo, en la cabecera)
echo "<h3>Estado actual de la mercancía: " . $estado_descripciones[$estado_mercancia] . "</h3>";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión<?php echo $tipo_porte; ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Definir el tipo de evento desde PHP
        const TIPO_EVENTO = <?php echo json_encode($tipo_evento); ?>;

        // Función para registrar la llegada con confirmación
        function registrarLlegada() {
            if (confirm("¿Estás seguro de que deseas registrar esta llegada? Una vez registrado, no se podrá modificar.")) {
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
    </script>
</head>
<body>
    <div class="container">
        <h1>Gestión <?php echo $tipo_evento; ?></h1>
               <h2>Registrar Llegada</h2>
        <label>Geolocalización de Llegada:</label>
        <input type="text" id="geolocalizacion_llegada" value="<?php echo $geolocalizacion_llegada; ?>" readonly><br><br>
        <label>Hora de Llegada:</label>
        <input type="text" id="hora_llegada" value="<?php echo $hora_llegada; ?>" readonly><br><br>
        <button type="button" onclick="registrarLlegada()">Capturar y Registrar Llegada</button><br><br>
        
        <h2>Eliminar Llegada</h2>
        <button type="button" onclick="eliminarLlegada()">Eliminar Registro de Llegada</button>

    </div>
    <br>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Foto/Video</title>
    <style>
        /* Estilo para colocar los botones en el mismo nivel con un pequeño espacio entre ellos */
        .botones {
            display: flex;
            gap: 40px; /* Espacio entre los botones */
        }
    </style>
    <script>
        // Obtener la geolocalización y hora actual
        function obtenerGeolocalizacion() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('geolocalizacion').value = position.coords.latitude + ', ' + position.coords.longitude;
                    document.getElementById('hora').value = new Date().toISOString().slice(0, 19).replace('T', ' ');
                });
            } else {
                alert("La geolocalización no está soportada en este navegador.");
            }
        }
    </script>
</head>
<body onload="obtenerGeolocalizacion()">

<form id="uploadForm" enctype="multipart/form-data">
  <h2> Archivos Multimedia </h2>
    <label for="categoria">Selecciona Categoría:</label>
    <select name="categoria" id="categoria">
        <option value="carga" selected>Carga</option>
        <option value="documento">Documento</option>
    </select><br><br>

  <h4> Tipo Archivo:  </h4>
    <div class="botones">
        <button type="button" id="btn_foto">FOTO</button>
        <button type="button" id="btn_video">VIDEO</button>
    </div>
    <input type="file" id="archivo_foto" name="archivo_foto" accept="image/*" capture="camera" style="display: none;">
    <input type="file" id="archivo_video" name="archivo_video" accept="video/*" capture="camcorder" style="display: none;">

    <input type="hidden" name="porte_id" value="<?php echo $porte_id; ?>">
    <input type="hidden" name="tipo_evento" value="<?php echo $tipo_evento; ?>">
    <input type="hidden" id="geolocalizacion" name="geolocalizacion" value="">
    <input type="hidden" id="hora" name="hora" value="">
</form>

<script>
    document.getElementById('btn_foto').addEventListener('click', function() {
        document.getElementById('archivo_foto').click();
    });
    document.getElementById('btn_video').addEventListener('click', function() {
        document.getElementById('archivo_video').click();
    });

    document.getElementById('archivo_foto').addEventListener('change', function() {
        subirArchivo(this.files[0], 'foto');
    });
    document.getElementById('archivo_video').addEventListener('change', function() {
        subirArchivo(this.files[0], 'video');
    });

    function subirArchivo(archivo, tipo_archivo) {
        var formData = new FormData();
        formData.append(tipo_archivo === 'foto' ? 'archivo_foto' : 'archivo_video', archivo);
        formData.append('categoria', document.getElementById('categoria').value);
        formData.append('porte_id', '<?php echo $porte_id; ?>');
        formData.append('tipo_evento', '<?php echo $tipo_evento; ?>');
        formData.append('geolocalizacion', document.getElementById('geolocalizacion').value);
        formData.append('hora', document.getElementById('hora').value);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'subir_archivo.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('El archivo se ha subido correctamente.');
                location.reload();
            } else {
                alert('Error al subir el archivo.');
            }
        };
        xhr.send(formData);
    }
</script>

<h3>Archivos Subidos:</h3>
<table border="1" cellpadding="10">
    <tr>
        <th>Fecha y Hora</th>
        <th>Tipo de Archivo</th>
        <th>Ver Archivo</th>
    </tr>
<?php
$sql = "SELECT nombre_archivo, tipo_archivo, url_archivo, timestamp 
        FROM multimedia_recogida_entrega 
        WHERE porte_id = '$porte_id' AND tipo_evento = '$tipo_evento'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $fecha_hora = $row['timestamp'];
        echo "<tr>";
        echo "<td>" . date("d-m-Y H:i:s", strtotime($fecha_hora)) . "</td>";
        echo "<td>" . ucfirst($row['tipo_archivo']) . "</td>";
        echo "<td><a href='" . $row['url_archivo'] . "' target='_blank'>Ver Archivo</a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3'>No se han subido archivos aún.</td></tr>";
}

mysqli_close($conn);
?>
</table>

</body>
</html>
<!-- Modificación del estado de la mercancía -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de la Mercancía</title>
</head>
<body>
    <h2>Estado de la Mercancía</h2>
    <form action="guardar_estado_mercancia.php" method="POST">
        <input type="hidden" name="porte_id" value="<?php echo $porte_id; ?>">
        <input type="hidden" name="tipo_evento" value="<?php echo $tipo_evento; ?>">

        <label for="estado_mercancia">Estado de la Mercancía:</label>
        <select name="estado_mercancia" id="estado_mercancia">
            <option value="0" <?php if($estado_mercancia == 0) echo 'selected'; ?>>Sin daños</option>
            <option value="1" <?php if($estado_mercancia == 1) echo 'selected'; ?>>Daños leves</option>
            <option value="2" <?php if($estado_mercancia == 2) echo 'selected'; ?>>Daños graves</option>
        </select>
        <br><br>

        <label for="observaciones">Observaciones:</label>
        <textarea name="observaciones" id="observaciones" rows="4" cols="50"><?php echo htmlspecialchars($observaciones); ?></textarea>
        <br><br>

        <label for="fecha_observaciones">Fecha de Observaciones:</label>
        <input type="datetime" name="fecha_observaciones" id="fecha_observaciones" value="<?php echo htmlspecialchars($fecha_observaciones); ?>" readonly>
        <br><br>

        <input type="submit" value="Guardar Cambios">
    </form>
</body>


<!-- Firma del evento -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma del Evento</title>
    <style>
        .firma-section {
            display: <?php echo ($firma) ? "block" : "none"; ?>;
            margin-top: 20px;
        }
        .signature-pad {
            border: 3px solid #000;
            width: 300px;
            height: 150px;
            background-color: #f0f0f0;
            padding: 10px;
            margin-top: 10px;
        }
        #success-message {
            color: green;
            display: none;
        }
    </style>
</head>
<body>
    <h2>Firma del Evento</h2>
    
    <button id="firmaOpcional">Firma (Opcional)</button>
    <div class="firma-section" id="firmaSection">
        <form id="firmaForm">
            <input type="hidden" name="porte_id" value="<?php echo $porte_id; ?>">
            <input type="hidden" name="tipo_evento" value="<?php echo $tipo_evento; ?>">

            <label for="nombre_firmante">Nombre del Firmante:</label>
            <input type="text" name="nombre_firmante" id="nombre_firmante" value="<?php echo $nombre_firmante; ?>" required>
            <br><br>

            <label for="identificacion_firmante">Documento de Identificación:</label>
            <input type="text" name="identificacion_firmante" id="identificacion_firmante" value="<?php echo $identificacion_firmante; ?>" required>
            <br><br>

            <?php 
            // Aquí el cambio:
            // Originalmente: if ($firma && file_exists($firma)):
            // Ajustado para ruta web => file_exists necesita la ruta absoluta:
            if ($firma && strpos($firma, 'data:image') === 0):
            ?>
                <p>Firma actual:</p>
                <img src="<?php echo $firma; ?>" alt="Firma" width="300" height="150"><br><br>
            <?php else: ?>
                <label for="firma">Firma (dibujar aquí):</label><br>
                <canvas id="signature-pad" class="signature-pad" width=300 height=150></canvas>
                <input type="hidden" name="firma" id="firma">
                <br><br>
                <button type="button" id="clear-pad">Limpiar Firma</button><br><br>
            <?php endif; ?>

            <label for="fecha_firma">Fecha de Firma:</label>
            <input type="datetime" name="fecha_firma" id="fecha_firma" value="<?php echo $fecha_firma ? $fecha_firma : date('Y-m-d H:i:s'); ?>" readonly>
            <br><br>

            <input type="submit" value="Guardar Firma">
        </form>
    </div>

    <p id="success-message">Firma registrada exitosamente.</p>

    <script>
        // Mostrar/ocultar la sección de firma cuando se hace clic en el botón "Firma (Opcional)"
        document.getElementById("firmaOpcional").addEventListener("click", function() {
            var firmaSection = document.getElementById("firmaSection");
            if (firmaSection.style.display === 'none' || firmaSection.style.display === '') {
                firmaSection.style.display = 'block';
            } else {
                firmaSection.style.display = 'none';
            }
        });

        // AJAX para enviar el formulario sin recargar la página
        document.getElementById('firmaForm').addEventListener('submit', function(event) {
            event.preventDefault();
            var formData = new FormData(this);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'guardar_firma_evento.php', true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('success-message').style.display = 'block';
                }
            };

            xhr.send(formData);
        });

        // Si la firma aún no existe, habilitar la funcionalidad del canvas
        <?php if (!$firma || strpos($firma, 'data:image') !== 0): ?>
        var canvas = document.getElementById('signature-pad');
        var signaturePad = canvas.getContext('2d');
        var isDrawing = false;

        canvas.addEventListener('mousedown', function(e) {
            isDrawing = true;
            signaturePad.beginPath();
            signaturePad.moveTo(e.offsetX, e.offsetY);
        });

        canvas.addEventListener('mousemove', function(e) {
            if (isDrawing) {
                signaturePad.lineTo(e.offsetX, e.offsetY);
                signaturePad.stroke();
            }
        });

        canvas.addEventListener('mouseup', function() {
            isDrawing = false;
        });

        document.getElementById('clear-pad').addEventListener('click', function() {
            signaturePad.clearRect(0, 0, canvas.width, canvas.height);
        });

        document.querySelector("form").addEventListener("submit", function() {
            var dataURL = canvas.toDataURL();
            document.getElementById('firma').value = dataURL;  // Guardar la imagen en base64
        });
        <?php endif; ?>
    </script>
</body>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salida</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>

        // Obtener el tipo de evento desde PHP (ya que viene exógena)
        const TIPO_EVENTO = <?php echo json_encode($tipo_evento); ?>;

        // Función para registrar la salida con confirmación
        function registrarSalida() {
            if (confirm("¿Estás seguro de que deseas registrar esta salida? Una vez registrado, no se podrá modificar.")) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        let latitud = position.coords.latitude;
                        let longitud = position.coords.longitude;
                        let coordenadas = latitud + ", " + longitud;
                        let fechaHoraActual = new Date().toISOString().slice(0, 19).replace('T', ' ');

                        // Enviar datos al servidor
                        $.ajax({
                            url: 'eventos_procesar.php',
                            type: 'POST',
                            data: {
                                registrar_salida: true,
                                porte_id: <?php echo json_encode($porte_id); ?>,
                                tipo_evento: TIPO_EVENTO,
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
        }

        // Función para eliminar la salida
        function eliminarSalida() {
            if (confirm("¿Borrar registro de salida?")) {
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
<!-- Botón de Volver atrás -->
<a href="javascript:history.back();" class="btn btn-primary">Volver Atrás</a>


</body>
</html>
