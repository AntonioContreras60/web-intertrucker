<?php
session_start();
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

// Obtener el ID del usuario desde la sesión
$usuario_id = $_SESSION['usuario_id'];

// Obtener el ID del porte desde la URL
$porte_id = isset($_GET['porte_id']) ? $_GET['porte_id'] : null;
$tipo_evento = 'entrega'; // Forzar siempre el tipo de evento como 'entrega'

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
    SELECT p.id AS porte_id, p.mercancia_descripcion AS mercancia, p.cantidad, p.peso_total, p.tipo_palet, p.volumen_total, 
           p.localizacion_entrega AS origen, p.localizacion_entrega AS destino, 
           p.fecha_entrega, p.fecha_entrega, v.matricula, v.marca, v.modelo, 
           c.nombre AS nombre_camionero, c.apellidos AS apellidos_camionero
    FROM porte_vehiculo_camionero pvc
    JOIN portes p ON pvc.porte_id = p.id
    JOIN vehiculos v ON pvc.vehiculo_id = v.id
    JOIN camioneros c ON pvc.camionero_id = c.id
    WHERE pvc.usuario_id = ? AND p.id = ?
";

$stmt_porte = $conn->prepare($sql_porte);
$stmt_porte->bind_param("ii", $usuario_id, $porte_id);
$stmt_porte->execute();
$result_porte = $stmt_porte->get_result();

if ($result_porte && $result_porte->num_rows > 0) {
    $porte_info = $result_porte->fetch_assoc();
} else {
    echo "<p>No se encontró información del porte.</p>";
}

// Consulta para verificar si ya existe un evento de tipo `entrega`
$sql_evento = "SELECT id FROM eventos WHERE porte_id = ? AND tipo_evento = ? LIMIT 1";
$stmt_evento = $conn->prepare($sql_evento);
$stmt_evento->bind_param("is", $porte_id, $tipo_evento); // Siempre 'entrega'
$stmt_evento->execute();
$result_evento = $stmt_evento->get_result();

$evento_id = '';
if ($result_evento && $result_evento->num_rows > 0) {
    // Si el evento ya existe, recuperar el ID del evento
    $evento_info = $result_evento->fetch_assoc();
    $evento_id = $evento_info['id'];
} else {
    // Si no existe el evento de tipo `entrega`, crearlo.
    $sql_insert_evento = "INSERT INTO eventos (porte_id, tipo_evento, estado_mercancia, observaciones, hora_llegada) VALUES (?, ?, '', '', NOW())";
    $stmt_insert = $conn->prepare($sql_insert_evento);
    $stmt_insert->bind_param("is", $porte_id, $tipo_evento); // Siempre 'entrega'
    $stmt_insert->execute();
    $evento_id = $stmt_insert->insert_id;
    $stmt_insert->close();
}
// Consulta para obtener el `evento_id` asociado al `porte_id`
$sql_evento = "SELECT id FROM eventos WHERE porte_id = ? LIMIT 1";
$stmt_evento = $conn->prepare($sql_evento);
$stmt_evento->bind_param("i", $porte_id);
$stmt_evento->execute();
$result_evento = $stmt_evento->get_result();

if ($result_evento && $result_evento->num_rows > 0) {
    $evento_info = $result_evento->fetch_assoc();
    $evento_id = $evento_info['id'];
} else {
    die("No se encontró un evento asociado al porte.");
}
// Consulta para obtener la información de llegada
$sql_llegada = "SELECT geolocalizacion_llegada, hora_llegada FROM eventos WHERE porte_id = ? AND tipo_evento = ?";
$stmt_llegada = $conn->prepare($sql_llegada);
$stmt_llegada->bind_param("is", $porte_id, $tipo_evento); // Siempre 'entrega'
$stmt_llegada->execute();
$result_llegada = $stmt_llegada->get_result();

$geolocalizacion_llegada = "";
$hora_llegada = "";

// Si se encontró un evento de llegada, asignar valores
if ($result_llegada && $result_llegada->num_rows > 0) {
    $llegada_info = $result_llegada->fetch_assoc();
    $geolocalizacion_llegada = $llegada_info['geolocalizacion_llegada'];
    $hora_llegada = date("d/m/Y, H:i:s", strtotime($llegada_info['hora_llegada']));
}

// Consulta para obtener la información de salida
$sql_salida = "SELECT geolocalizacion_salida, hora_salida FROM eventos WHERE id = ?";
$stmt_salida = $conn->prepare($sql_salida);
$stmt_salida->bind_param("i", $evento_id);
$stmt_salida->execute();
$result_salida = $stmt_salida->get_result();

$geolocalizacion_salida = "";
$hora_salida = "";

// Si se encontró un evento de salida, asignar valores
if ($result_salida && $result_salida->num_rows > 0) {
    $salida_info = $result_salida->fetch_assoc();
    $geolocalizacion_salida = $salida_info['geolocalizacion_salida'];
    $hora_salida = !empty($salida_info['hora_salida']) ? date("d/m/Y, H:i:s", strtotime($salida_info['hora_salida'])) : "";
}

// Consulta para obtener el estado de la mercancía
$sql_estado_mercancia = "SELECT estado_mercancia FROM eventos WHERE id = ?";
$stmt_estado_mercancia = $conn->prepare($sql_estado_mercancia);
$stmt_estado_mercancia->bind_param("i", $evento_id);
$stmt_estado_mercancia->execute();
$resultado_estado = $stmt_estado_mercancia->get_result();

if ($resultado_estado && $resultado_estado->num_rows > 0) {
    $fila_estado = $resultado_estado->fetch_assoc();
    $estado_mercancia = $fila_estado['estado_mercancia'];
} else {
    $estado_mercancia = '';
}
$stmt_estado_mercancia->close();

// Consulta para obtener la última observación y la fecha de modificación del evento específico
$sql_observaciones = "SELECT observaciones, fecha_observaciones FROM eventos WHERE id = ?";
$stmt_observaciones = $conn->prepare($sql_observaciones);
$stmt_observaciones->bind_param("i", $evento_id);
$stmt_observaciones->execute();
$resultado_observaciones = $stmt_observaciones->get_result();

// Asignar valores de observación y fecha
$observaciones = '';
$fecha_observaciones = '';
if ($resultado_observaciones && $resultado_observaciones->num_rows > 0) {
    $fila_observaciones = $resultado_observaciones->fetch_assoc();
    $observaciones = $fila_observaciones['observaciones'];
    $fecha_observaciones = $fila_observaciones['fecha_observaciones'];
}

// Manejo de observaciones solo si se hace clic en el botón "Guardar Observaciones"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_observaciones'])) {
    $estado_mercancia = isset($_POST['estado_mercancia']) ? $_POST['estado_mercancia'] : '';
    $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';

    // Solo generar la fecha de observaciones si se hace clic en guardar observaciones
    $hora_observaciones = date("Y-m-d H:i:s");

    // Actualizar solo las observaciones y el estado de la mercancía
    $sql_evento_update = "UPDATE eventos SET estado_mercancia = ?, observaciones = ?, fecha_observaciones = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_evento_update);
    $stmt_update->bind_param("sssi", $estado_mercancia, $observaciones, $hora_observaciones, $evento_id);

    if ($stmt_update->execute()) {
        echo "Estado de la mercancía y observaciones actualizados correctamente.";
    } else {
        echo "Error al actualizar: " . $stmt_update->error;
    }
    $stmt_update->close();
}

// Obtener datos de la firma si ya están guardados
$sql_firma = "SELECT nombre_firmante, identificacion_firmante, firma, fecha_firma FROM eventos WHERE id = ?";
$stmt_firma = $conn->prepare($sql_firma);
$stmt_firma->bind_param("i", $evento_id);
$stmt_firma->execute();
$resultado_firma = $stmt_firma->get_result();

if ($resultado_firma && $resultado_firma->num_rows > 0) {
    $fila_firma = $resultado_firma->fetch_assoc();
    $nombre_firmante = $fila_firma['nombre_firmante'];
    $identificacion_firmante = $fila_firma['identificacion_firmante'];
    $ruta_firma = $fila_firma['firma'];
    $hora_firma = $fila_firma['fecha_firma'];
} else {
    $nombre_firmante = '';
    $identificacion_firmante = '';
    $ruta_firma = '';
    $hora_firma = '';
}

$stmt_firma->close();
$stmt_porte->close();
$stmt_evento->close();
$stmt_observaciones->close();
$stmt_llegada->close();
$stmt_salida->close();
mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Entrega - Porte ID: <?php echo $porte_id; ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const TIPO_EVENTO = 'entrega'; // Definir tipo de evento como constante

        function registrarLlegada() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    let latitud = position.coords.latitude;
                    let longitud = position.coords.longitude;
                    let coordenadas = latitud + ", " + longitud;
            let fechaHoraActual = new Date().toISOString().slice(0, 19).replace('T', ' '); // "YYYY-MM-DD HH:MM:SS"

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

        function registrarSalida() {
    if (navigator.geolocation) {
        // Capturar la geolocalización actual
        navigator.geolocation.getCurrentPosition(function (position) {
            let latitud = position.coords.latitude;
            let longitud = position.coords.longitude;
            let coordenadas = latitud + ", " + longitud;
            let fechaHoraActual = new Date().toLocaleString('sv-SE'); // Formato: "YYYY-MM-DD HH:MM:SS"

            // Enviar datos al servidor usando AJAX
            $.ajax({
                url: 'eventos_procesar.php',
                type: 'POST',
                data: {
                    registrar_salida: true,
                    porte_id: <?php echo json_encode($porte_id); ?>,
                    tipo_evento: 'entrega', // Asegúrate de que sea el tipo de evento correcto
                    geolocalizacion_salida: coordenadas,
                    hora_salida: fechaHoraActual
                },
                success: function(response) {
                    alert("Salida registrada correctamente.");

                    // Actualizar los campos de la página con los nuevos datos
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
           let categoriaSeleccionada = '';

        function seleccionarCategoria(categoria) {
            categoriaSeleccionada = categoria;
        }

        function subirAutomaticamente(tipo) {
            if (!categoriaSeleccionada) {
                alert("Por favor, selecciona si es 'Carga' o 'Documento' antes de capturar.");
                return;
            }

            const archivoInput = tipo === 'Foto' ? document.getElementById('archivoFoto') : document.getElementById('archivoVideo');
            const archivoSeleccionado = archivoInput.files[0];
            const tipoArchivo = tipo;

            if (archivoSeleccionado) {
                const nombreArchivo = archivoSeleccionado.name;
                const tamanoArchivo = archivoSeleccionado.size;
                const urlArchivo = '/uploads/multimedia/' + nombreArchivo;

                const fechaActual = new Date();
                const timestamp = fechaActual.getFullYear() + '-' +
                                  ('0' + (fechaActual.getMonth() + 1)).slice(-2) + '-' +
                                  ('0' + fechaActual.getDate()).slice(-2) + ' ' +
                                  ('0' + fechaActual.getHours()).slice(-2) + ':' +
                                  ('0' + fechaActual.getMinutes()).slice(-2) + ':' +
                                  ('0' + fechaActual.getSeconds()).slice(-2);

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const latitud = position.coords.latitude;
                        const longitud = position.coords.longitude;

                        $.ajax({
                            url: 'eventos_procesar.php',
                            type: 'POST',
                            data: {
                                accion: 'guardar_info',
                                evento_id: <?php echo $evento_id; ?>,
                                nombre_archivo: nombreArchivo,
                                tamano_archivo: tamanoArchivo,
                                url_archivo: urlArchivo,
                                latitud: latitud,
                                longitud: longitud,
                                fecha_hora: timestamp,
                                tipo_archivo: tipoArchivo,
                                categoria: categoriaSeleccionada
                            },
                            success: function(response) {
                                console.log("Información guardada en la base de datos: " + response);

                                const formData = new FormData();
                                formData.append('archivo', archivoSeleccionado);

                                const xhr = new XMLHttpRequest();
                                xhr.open('POST', 'eventos_procesar.php', true);
                                xhr.onload = function() {
                                    if (xhr.status === 200) {
                                        alert("Archivo subido al servidor exitosamente y procesado.");
                                        location.reload();  // Recargar la página para mostrar el nuevo archivo en la lista
                                    } else {
                                        alert("Error al subir el archivo al servidor.");
                                    }
                                };
                                xhr.send(formData);
                            },
                            error: function(xhr, status, error) {
                                alert("Error al guardar la información en la base de datos: " + xhr.responseText);
                            }
                        });
                    });
                } else {
                    alert("La geolocalización no es soportada por este navegador.");
                }
            } else {
                alert("No se ha seleccionado ningún archivo.");
            }
        }
              let categoriaSeleccionada = '';

        function seleccionarCategoria(categoria) {
            categoriaSeleccionada = categoria;
        }

        function subirAutomaticamente(tipo) {
            if (!categoriaSeleccionada) {
                alert("Por favor, selecciona si es 'Carga' o 'Documento' antes de capturar.");
                return;
            }

            const archivoInput = tipo === 'Foto' ? document.getElementById('archivoFoto') : document.getElementById('archivoVideo');
            const archivoSeleccionado = archivoInput.files[0];
            const tipoArchivo = tipo;

            if (archivoSeleccionado) {
                const nombreArchivo = archivoSeleccionado.name;
                const tamanoArchivo = archivoSeleccionado.size;
                const urlArchivo = '/uploads/multimedia/' + nombreArchivo;

                const fechaActual = new Date();
                const timestamp = fechaActual.getFullYear() + '-' +
                                  ('0' + (fechaActual.getMonth() + 1)).slice(-2) + '-' +
                                  ('0' + fechaActual.getDate()).slice(-2) + ' ' +
                                  ('0' + fechaActual.getHours()).slice(-2) + ':' +
                                  ('0' + fechaActual.getMinutes()).slice(-2) + ':' +
                                  ('0' + fechaActual.getSeconds()).slice(-2);

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const latitud = position.coords.latitude;
                        const longitud = position.coords.longitude;

                        $.ajax({
                            url: 'eventos_procesar.php',
                            type: 'POST',
                            data: {
                                accion: 'guardar_info',
                                evento_id: <?php echo $evento_id; ?>,
                                nombre_archivo: nombreArchivo,
                                tamano_archivo: tamanoArchivo,
                                url_archivo: urlArchivo,
                                latitud: latitud,
                                longitud: longitud,
                                fecha_hora: timestamp,
                                tipo_archivo: tipoArchivo,
                                categoria: categoriaSeleccionada
                            },
                            success: function(response) {
                                console.log("Información guardada en la base de datos: " + response);

                                const formData = new FormData();
                                formData.append('archivo', archivoSeleccionado);

                                const xhr = new XMLHttpRequest();
                                xhr.open('POST', 'eventos_procesar.php', true);
                                xhr.onload = function() {
                                    if (xhr.status === 200) {
                                        alert("Archivo subido al servidor exitosamente y procesado.");
                                        location.reload();  // Recargar la página para mostrar el nuevo archivo en la lista
                                    } else {
                                        alert("Error al subir el archivo al servidor.");
                                    }
                                };
                                xhr.send(formData);
                            },
                            error: function(xhr, status, error) {
                                alert("Error al guardar la información en la base de datos: " + xhr.responseText);
                            }
                        });
                    });
                } else {
                    alert("La geolocalización no es soportada por este navegador.");
                }
            } else {
                alert("No se ha seleccionado ningún archivo.");
            }
        }
    </script>
    </script>
</head>
<body>
    <div class="container">
        <h1>Entrega porte recibido </h1>
        <?php if ($porte_info): ?>
            <p><strong>Mercancía:</strong> <?php echo $porte_info['mercancia']; ?></p>
            <p><strong>tipo palet:</strong> <?php echo $porte_info['tipo_palet']; ?></p>
            <p><strong>Cantidad:</strong> <?php echo $porte_info['cantidad']; ?></p>
            <p><strong>Peso:</strong> <?php echo $porte_info['peso_total']; ?></p>    
            <p><strong>Volumen:</strong> <?php echo $porte_info['volumen_total']; ?></p>
            <p><strong>Origen:</strong> <?php echo $porte_info['origen']; ?></p>
            <p><strong>Destino:</strong> <?php echo $porte_info['destino']; ?></p>
            <p><strong>Fecha de Entrega:</strong> <?php echo $porte_info['fecha_entrega']; ?></p>
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

         <h2>Captura de Fotos y Videos</h2>
        <form id="multimediaForm">
            <label>Seleccione la Categoría:</label>
            <select id="categoria" onchange="seleccionarCategoria(this.value)">
                <option value="">-- Seleccione --</option>
                <option value="Carga">Carga</option>
                <option value="Documento">Documento</option>
            </select><br><br>

            <label for="archivoFoto">Capturar Foto:</label>
            <input type="file" id="archivoFoto" accept="image/*" capture="camera" onchange="subirAutomaticamente('Foto')"><br><br>

            <label for="archivoVideo">Grabar Video:</label>
            <input type="file" id="archivoVideo" accept="video/*" capture="camera" onchange="subirAutomaticamente('Video')"><br><br>
        </form>

        <!-- Mostrar los archivos subidos con enlaces -->
        <h3>Archivos Subidos</h3>
        <?php
        include('conexion.php');
        $sql_archivos = "SELECT nombre_archivo, tipo_archivo, url_archivo, tamano, `carga o documento` AS asunto FROM multimedia_entrega_entrega WHERE evento_id = ?";
        $stmt_archivos = $conn->prepare($sql_archivos);
        $stmt_archivos->bind_param("i", $evento_id);
        $stmt_archivos->execute();
        $result_archivos = $stmt_archivos->get_result();

        if ($result_archivos->num_rows > 0) {
            echo "<table border='1'>";
            echo "<tr><th>Nombre</th><th>Asunto</th><th>Tipo</th><th>URL</th><th>Tamaño (KB)</th></tr>";
            while ($row = $result_archivos->fetch_assoc()) {
                $nombreCorto = substr($row['nombre_archivo'], -9);  // Obtener los últimos 9 caracteres del nombre del archivo
                echo "<tr>";
                echo "<td>" . htmlspecialchars($nombreCorto) . "</td>";
                echo "<td>" . htmlspecialchars($row['asunto']) . "</td>";  // Mostrar si es "Carga" o "Documento"
                echo "<td>" . htmlspecialchars($row['tipo_archivo']) . "</td>";
                echo "<td><a href='" . htmlspecialchars($row['url_archivo']) . "' target='_blank'>Ver Archivo</a></td>";
                echo "<td>" . htmlspecialchars(number_format($row['tamano'] / 1024, 2)) . " KB</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No se han subido archivos para este evento.</p>";
        }
        $stmt_archivos->close();
        $conn->close();
        ?>
          <h3>Estado de la Mercancía</h3>
<!-- Formulario separado para estado de mercancía y observaciones -->
<form method="POST" action="entrega.php?porte_id=<?php echo $porte_id; ?>">

    <label for="estado_mercancia">Estado de la Mercancía:</label>
    <select name="estado_mercancia" id="estado_mercancia">
        <option value="">-- Seleccione el estado --</option>  <!-- Opción vacía predeterminada -->
        <option value="En buen estado" <?php echo ($estado_mercancia == 'En buen estado') ? 'selected' : ''; ?>>En buen estado</option>
        <option value="Leve daño" <?php echo ($estado_mercancia == 'Leve daño') ? 'selected' : ''; ?>>Leve daño</option>
        <option value="Daño moderado" <?php echo ($estado_mercancia == 'Daño moderado') ? 'selected' : ''; ?>>Daño moderado</option>
        <option value="Grave daño" <?php echo ($estado_mercancia == 'Grave daño') ? 'selected' : ''; ?>>Grave daño</option>
    </select>

    <br><br>

    <label for="observaciones">Observaciones:</label>
    <textarea name="observaciones" id="observaciones" rows="4" cols="50"><?php echo htmlspecialchars($observaciones); ?></textarea>
    
    <br><br>

    <!-- Mostrar la fecha de observaciones solo si existe -->
    <?php if (!empty($fecha_observaciones)): ?>
        <p><strong>Fecha de Observaciones:</strong> <?php echo date("d/m/Y H:i:s", strtotime($fecha_observaciones)); ?></p>
    <?php endif; ?>

    <br>
    <button type="submit" name="guardar_observaciones">Guardar Observaciones</button>
</form>

<!-- Sección de la firma -->
<div id="seccion_firma" style="display: <?php echo ($nombre_firmante != '' && $identificacion_firmante != '') ? 'block' : 'none'; ?>;">
    <form method="POST" action="entrega.php?porte_id=<?php echo $porte_id; ?>" enctype="multipart/form-data">
        <h3>Datos del Firmante</h3>
        <label for="nombre_firmante">Nombre del Firmante:</label>
        <input type="text" name="nombre_firmante" id="nombre_firmante" value="<?php echo htmlspecialchars($nombre_firmante); ?>" placeholder="Nombre completo" <?php echo ($nombre_firmante != '') ? 'readonly' : ''; ?>>
        <br><br>

        <label for="identificacion_firmante">Número de Identificación:</label>
        <input type="text" name="identificacion_firmante" id="identificacion_firmante" value="<?php echo htmlspecialchars($identificacion_firmante); ?>" placeholder="DNI, Pasaporte, etc." <?php echo ($identificacion_firmante != '') ? 'readonly' : ''; ?>>
        <br><br>

        <!-- Campo oculto para registrar la fecha y hora de la firma -->
        <input type="hidden" id="hora_firma" name="hora_firma" value="<?php echo htmlspecialchars($hora_firma); ?>">
        <br>

        <!-- Área de firma digital solo si no está registrada -->
        <?php if ($ruta_firma == ''): ?>
        <h3>Firma Digital</h3>
        <label for="firma_canvas">Dibujar Firma:</label>
        <br>
        <canvas id="firma_canvas" width="400" height="200" style="border:1px solid #000000;"></canvas>
        <br>
        <button type="button" onclick="limpiarCanvas()">Limpiar</button>
        <button type="button" onclick="guardarFirma()">Guardar Firma</button>
        <br><br>

        <!-- Campo oculto para guardar la firma digital en formato base64 -->
        <input type="hidden" id="firma_datos" name="firma_datos">
        <br>
        <button type="submit" name="guardar_firma">Guardar Firma y Datos del Firmante</button>
        <?php else: ?>
            <!-- Mostrar la firma guardada si ya está presente -->
            <h3>Firma Guardada</h3>
            <img src="<?php echo htmlspecialchars($ruta_firma); ?>" alt="Firma del Firmante" style="border:1px solid #000000;">
            <br><br>
            <p><strong>Fecha de la Firma:</strong> <?php echo htmlspecialchars($hora_firma); ?></p>
        <?php endif; ?>
    </form>
</div>
<br>

<!-- JavaScript para controlar la visibilidad de la sección de firma y del botón -->
<script>
var canvas = document.getElementById('firma_canvas');
var ctx = canvas.getContext('2d');
var dibujando = false;

// Función para mostrar u ocultar la sección de firma y controlar el botón de guardar
function toggleFirma() {
    var seccionFirma = document.getElementById('seccion_firma');
    var botonMostrarFirma = document.getElementById('boton_mostrar_firma');
    var nombreFirmante = document.getElementById('nombre_firmante').value;
    var identificacionFirmante = document.getElementById('identificacion_firmante').value;

    if (seccionFirma.style.display === "none") {
        seccionFirma.style.display = "block";
        botonMostrarFirma.textContent = "Ocultar Sección de Firma";
    } else {
        // Solo ocultar si los campos no están rellenos
        if (nombreFirmante === '' && identificacionFirmante === '') {
            seccionFirma.style.display = "none";
            botonMostrarFirma.textContent = "Firmar (opcional)";
        }
    }
}

// Mostrar la sección de firma automáticamente si ya está rellena al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    var nombreFirmante = document.getElementById('nombre_firmante').value;
    var identificacionFirmante = document.getElementById('identificacion_firmante').value;
    var seccionFirma = document.getElementById('seccion_firma');
    var botonMostrarFirma = document.getElementById('boton_mostrar_firma');

    // Si la firma ya está guardada, mostrar la sección de firma y deshabilitar el botón
    if (nombreFirmante !== '' && identificacionFirmante !== '') {
        seccionFirma.style.display = "block";
        botonMostrarFirma.style.display = "none";  // Ocultar el botón para evitar ocultar la sección
    }
});

// Eventos para dibujar en el canvas
canvas.addEventListener('mousedown', function(e) { dibujando = true; ctx.beginPath(); });
canvas.addEventListener('mouseup', function(e) { dibujando = false; });
canvas.addEventListener('mousemove', function(e) {
    if (dibujando) {
        var rect = canvas.getBoundingClientRect();
        ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
        ctx.stroke();
    }
});

// Función para limpiar el canvas
function limpiarCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

// Función para guardar la firma en formato base64 y registrar la hora
function guardarFirma() {
    var firmaBase64 = canvas.toDataURL();  // Convertir la firma a base64
    document.getElementById('firma_datos').value = firmaBase64;  // Guardar la base64 en el campo oculto
    document.getElementById('hora_firma').value = new Date().toISOString();  // Registrar la hora de la firma
    alert("Firma guardada correctamente.");
}
</script>

    <div class="container">
        
         <h2>Registrar Salida</h2>
        <label>Geolocalización de Salida:</label>
        <input type="text" id="geolocalizacion_salida" value="<?php echo htmlspecialchars($geolocalizacion_salida); ?>" readonly><br><br>
        <label>Hora de Salida:</label>
        <input type="text" id="hora_salida" value="<?php echo htmlspecialchars($hora_salida); ?>" readonly><br><br>
        <button type="button" onclick="registrarSalida()">Capturar y Registrar Salida</button><br><br>

        <h3>Eliminar Salida</h3>
        <button type="button" onclick="eliminarSalida()">Eliminar Registro de Salida</button> <br><br><br>

    </div>
<style>
    .mi-boton {
        font-size: 16px; /* Aumenta el tamaño del texto */
        background-color: #007BFF; /* Cambia el color de fondo */
        color: white; /* Cambia el color del texto a blanco */
        padding: 10px 20px; /* Ajusta el espaciado interno */
        border: none; /* Sin bordes */
        cursor: pointer; /* Cambia el cursor a mano al pasar */
    }
</style>

<button onclick="window.location.href='portes_trucks_recibidos.php';" class="mi-boton">Volver a Portes Recibidos</button>
<br><br>
</body>
</html>
