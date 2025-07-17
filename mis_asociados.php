<?php
require_once __DIR__.'/auth.php';
require_login();
include 'conexion.php'; // Conexión a la base de datos

$usuario_id = $_SESSION['usuario_id']; // Usuario en sesión

// Filtro por nombre/apellidos (opcional)
$filtro = "";
if (isset($_POST['filtro']) && $_POST['filtro'] !== "") {
    $filtro = $_POST['filtro'];
    $sql = "
        SELECT 
            u.id AS usuario_id,
            u.nombre_usuario,
            u.apellidos,
            u.email,
            c.id AS camionero_id,
            c.tipo_carnet,
            c.num_licencia,
            c.activo
        FROM usuarios u
        JOIN camioneros c ON c.usuario_id = u.id
        WHERE u.admin_id = (SELECT admin_id FROM usuarios WHERE id = ?)
          AND u.rol = 'asociado'
          AND (u.nombre_usuario LIKE ? OR u.apellidos LIKE ?)
    ";
    $stmt = $conn->prepare($sql);
    $search = '%' . $filtro . '%';
    $stmt->bind_param("iss", $usuario_id, $search, $search);
} else {
    $sql = "
        SELECT 
            u.id AS usuario_id,
            u.nombre_usuario,
            u.apellidos,
            u.email,
            c.id AS camionero_id,
            c.tipo_carnet,
            c.num_licencia,
            c.activo
        FROM usuarios u
        JOIN camioneros c ON c.usuario_id = u.id
        WHERE u.admin_id = (SELECT admin_id FROM usuarios WHERE id = ?)
          AND u.rol = 'asociado'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
}

if (!$stmt->execute()) {
    die("Error al consultar asociados: " . $stmt->error);
}
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Asociados</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1rem;
        }
        thead {
            background-color: #f5f5f5;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
        }
        .activo {
            background-color: #d4edda;
        }
        .no-activo {
            background-color: #f8d7da;
        }
        .btn {
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
        }
        .btn-camion {
            background-color: #007bff;
        }
        .btn-editar {
            background-color: #28a745;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<h1>Asociados</h1>
<br>
<a href="agregar_asociado.php">➕ Añadir nuevo asociado</a><br>
<form method="POST" action="mis_asociados.php">
    <label for="filtro">Buscar por nombre o apellidos:</label>
    <input type="text" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
    <button type="submit">Buscar</button>
</form>

<?php if ($resultado->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Tipo Carnet</th>
                <th>Licencia</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($asociado = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($asociado['nombre_usuario'] . ' ' . $asociado['apellidos']) ?></td>
                <td><?= htmlspecialchars($asociado['email']) ?></td>
                <td><?= htmlspecialchars($asociado['tipo_carnet']) ?></td>
                <td><?= htmlspecialchars($asociado['num_licencia']) ?></td>
                <td class="<?= $asociado['activo'] ? 'activo' : 'no-activo' ?>">
                    <?= $asociado['activo'] ? 'Activo' : 'Inactivo' ?>
                </td>
                <td>
                    <a href="vehiculo_asociado.php?usuario_id=<?= $asociado['usuario_id'] ?>" class="btn btn-camion">Vehículo</a>
                    <a href="editar_camionero.php?id=<?= $asociado['camionero_id'] ?>" class="btn btn-editar">Editar</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No se encontraron asociados.</p>
<?php endif; ?>



</body>
</html>
