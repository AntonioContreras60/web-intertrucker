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

$usuario_id = $_SESSION['usuario_id']; // ID del usuario en sesión

// Obtener el tren_id de la URL
$tren_id = isset($_GET['tren_id']) ? intval($_GET['tren_id']) : null;

if (!$tren_id) {
    die("Error: ID de tren no proporcionado.");
}

// Obtener el nombre del tren
$sql_tren = "SELECT tren_nombre FROM tren WHERE id = ?";
$stmt_tren = $conn->prepare($sql_tren);
$stmt_tren->bind_param("i", $tren_id);
$stmt_tren->execute();
$result_tren = $stmt_tren->get_result();
$tren_info = $result_tren->fetch_assoc();
$nombre_tren = $tren_info ? $tren_info['tren_nombre'] : "Nombre del tren no encontrado";

// Consulta SQL para obtener los portes asociados al tren
$sql = "
    SELECT p.id AS porte_id, p.mercancia_descripcion, p.tipo_palet, p.cantidad, 
           p.peso_total, p.volumen_total, p.mercancia_conservacion, p.estado_recogida_entrega,
           p.localizacion_recogida, p.localizacion_entrega, p.fecha_recogida, p.fecha_entrega
    FROM porte_tren pt
    JOIN portes p ON pt.porte_id = p.id
    WHERE pt.tren_id = ? AND pt.fin_tren IS NULL
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tren_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si el tren tiene un camionero asignado
$sql_camionero = "
    SELECT c.id AS camionero_id, c.nombre, c.apellidos 
    FROM tren_camionero tc 
    JOIN camioneros c ON tc.camionero_id = c.id 
    WHERE tc.tren_id = ? AND tc.fin_tren_camionero IS NULL LIMIT 1";
$stmt_camionero = $conn->prepare($sql_camionero);
$stmt_camionero->bind_param("i", $tren_id);
$stmt_camionero->execute();
$result_camionero = $stmt_camionero->get_result();
$camionero = $result_camionero->fetch_assoc();
$camionero_id = $camionero ? $camionero['camionero_id'] : null;
$nombre_camionero = $camionero ? $camionero['nombre'] . " " . $camionero['apellidos'] : "Camionero no asignado";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Portes del Tren</title>
    <link rel="stylesheet" href="styles.css"> <!-- Vincula tus estilos -->
    <style>
        .card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card h3, .card p {
            margin: 5px 0;
        }
        .actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .actions a, .actions button {
            padding: 10px;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            min-width: 100px;
        }
        .actions a.recogida {
            background-color: #ffc107;
        }
        .actions a.entrega {
            background-color: #17a2b8;
        }
        .actions button {
            background-color: #6c757d;
            font-weight: normal;
            padding: 8px;
            min-width: 80px;
            color: #e0e0e0;
        }
        .actions a:hover, .actions button:hover {
            opacity: 0.9;
        }
        .options {
            margin-top: 10px;
            display: none;
            gap: 10px;
        }
        .options a {
            padding: 10px;
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .options a:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; ?>

    <h1>Listado de Portes del Tren</h1>
    <h2>Nombre del Tren: <?php echo htmlspecialchars($nombre_tren); ?></h2>
    <h2>Camionero Asignado: <?php echo htmlspecialchars($nombre_camionero); ?></h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card">
                <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
                <p><strong>Tipo de Palet:</strong> <?php echo htmlspecialchars($row['tipo_palet']); ?></p>
                <p><strong>Nº Palets:</strong> <?php echo htmlspecialchars($row['cantidad']); ?></p>
                <p><strong>Peso Total:</strong> <?php echo htmlspecialchars($row['peso_total']); ?></p>
                <p><strong>Volumen Total:</strong> <?php echo htmlspecialchars($row['volumen_total']); ?></p>
                <p><strong>Estado:</strong> <?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></p>
                <p><strong>Recogida:</strong> <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - <strong>Fecha:</strong> <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
                <p><strong>Entrega:</strong> <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - <strong>Fecha:</strong> <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
                <div class="actions">
                    <?php if ($camionero_id): ?>
                        <a href="recogida_entrega.php?porte_id=<?php echo $row['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>&usuario_id=<?php echo $usuario_id; ?>&tipo_evento=recogida" class="recogida">Recogida</a>
                        <a href="recogida_entrega.php?porte_id=<?php echo $row['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>&usuario_id=<?php echo $usuario_id; ?>&tipo_evento=entrega" class="entrega">Entrega</a>
                    <?php else: ?>
                        <p>No se ha asignado un camionero a este tren.</p>
                    <?php endif; ?>
                    <a href="javascript:void(0);" onclick="toggleOptions(<?php echo $row['porte_id']; ?>)" style="color: #6c757d; font-weight: normal; text-decoration: underline;">Más Opciones</a>
                    <div id="options-<?php echo $row['porte_id']; ?>" class="options">
                        <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>">Ver Detalles</a>
                        <a href="cambiar_tren.php?porte_id=<?php echo $row['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>">Cambiar de Tren</a>
                        <a href="hacer_oferta.php?porte_id=<?php echo $row['porte_id']; ?>">Ofrecer Porte</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No se encontraron portes asociados a este tren.</p>
    <?php endif; ?>

    <button onclick="window.location.href='portes_trucks.php'" style="padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Volver al listado de trenes</button>

    <script>
        function toggleOptions(porteId) {
            const options = document.getElementById(options-${porteId});
            options.style.display = options.style.display === 'none' ? 'flex' : 'none';
        }
    </script>

    <?php
    $stmt->close();
    $stmt_camionero->close();
    $conn->close();
    ?>
</body>
</html>