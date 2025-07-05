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

// Verificar si el tren tiene un camionero asignado
$sql_camionero = "
    SELECT 
        u.id AS camionero_id, 
        u.nombre_usuario AS nombre, 
        u.apellidos AS apellidos 
    FROM 
        tren_camionero tc
    JOIN 
        camioneros c ON tc.camionero_id = c.id
    JOIN 
        usuarios u ON c.usuario_id = u.id
    WHERE 
        tc.tren_id = ? 
        AND tc.fin_tren_camionero IS NULL 
    LIMIT 1
";
$stmt_camionero = $conn->prepare($sql_camionero);
$stmt_camionero->bind_param("i", $tren_id);
$stmt_camionero->execute();
$result_camionero = $stmt_camionero->get_result();
$camionero = $result_camionero->fetch_assoc();
$camionero_id = $camionero ? $camionero['camionero_id'] : null;
$nombre_camionero = $camionero ? $camionero['nombre'] . " " . $camionero['apellidos'] : "Camionero no asignado";

// Consulta SQL para obtener los portes asociados al tren, ordenados por ID desc
$sql_portes = "
    SELECT p.id AS porte_id,
           p.mercancia_descripcion,
           p.tipo_palet,
           p.cantidad,
           p.peso_total,
           p.volumen_total,
           p.mercancia_conservacion,
           p.estado_recogida_entrega,
           p.localizacion_recogida,
           p.localizacion_entrega,
           p.fecha_recogida,
           p.fecha_entrega
    FROM porte_tren pt
    JOIN portes p ON pt.porte_id = p.id
    WHERE pt.tren_id = ? 
      AND pt.fin_tren IS NULL
    ORDER BY p.id DESC
";
$stmt_portes = $conn->prepare($sql_portes);
$stmt_portes->bind_param("i", $tren_id);
$stmt_portes->execute();
$result_portes = $stmt_portes->get_result();

// Guardamos los portes en un array para mostrarlos tanto en tabla (vista escritorio) como en tarjetas (vista móvil)
$portes = [];
while ($row = $result_portes->fetch_assoc()) {
    $portes[] = $row;
}

$stmt_tren->close();
$stmt_camionero->close();
$stmt_portes->close();
$conn->close();

// Contar cuántos portes tienen estado "Pendiente"
$portes_pendientes = 0;
foreach ($portes as $p) {
    if (isset($p['estado_recogida_entrega']) && $p['estado_recogida_entrega'] === 'Pendiente') {
        $portes_pendientes++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Portes del Tren</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Vincula tus estilos globales si los tienes -->
    <link rel="stylesheet" href="styles.css">

    <style>
        /* ==================== ESTILOS GENERALES ==================== */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2 {
            margin: 10px 0;
        }
        button {
            cursor: pointer;
        }

        /* Enlaces y botones de acciones */
        .actions {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .actions a,
        .actions button {
            padding: 10px;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            font-weight: bold;
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
        .actions a:hover,
        .actions button:hover {
            opacity: 0.9;
        }

        /* Opciones desplegables */
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

        /* ==================== VISTA ESCRITORIO (TABLA) ==================== */
        .desktop-view {
            display: block; /* visible en pantallas grandes */
        }
        .desktop-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .desktop-table th,
        .desktop-table td {
            padding: 8px;
            border: 1px solid #ccc;
            vertical-align: middle;
        }
        .desktop-table th {
            background-color: #f8f8f8;
        }

        /* ==================== VISTA MÓVIL (TARJETAS) ==================== */
        .mobile-view {
            display: none; /* se mostrará solo en móvil con media query */
            margin-top: 20px;
        }
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

        /* ==================== MEDIA QUERIES ==================== */
        @media (max-width: 768px) {
            /* Ocultamos la tabla en pantallas pequeñas y mostramos la vista tarjeta */
            .desktop-view {
                display: none;
            }
            .mobile-view {
                display: block;
            }
        }
    </style>
<link rel='stylesheet' href='/header.css'>
<script src='/header.js'></script>
</head>
<body>
<?php require_once $_SERVER["DOCUMENT_ROOT"]."/header.php"; ?>


    <h1>Listado de Portes del Tren</h1>
    <h2>Nombre del Tren: <?php echo htmlspecialchars($nombre_tren); ?></h2>
    <h2>Camionero Asignado: <?php echo htmlspecialchars($nombre_camionero); ?></h2>
    <!-- Mostramos cuántos portes están en estado 'Pendiente' -->
    <h2>Portes Pendientes: <?php echo $portes_pendientes; ?></h2>

    <!-- ==================== VISTA ESCRITORIO (TABLA) ==================== -->
    <div class="desktop-view">
        <?php if (count($portes) > 0): ?>
            <table class="desktop-table">
                <thead>
                    <tr>
                        <th>ID Porte</th>
                        <th>Mercancía</th>
                        <th>Tipo de Palet</th>
                        <th>Cantidad</th>
                        <th>Peso Total</th>
                        <th>Volumen Total</th>
                        <th>Estado</th>
                        <th>Recogida (Fecha)</th>
                        <th>Entrega (Fecha)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($portes as $porte): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($porte['porte_id']); ?></td>
                        <td><?php echo htmlspecialchars($porte['mercancia_descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($porte['tipo_palet']); ?></td>
                        <td><?php echo htmlspecialchars($porte['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars($porte['peso_total']); ?></td>
                        <td><?php echo htmlspecialchars($porte['volumen_total']); ?></td>
                        <td><?php echo htmlspecialchars($porte['estado_recogida_entrega']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($porte['localizacion_recogida']); ?>
                            <br>
                            <strong><?php echo htmlspecialchars($porte['fecha_recogida']); ?></strong>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($porte['localizacion_entrega']); ?>
                            <br>
                            <strong><?php echo htmlspecialchars($porte['fecha_entrega']); ?></strong>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if ($camionero_id): ?>
                                    <a href="recogida_entrega_vista.php?porte_id=<?php echo $porte['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>&usuario_id=<?php echo $usuario_id; ?>&tipo_evento=recogida" class="recogida">
                                        Recogida
                                    </a>
                                    <a href="recogida_entrega_vista.php?porte_id=<?php echo $porte['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>&usuario_id=<?php echo $usuario_id; ?>&tipo_evento=entrega" class="entrega">
                                        Entrega
                                    </a>
                                <?php else: ?>
                                    <p style="font-size: 0.9rem;">Sin camionero</p>
                                <?php endif; ?>
                                <button onclick="toggleOptions(<?php echo $porte['porte_id']; ?>, false)">
                                    Más Opciones
                                </button>
                            </div>
                            <!-- Opciones desplegables -->
                            <div id="options-desktop-<?php echo $porte['porte_id']; ?>" class="options">
                                <a href="detalle_porte.php?id=<?php echo $porte['porte_id']; ?>">Ver Detalles</a>
                                <a href="cambiar_tren.php?porte_id=<?php echo $porte['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>">Cambiar de Tren</a>
                                <a href="hacer_oferta.php?porte_id=<?php echo $porte['porte_id']; ?>">Ofrecer Porte</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron portes asociados a este tren.</p>
        <?php endif; ?>
    </div>

    <!-- ==================== VISTA MÓVIL (TARJETAS) ==================== -->
    <div class="mobile-view">
        <?php if (count($portes) > 0): ?>
            <?php foreach ($portes as $porte): ?>
                <div class="card">
                    <h3>ID Porte: <?php echo htmlspecialchars($porte['porte_id']); ?></h3>
                    <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($porte['mercancia_descripcion']); ?></p>
                    <p><strong>Tipo de Palet:</strong> <?php echo htmlspecialchars($porte['tipo_palet']); ?></p>
                    <p><strong>Cantidad:</strong> <?php echo htmlspecialchars($porte['cantidad']); ?></p>
                    <p><strong>Peso Total:</strong> <?php echo htmlspecialchars($porte['peso_total']); ?></p>
                    <p><strong>Volumen Total:</strong> <?php echo htmlspecialchars($porte['volumen_total']); ?></p>
                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($porte['estado_recogida_entrega']); ?></p>
                    <p>
                        <strong>Recogida:</strong> 
                        <?php echo htmlspecialchars($porte['localizacion_recogida']); ?> 
                        - 
                        <strong>Fecha:</strong> 
                        <?php echo htmlspecialchars($porte['fecha_recogida']); ?>
                    </p>
                    <p>
                        <strong>Entrega:</strong> 
                        <?php echo htmlspecialchars($porte['localizacion_entrega']); ?> 
                        - 
                        <strong>Fecha:</strong> 
                        <?php echo htmlspecialchars($porte['fecha_entrega']); ?>
                    </p>
                    <div class="actions">
                        <?php if ($camionero_id): ?>
                            <a href="recogida_entrega_vista.php?porte_id=<?php echo $porte['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>&usuario_id=<?php echo $usuario_id; ?>&tipo_evento=recogida" class="recogida">
                                Recogida
                            </a>
                            <a href="recogida_entrega_vista.php?porte_id=<?php echo $porte['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>&usuario_id=<?php echo $usuario_id; ?>&tipo_evento=entrega" class="entrega">
                                Entrega
                            </a>
                        <?php else: ?>
                            <p>No se ha asignado un camionero a este tren.</p>
                        <?php endif; ?>
                        <button onclick="toggleOptions(<?php echo $porte['porte_id']; ?>, true)">
                            Más Opciones
                        </button>
                    </div>
                    <div id="options-mobile-<?php echo $porte['porte_id']; ?>" class="options">
                        <a href="detalle_porte.php?id=<?php echo $porte['porte_id']; ?>">Ver Detalles</a>
                        <a href="cambiar_tren.php?porte_id=<?php echo $porte['porte_id']; ?>&tren_id=<?php echo $tren_id; ?>">Cambiar de Tren</a>
                        <a href="hacer_oferta.php?porte_id=<?php echo $porte['porte_id']; ?>">Ofrecer Porte</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron portes asociados a este tren.</p>
        <?php endif; ?>
    </div>

    <button 
        onclick="window.location.href='portes_trucks.php'"
        style="padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; margin-top: 20px;">
        Volver al listado de trenes
    </button>

    <script>
        /**
         * Muestra/oculta las opciones "Más Opciones" 
         * @param {number} porteId 
         * @param {boolean} isMobile 
         */
        function toggleOptions(porteId, isMobile) {
            // Usamos un ID diferente según si es móvil o escritorio
            const optionsId = isMobile 
                ? `options-mobile-${porteId}` 
                : `options-desktop-${porteId}`;
            const options = document.getElementById(optionsId);

            if (options) {
                options.style.display = (options.style.display === 'none' || !options.style.display) 
                    ? 'flex' 
                    : 'none';
            }
        }
    </script>

</body>
</html>
