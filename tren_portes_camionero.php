<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'] ?? null;

// Inicializar variables
$tren_id = null;
$camionero_id = null;
$nombre_tren = "Tren no encontrado";
$nombre_camionero = "Desconocido";

// Verificar camionero asociado al usuario y obtener su nombre
$stmt_camionero = $conn->prepare("
    SELECT c.id, u.nombre_usuario 
    FROM camioneros c
    JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.usuario_id = ?
");
if (!$stmt_camionero) {
    die("Error en la consulta camionero: " . $conn->error);
}
$stmt_camionero->bind_param('i', $usuario_id);
$stmt_camionero->execute();
$result_camionero = $stmt_camionero->get_result();

if ($result_camionero->num_rows > 0) {
    $camionero = $result_camionero->fetch_assoc();
    $camionero_id = $camionero['id'];
    $nombre_camionero = $camionero['nombre_usuario']; // Campo correcto obtenido desde la tabla `usuarios`
} else {
    die("Error: No se encontró el perfil de camionero para este usuario.");
}
$stmt_camionero->close();

// Buscar el último tren activo
$stmt_tren = $conn->prepare("
    SELECT tren_id 
    FROM tren_camionero 
    WHERE camionero_id = ? AND fin_tren_camionero IS NULL 
    ORDER BY inicio_tren_camionero DESC 
    LIMIT 1
");
if (!$stmt_tren) {
    die("Error en la consulta tren activo: " . $conn->error);
}
$stmt_tren->bind_param('i', $camionero_id);
$stmt_tren->execute();
$result_tren = $stmt_tren->get_result();

if ($result_tren->num_rows > 0) {
    $tren = $result_tren->fetch_assoc();
    $tren_id = $tren['tren_id'];
} else {
    die("Error: No tienes ningún tren activo asignado actualmente.");
}
$stmt_tren->close();

// Obtener el nombre del tren
$stmt_tren_nombre = $conn->prepare("SELECT tren_nombre FROM tren WHERE id = ?");
if (!$stmt_tren_nombre) {
    die("Error en la consulta nombre del tren: " . $conn->error);
}
$stmt_tren_nombre->bind_param("i", $tren_id);
$stmt_tren_nombre->execute();
$result_tren_nombre = $stmt_tren_nombre->get_result();
$tren_info = $result_tren_nombre->fetch_assoc();
$nombre_tren = $tren_info ? $tren_info['tren_nombre'] : "Nombre del tren no encontrado";
$stmt_tren_nombre->close();

// Incluir el header con el nombre del camionero
$nombre_camionero_header = $nombre_camionero;
include 'header_camionero.php';

// Consulta para obtener portes activos
$sql_portes = "
    SELECT p.id AS porte_id, p.mercancia_descripcion, p.tipo_palet, p.cantidad, 
           p.peso_total, p.volumen_total, p.estado_recogida_entrega, 
           p.localizacion_recogida, p.fecha_recogida, 
           p.localizacion_entrega, p.fecha_entrega
    FROM porte_tren pt
    JOIN portes p ON pt.porte_id = p.id
    WHERE pt.tren_id = ? AND pt.fin_tren IS NULL
";
$stmt_portes = $conn->prepare($sql_portes);
if (!$stmt_portes) {
    die("Error en la consulta portes activos: " . $conn->error);
}
$stmt_portes->bind_param("i", $tren_id);
$stmt_portes->execute();
$result_portes = $stmt_portes->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Portes del Tren</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 10px 0;
            padding: 15px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            margin-top: 0;
        }

        .actions a {
            display: inline-block;
            margin: 5px 10px 0 0;
            padding: 8px 12px;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        .actions a.recogida {
            background-color: #ffc107;
        }

        .actions a.entrega {
            background-color: #17a2b8;
        }

        .actions a.detalle {
            background-color: #28a745;
        }

        .actions a:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <h2>Tren: <?php echo htmlspecialchars($nombre_tren); ?></h2>

    <?php if ($result_portes->num_rows > 0): ?>
        <?php while ($row = $result_portes->fetch_assoc()): ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
                <p><strong>Tipo de Palet:</strong> <?php echo htmlspecialchars($row['tipo_palet'] ?: 'Ninguno'); ?></p>
                <p><strong>Recogida:</strong> <?php echo htmlspecialchars($row['localizacion_recogida'] . ' - ' . $row['fecha_recogida']); ?></p>
                <p><strong>Entrega:</strong> <?php echo htmlspecialchars($row['localizacion_entrega'] . ' - ' . $row['fecha_entrega']); ?></p>
                <p><strong>Estado:</strong> <?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></p>
                <div class="actions">
                    <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>&usuario_id=<?php echo $usuario_id; ?>&tipo_evento=recogida" class="recogida">Recogida</a>
                    <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>&usuario_id=<?php echo $usuario_id; ?>&tipo_evento=entrega" class="entrega">Entrega</a>
                    <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>" class="detalle">Detalles</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No se encontraron portes asociados a este tren.</p>
    <?php endif; ?>

    <?php $stmt_portes->close(); $conn->close(); ?>
</body>
</html>
