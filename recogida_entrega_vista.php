<?php
session_start();

// Ajusta si quieres ver errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

// Ajustar la zona horaria a la de España (opcional)
date_default_timezone_set('Europe/Madrid');

// Verificar si el usuario ha iniciado sesión (opcional según tu proyecto)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener parámetros de la URL
$porte_id = isset($_GET['porte_id']) ? intval($_GET['porte_id']) : null;
$tipo_evento = isset($_GET['tipo_evento']) ? $_GET['tipo_evento'] : null;

// Validar
if (!$porte_id || !$tipo_evento) {
    die("Error: Datos faltantes (porte_id y tipo_evento).");
}

// 1) Datos básicos del porte
$sql_porte = "SELECT 
    mercancia_descripcion, 
    fecha_recogida, 
    localizacion_recogida, 
    localizacion_entrega
  FROM portes
  WHERE id = ?";
$stmt_porte = $conn->prepare($sql_porte);
$stmt_porte->bind_param("i", $porte_id);
$stmt_porte->execute();
$result_porte = $stmt_porte->get_result();
$porte_info = $result_porte->fetch_assoc();
$stmt_porte->close();

if (!$porte_info) {
    die("Error: No se encontró el porte con ID=" . $porte_id);
}

// Extraer algunos valores
$mercancia_descripcion = $porte_info['mercancia_descripcion'] ?? '';
$fecha_recogida = $porte_info['fecha_recogida'] ?? '';
$localizacion_recogida = $porte_info['localizacion_recogida'] ?? '';
$localizacion_entrega = $porte_info['localizacion_entrega'] ?? '';

// 2) Info de llegada (geolocalizacion_llegada / hora_llegada)
$sql_llegada = "SELECT geolocalizacion_llegada, hora_llegada
  FROM eventos
  WHERE porte_id = ? AND tipo_evento = ?
  LIMIT 1";
$stmt_llegada = $conn->prepare($sql_llegada);
$stmt_llegada->bind_param("is", $porte_id, $tipo_evento);
$stmt_llegada->execute();
$result_llegada = $stmt_llegada->get_result();
$llegada_data = $result_llegada->fetch_assoc();
$stmt_llegada->close();

$geolocalizacion_llegada = $llegada_data['geolocalizacion_llegada'] ?? 'No registrada';
$hora_llegada = $llegada_data['hora_llegada'] ?? 'No registrada';

// 3) Documentos multimedia
$sql_multimedia = "SELECT nombre_archivo, tipo_archivo, url_archivo, timestamp
  FROM multimedia_recogida_entrega
  WHERE porte_id = ? AND tipo_evento = ?";
$stmt_multimedia = $conn->prepare($sql_multimedia);
$stmt_multimedia->bind_param("is", $porte_id, $tipo_evento);
$stmt_multimedia->execute();
$result_multimedia = $stmt_multimedia->get_result();
// (Luego usaremos $result_multimedia en HTML)
$stmt_multimedia->close();

// 4) Estado de la mercancía
$sql_mercancia = "SELECT estado_mercancia, observaciones
  FROM eventos
  WHERE porte_id = ? AND tipo_evento = ?
  LIMIT 1";
$stmt_mercancia = $conn->prepare($sql_mercancia);
$stmt_mercancia->bind_param("is", $porte_id, $tipo_evento);
$stmt_mercancia->execute();
$result_mercancia = $stmt_mercancia->get_result();
$mercancia_data = $result_mercancia->fetch_assoc();
$stmt_mercancia->close();

$estado_mercancia = $mercancia_data['estado_mercancia'] ?? '';
$observaciones = $mercancia_data['observaciones'] ?? 'Sin observaciones';

// Si en tu DB guardas la mercancía como número 0,1,2 => mapeamos:
$estado_descripciones = [
    0 => 'Sin daños',
    1 => 'Daños leves',
    2 => 'Daños graves'
];
$estado_mercancia_texto = $estado_mercancia;
if (is_numeric($estado_mercancia) && isset($estado_descripciones[$estado_mercancia])) {
    $estado_mercancia_texto = $estado_descripciones[$estado_mercancia];
} else {
    $estado_mercancia_texto = 'No especificado';
}

// 5) Firma del evento
$sql_firma = "SELECT firma, nombre_firmante, identificacion_firmante, fecha_firma FROM eventos WHERE porte_id = ? AND tipo_evento = ? LIMIT 1";
$stmt_firma = $conn->prepare($sql_firma);
$stmt_firma->bind_param("is", $porte_id, $tipo_evento);
$stmt_firma->execute();
$firma_data = $stmt_firma->get_result()->fetch_assoc();
$stmt_firma->close();

$firma = $firma_data['firma'] ?? '';
$nombre_firmante = $firma_data['nombre_firmante'] ?? 'Sin firmante';
$identificacion_firmante = $firma_data['identificacion_firmante'] ?? 'No especificada';
$fecha_firma = $firma_data['fecha_firma'] ?? 'Sin fecha';

// Arreglo base64
if (!empty($firma) && strpos($firma, 'data:image') !== 0 && !preg_match('/^\/.*\.(png|jpg|jpeg|gif)$/i', $firma)) {
    $firma = "data:image/png;base64," . $firma;
}

// 6) Información de salida
$sql_salida = "SELECT geolocalizacion_salida, hora_salida
  FROM eventos
  WHERE porte_id = ? AND tipo_evento = ?
  LIMIT 1";
$stmt_salida = $conn->prepare($sql_salida);
$stmt_salida->bind_param("is", $porte_id, $tipo_evento);
$stmt_salida->execute();
$result_salida = $stmt_salida->get_result();
$salida_data = $result_salida->fetch_assoc();
$stmt_salida->close();

$geolocalizacion_salida = $salida_data['geolocalizacion_salida'] ?? 'No registrada';
$hora_salida = $salida_data['hora_salida'] ?? 'No registrada';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Evento - <?php echo htmlspecialchars($tipo_evento); ?></title>
</head>
<body>
    <h1>Detalle de Evento (<?php echo htmlspecialchars($tipo_evento); ?>)</h1>

    <h2>Información Básica del Porte</h2>
    <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($mercancia_descripcion); ?></p>
    <p><strong>Fecha Recogida:</strong> <?php echo htmlspecialchars($fecha_recogida); ?></p>
    <p><strong>Localización Recogida:</strong> <?php echo htmlspecialchars($localizacion_recogida); ?></p>
    <p><strong>Localización Entrega:</strong> <?php echo htmlspecialchars($localizacion_entrega); ?></p>

    <hr>
    <h2>Información de Llegada</h2>
    <p><strong>Geolocalización Llegada:</strong> <?php echo htmlspecialchars($geolocalizacion_llegada); ?></p>
    <p><strong>Hora de Llegada:</strong> <?php echo htmlspecialchars($hora_llegada); ?></p>

    <hr>
    <h2>Documentos Multimedia</h2>
    <table border="1" cellpadding="8">
        <tr>
            <th>Fecha/Hora</th>
            <th>Tipo Archivo</th>
            <th>Ver</th>
        </tr>
        <?php if ($result_multimedia->num_rows > 0): ?>
            <?php while($row = $result_multimedia->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date("d-m-Y H:i:s", strtotime($row['timestamp'])); ?></td>
                    <td><?php echo ucfirst($row['tipo_archivo']); ?></td>
                    <td><a href="<?php echo $row['url_archivo']; ?>" target="_blank">Abrir</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3">No se han subido archivos todavía.</td></tr>
        <?php endif; ?>
    </table>

    <hr>
    <h2>Estado de la Mercancía</h2>
    <p><strong>Estado:</strong> <?php echo htmlspecialchars($estado_mercancia_texto); ?></p>
    <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($observaciones); ?></p>

    <hr>
    <?php
    if (!empty($firma)) {
        // Si la firma es una ruta (empieza con "/firmas/"), agregar la URL completa
        if (strpos($firma, '/') === 0) {
            $firma = "https://intertrucker.net" . $firma;
        }
    }
    ?>

    <h2>Firma</h2>
    <p><strong>Firmante:</strong> <?php echo htmlspecialchars($nombre_firmante); ?></p>
    <p><strong>ID Firmante:</strong> <?php echo htmlspecialchars($identificacion_firmante); ?></p>
    <p><strong>Fecha Firma:</strong> <?php echo htmlspecialchars($fecha_firma); ?></p>

    <?php if (!empty($firma)): ?>
        <img src="<?php echo htmlspecialchars($firma); ?>" alt="Firma" width="300" height="150">
    <?php else: ?>
        <p>Sin firma registrada</p>
    <?php endif; ?>


    <hr>
    <h2>Información de Salida</h2>
    <p><strong>Geolocalización Salida:</strong> <?php echo htmlspecialchars($geolocalizacion_salida); ?></p>
    <p><strong>Hora de Salida:</strong> <?php echo htmlspecialchars($hora_salida); ?></p>

    <!-- Botón "Volver Atrás" -->
    <hr>
    <p>
      <a href="javascript:history.back();">Volver Atrás</a>
    </p>

</body>
</html>

<?php
$conn->close();
?>
