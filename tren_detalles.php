<?php
session_start();
include 'conexion.php';

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica si el usuario está en sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay un usuario en sesión.");
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario en sesión

// Verificar el rol y admin_id del usuario
$sql_role_check = "
    SELECT rol, admin_id
    FROM usuarios
    WHERE id = ?
";
if (!$stmt = $conn->prepare($sql_role_check)) {
    die("Error en la preparación de la consulta del usuario: " . $conn->error);
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

// Verifica si el usuario tiene rol permitido
if (!in_array($rol, ['administrador', 'gestor'])) {
    die("Acceso denegado: Solo administradores o gestores pueden acceder a esta página.");
}

// Verifica si se ha pasado el tren_id como parámetro
if (!isset($_GET['tren_id'])) {
    die("Error: No se ha especificado el tren.");
}

$tren_id = $_GET['tren_id'];

// Consulta SQL para obtener los detalles del tren dentro de la misma empresa (admin_id)
$sql_tren = "
    SELECT t.*
    FROM tren t
    JOIN tren_vehiculos tv ON tv.tren_id = t.id
    JOIN vehiculos v ON tv.vehiculo_id = v.id
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE t.id = ? AND u.admin_id = ?
";
if (!$stmt_tren = $conn->prepare($sql_tren)) {
    die("Error en la preparación de la consulta del tren: " . $conn->error);
}
$stmt_tren->bind_param("ii", $tren_id, $admin_id);
$stmt_tren->execute();
$result_tren = $stmt_tren->get_result();

// Verifica si se encontró el tren y pertenece a la empresa
if ($result_tren->num_rows === 0) {
    die("Error: Tren no encontrado o no pertenece a esta empresa.");
}

$tren = $result_tren->fetch_assoc();

// Consulta SQL para obtener los vehículos asociados al tren dentro de la misma empresa
$sql_vehiculos = "
    SELECT v.id, v.matricula, v.marca, v.modelo, v.capacidad, v.ano_fabricacion, v.nivel_1, v.nivel_2
    FROM tren_vehiculos tv
    JOIN vehiculos v ON tv.vehiculo_id = v.id
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE tv.tren_id = ? AND u.admin_id = ?
";
if (!$stmt_vehiculos = $conn->prepare($sql_vehiculos)) {
    die("Error en la preparación de la consulta de vehículos: " . $conn->error);
}
$stmt_vehiculos->bind_param("ii", $tren_id, $admin_id);
$stmt_vehiculos->execute();
$result_vehiculos = $stmt_vehiculos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Tren</title>
</head>
<body>

<h1>Detalles del Tren: <?php echo htmlspecialchars($tren['tren_nombre']); ?></h1>

<p><strong>ID del Tren:</strong> <?php echo htmlspecialchars($tren['id']); ?></p>
<p><strong>Nombre del Tren:</strong> <?php echo htmlspecialchars($tren['tren_nombre']); ?></p>

<h2>Vehículos Asociados</h2>

<?php if ($result_vehiculos->num_rows > 0): ?>
    <table border="1">
        <thead>
            <tr>
                <th>Matrícula</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Nivel 1</th>
                <th>Nivel 2</th>
                <th>Capacidad</th>
                <th>Año de Fabricación</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($vehiculo = $result_vehiculos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($vehiculo['matricula']); ?></td>
                    <td><?php echo htmlspecialchars($vehiculo['marca']); ?></td>
                    <td><?php echo htmlspecialchars($vehiculo['modelo']); ?></td>
                    <td><?php echo htmlspecialchars($vehiculo['nivel_1']); ?></td>
                    <td><?php echo htmlspecialchars($vehiculo['nivel_2']); ?></td>
                    <td><?php echo htmlspecialchars($vehiculo['capacidad']); ?></td>
                    <td><?php echo htmlspecialchars($vehiculo['ano_fabricacion']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No hay vehículos asociados a este tren.</p>
<?php endif; ?>
<br>
<!-- Opción para modificar el tren -->
<form action="modificar_tren.php" method="POST" style="display:inline;">
    <input type="hidden" name="tren_id" value="<?php echo $tren['id']; ?>">
    <button type="submit" style="margin-right: 10px;">Modificar Tren</button>
</form>

<!-- Botón Volver -->
<button onclick="window.history.back();">Volver</button>

</body>
</html>
