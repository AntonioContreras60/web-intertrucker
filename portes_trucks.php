<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Error: No se ha definido el ID del usuario en la sesión.");
}

$usuario_id = $_SESSION['usuario_id'];

$sql_role_check = "
    SELECT rol, admin_id
    FROM usuarios
    WHERE id = ?
";
if (!$stmt = $conn->prepare($sql_role_check)) {
    die("Error al preparar la consulta SQL: " . $conn->error);
}

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Error: Usuario no encontrado.");
}

$user_data = $result->fetch_assoc();
$rol = $user_data['rol'];
$admin_id = $user_data['admin_id'];

if (!in_array($rol, ['administrador', 'gestor'])) {
    die("Acceso denegado: Solo administradores o gestores pueden acceder a esta página.");
}

// === CONSULTA PRINCIPAL ===
$sql = "
    SELECT 
        t.id AS tren_id, 
        t.tren_nombre, 
        c.id AS camionero_id,
        u.nombre_usuario AS camionero_nombre,
        u.apellidos AS camionero_apellidos,
        (
            SELECT COUNT(*)
            FROM porte_tren pt
            JOIN portes p ON pt.porte_id = p.id
            WHERE pt.tren_id = t.id
              AND pt.fin_tren IS NULL
              AND (p.estado_recogida_entrega IS NULL OR p.estado_recogida_entrega != 'Entregado')
        ) AS portes_pendientes
    FROM 
        tren t
    JOIN 
        tren_camionero tc ON t.id = tc.tren_id
    JOIN 
        camioneros c ON tc.camionero_id = c.id
    JOIN 
        usuarios u ON c.usuario_id = u.id
    WHERE 
        u.admin_id = ?
        AND tc.fin_tren_camionero IS NULL
    ORDER BY 
        tc.inicio_tren_camionero DESC
";

if (!$stmt = $conn->prepare($sql)) {
    die("Error en la consulta SQL: " . $conn->error);
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Trenes con Portes Asignados</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">

    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        table thead {
            background-color: #f8f8f8;
        }
        table thead th {
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ccc;
        }
        table tbody tr {
            border-bottom: 1px solid #ddd;
        }
        table td {
            padding: 10px;
            vertical-align: middle;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        button {
            padding: 8px 12px;
            cursor: pointer;
            background-color: #6c757d;
            color: #fff;
            border: none;
            border-radius: 4px;
        }
        button:hover {
            opacity: 0.9;
        }

        @media (max-width: 568px) {
            th.pendientes-col,
            td.pendientes-col {
                display: none;
            }
        }
    </style>
  <link rel="stylesheet" href="/header.css">
  <script src="/header.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <h2>Listado de Trenes con Portes Asignados</h2>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre del Tren</th>
                    <th>Conductor</th>
                    <th class="pendientes-col" style="text-align:center;">Portes Sin Finalizar</th>
                    <th>Ver Portes</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="tren_detalles.php?tren_id=<?php echo $row['tren_id']; ?>">
                                <?php echo htmlspecialchars($row['tren_nombre']); ?>
                            </a>
                        </td>
                        <td>
                            <a href="camionero_detalles.php?camionero_id=<?php echo $row['camionero_id']; ?>&tren_id=<?php echo $row['tren_id']; ?>">
                                <?php echo htmlspecialchars($row['camionero_nombre'] . ' ' . $row['camionero_apellidos']); ?>
                            </a>
                        </td>
                        <td class="pendientes-col" style="text-align:center;">
                            <?php echo (int)$row['portes_pendientes']; ?>
                        </td>
                        <td>
                            <form action="tren_portes.php" method="GET" style="margin: 0;">
                                <input type="hidden" name="tren_id" value="<?php echo $row['tren_id']; ?>">
                                <button type="submit">Ver Portes</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No se encontraron trenes activos con portes asignados.</p>
    <?php endif; ?>

    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
