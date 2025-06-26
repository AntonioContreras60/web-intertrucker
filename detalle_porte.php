<?php  
session_start();
include 'conexion.php';

// Habilitar la visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Obtener el ID del porte desde la URL
$porte_id = $_GET['id'];

// Verificar que el porte_id esté presente
if (!isset($porte_id) || !is_numeric($porte_id)) {
    die("Error: ID no válido.");
}

// Consultar toda la información del porte incluyendo usuarios y entidades
$sql_porte = "SELECT p.mercancia_descripcion, p.fecha_recogida, p.localizacion_recogida, p.localizacion_entrega, 
                     p.cantidad, p.peso_total, p.volumen_total, 
                     p.mercancia_conservacion, p.tipo_carga, p.observaciones, 
                     p.tipo_palet, 
                     COALESCE(eu.nombre_usuario, ee.nombre) AS expedidor_nombre, 
                     COALESCE(du.nombre_usuario, de.nombre) AS destinatario_nombre, 
                     COALESCE(cu.nombre_usuario, ce.nombre) AS cliente_nombre
              FROM portes p
              LEFT JOIN usuarios eu ON p.expedidor_usuario_id = eu.id
              LEFT JOIN entidades ee ON p.expedidor_entidad_id = ee.id
              LEFT JOIN usuarios du ON p.destinatario_usuario_id = du.id
              LEFT JOIN entidades de ON p.destinatario_entidad_id = de.id
              LEFT JOIN usuarios cu ON p.cliente_usuario_id = cu.id
              LEFT JOIN entidades ce ON p.cliente_entidad_id = ce.id
              WHERE p.id = ?";

// Preparar la consulta
$stmt_porte = $conn->prepare($sql_porte);

// Verificar si la preparación de la consulta falló
if ($stmt_porte === false) {
    die("Error en la preparación de la consulta SQL: " . $conn->error);
}

// Bind de parámetros
$stmt_porte->bind_param("i", $porte_id);

// Ejecutar la consulta
$stmt_porte->execute();

// Obtener el resultado
$result_porte = $stmt_porte->get_result();

if ($result_porte->num_rows > 0) {
    $porte = $result_porte->fetch_assoc();
} else {
    die("Error: No se encontraron detalles del porte.");
}

// Consultar quién te ofreció el porte (desde la tabla ofertas_varios)
$sql_oferta = "SELECT u.nombre_usuario AS nombre_ofertante
               FROM ofertas_varios o
               JOIN usuarios u ON o.ofertante_id = u.id
               WHERE o.porte_id = ? AND o.usuario_id = ?";
$stmt_oferta = $conn->prepare($sql_oferta);

// Verificar si la preparación de la consulta falló
if ($stmt_oferta === false) {
    die("Error en la preparación de la consulta de ofertas: " . $conn->error);
}

// Bind de parámetros para obtener el usuario que ofreció el porte
$stmt_oferta->bind_param("ii", $porte_id, $_SESSION['usuario_id']);
$stmt_oferta->execute();

// Obtener el resultado
$result_oferta = $stmt_oferta->get_result();

if ($result_oferta->num_rows > 0) {
    $oferta = $result_oferta->fetch_assoc();
    $nombre_ofertante = $oferta['nombre_ofertante'];
} else {
    $nombre_ofertante = "Desconocido";
}

// Consultar el tren y el camionero asignado al porte, obteniendo los nombres del camionero desde la tabla usuarios
$sql_tren_camionero = "SELECT tc.tren_id, tc.camionero_id, t.tren_nombre, 
                              u.nombre_usuario AS nombre_camionero, 
                              u.apellidos AS apellidos_camionero
                       FROM tren_camionero tc
                       JOIN tren t ON tc.tren_id = t.id
                       JOIN camioneros c ON tc.camionero_id = c.id
                       JOIN usuarios u ON c.usuario_id = u.id
                       JOIN porte_tren pt ON pt.tren_id = tc.tren_id
                       WHERE pt.porte_id = ? 
                       AND tc.fin_tren_camionero IS NULL
                       LIMIT 1";
$stmt_tren_camionero = $conn->prepare($sql_tren_camionero);
$stmt_tren_camionero->bind_param("i", $porte_id);
$stmt_tren_camionero->execute();
$result_tren_camionero = $stmt_tren_camionero->get_result();

if ($result_tren_camionero->num_rows > 0) {
    $tren_camionero = $result_tren_camionero->fetch_assoc();
    $tren_nombre = $tren_camionero['tren_nombre'];
    $nombre_camionero = $tren_camionero['nombre_camionero'] . ' ' . $tren_camionero['apellidos_camionero'];
} else {
    $tren_nombre = "No asignado";
    $nombre_camionero = "No asignado";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Porte</title>
    <style>
        /* Estilos generales para el diseño responsivo */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #444;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-item strong {
            display: inline-block;
            width: 140px;
            color: #555;
        }

        /* Estilos para móviles */
        @media (max-width: 600px) {
            .detail-item strong {
                display: block;
                width: auto;
                margin-bottom: 5px;
            }

            .detail-item {
                font-size: 14px;
            }
        }

        button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detalles del Porte</h1>
          <div class="detail-item">
            <strong>ID del Porte:</strong> <?php echo htmlspecialchars($porte_id); ?>
        </div>
        <div class="detail-item">
            <strong>Mercancía:</strong> <?php echo $porte['mercancia_descripcion']; ?>
        </div>
        <div class="detail-item">
            <strong>Fecha de recogida:</strong> <?php echo $porte['fecha_recogida']; ?>
        </div>
        <div class="detail-item">
            <strong>Lugar de recogida:</strong> <?php echo $porte['localizacion_recogida']; ?>
        </div>
        <div class="detail-item">
            <strong>Lugar de entrega:</strong> <?php echo $porte['localizacion_entrega']; ?>
        </div>
        <div class="detail-item">
            <strong>Cantidad:</strong> <?php echo $porte['cantidad']; ?>
        </div>
        <div class="detail-item">
            <strong>Peso total:</strong> <?php echo $porte['peso_total']; ?> kg
        </div>
        <div class="detail-item">
            <strong>Volumen total:</strong> <?php echo $porte['volumen_total']; ?> m³
        </div>
        <div class="detail-item">
            <strong>Condiciones de conservación:</strong> <?php echo $porte['mercancia_conservacion']; ?>
        </div>
        <div class="detail-item">
            <strong>Tipo de carga:</strong> <?php echo $porte['tipo_carga']; ?>
        </div>
        <div class="detail-item">
            <strong>Observaciones:</strong> <?php echo $porte['observaciones']; ?>
        </div>
        <div class="detail-item">
            <strong>Tipo de Palet:</strong> <?php echo ucfirst($porte['tipo_palet']); ?>
        </div>
        <div class="detail-item">
            <strong>Expedidor:</strong> <?php echo $porte['expedidor_nombre']; ?>
        </div>
        <div class="detail-item">
            <strong>Destinatario:</strong> <?php echo $porte['destinatario_nombre']; ?>
        </div>
        <div class="detail-item">
            <strong>Cliente:</strong> <?php echo $porte['cliente_nombre']; ?>
        </div>
        <div class="detail-item">
            <strong>Ofrecido por:</strong> <?php echo $nombre_ofertante; ?>
        </div>

        <!-- Historial de ofertas donde participó el usuario -->
        <h2>Historial de Ofertas</h2>
        <?php
// Consultar el historial de ofertas relacionadas con el usuario de sesión o sus compañeros
$sql_historial = "SELECT o.ofertante_id, u.nombre_usuario AS nombre_ofertante, o.usuario_id, u2.nombre_usuario AS nombre_aceptante, 
                         o.estado_oferta, o.fecha_oferta, o.precio
                  FROM ofertas_varios o
                  JOIN usuarios u ON o.ofertante_id = u.id
                  JOIN usuarios u2 ON o.usuario_id = u2.id
                  WHERE o.porte_id = ?
                  AND (u.admin_id = ? OR u2.admin_id = ?)
                  ORDER BY o.fecha_oferta ASC";

$stmt_historial = $conn->prepare($sql_historial);
$stmt_historial->bind_param("iii", $porte_id, $_SESSION['admin_id'], $_SESSION['admin_id']);
$stmt_historial->execute();
$result_historial = $stmt_historial->get_result();

if ($result_historial->num_rows > 0) {
    echo "<div>";
    while ($historial = $result_historial->fetch_assoc()) {
        echo "<div>
                <p><strong>Fecha:</strong> " . htmlspecialchars($historial['fecha_oferta']) . "
                <strong>Ofertante:</strong> " . htmlspecialchars($historial['nombre_ofertante']) . "
                <strong>Receptor:</strong> " . htmlspecialchars($historial['nombre_aceptante']) . "
                <strong>Estado:</strong> " . htmlspecialchars($historial['estado_oferta']) . "
                <strong>Precio:</strong> " . htmlspecialchars(number_format($historial['precio'], 2)) . " €</p>
                <hr>
              </div>";
    }
    echo "</div>";
} else {
    echo "<p>No hay historial de ofertas en las que hayas participado o tus compañeros hayan participado para este porte.</p>";
}



$stmt_historial->close();


        ?>
        <h2>Tren y Camionero</h2>
        <div class="detail-item">
            <strong>Tren:</strong> <?php echo htmlspecialchars($tren_nombre); ?>
        </div>
        <div class="detail-item">
            <strong>Camionero:</strong> <?php echo htmlspecialchars($nombre_camionero); ?>
        </div>
        <button onclick="window.history.back()">Volver</button>
    </div>
</body>
</html>
