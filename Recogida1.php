<?php
session_start();
include('conexion.php');

// Obtener el ID del usuario desde la sesión
$usuario_id = $_SESSION['usuario_id'];

// Obtener el ID del porte desde la URL
$porte_id = isset($_GET['porte_id']) ? $_GET['porte_id'] : null;

if (!$porte_id) {
    die("ID de porte no proporcionado.");
}

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
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

$stmt_evento->close();
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Recogida - Porte ID: <?php echo $porte_id; ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
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
</head>
<body>
    <div class="container">
        <h1>Gestión de Recogida - Porte ID: <?php echo $porte_id; ?></h1>

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
        $sql_archivos = "SELECT nombre_archivo, tipo_archivo, url_archivo, tamano, `carga o documento` AS asunto FROM multimedia_recogida_entrega WHERE evento_id = ?";
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
    </div>
</body>
</html>
