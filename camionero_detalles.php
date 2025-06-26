<?php
session_start();
include 'conexion.php'; // Incluye la conexión a la base de datos

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario está en sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No se ha definido el ID del usuario en la sesión.");
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario en sesión

// --- Verificar el rol y admin_id del usuario ---
$sql_role_check = "
    SELECT rol, admin_id
    FROM usuarios
    WHERE id = ?
";
$stmt = $conn->prepare($sql_role_check);
if (!$stmt) {
    die("Error al preparar la consulta de verificación de rol: " . $conn->error);
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

// Verificar si el usuario tiene rol permitido
if (!in_array($rol, ['administrador', 'gestor'])) {
    die("Acceso denegado: Solo administradores o gestores pueden acceder a esta página.");
}

// --- Verificar parámetros recibidos ---
if (!isset($_GET['camionero_id']) || !isset($_GET['tren_id'])) {
    die("Error: No se recibieron los parámetros necesarios.");
}

$camionero_id = (int)$_GET['camionero_id'];
$tren_id = (int)$_GET['tren_id'];

// --- Consulta para obtener los detalles del camionero ---
$sql = "
    SELECT 
        u.nombre_usuario AS nombre, 
        u.apellidos, 
        u.telefono, 
        u.email, 
        c.tipo_carnet, 
        c.num_licencia, 
        c.fecha_caducidad, 
        c.caducidad_profesional, 
        c.fecha_nacimiento, 
        c.fecha_contratacion, 
        c.activo, 
        t.tren_nombre
    FROM 
        tren t
    JOIN 
        tren_camionero tc ON t.id = tc.tren_id
    JOIN 
        camioneros c ON tc.camionero_id = c.id
    JOIN 
        usuarios u ON c.usuario_id = u.id
    WHERE 
        c.id = ? AND t.id = ? AND u.admin_id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error . " | SQL: " . $sql);
}
$stmt->bind_param("iii", $camionero_id, $tren_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: No se encontraron detalles para el camionero o no tienes acceso a estos datos.");
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Camionero</title>
    <link rel="stylesheet" href="styles.css"> <!-- Enlace a tus estilos -->
</head>
<body>
    <?php include 'header.php'; ?> <!-- Incluir el menú de navegación -->

    <h1>Detalles del Camionero</h1>
    <h2>Tren: <?php echo htmlspecialchars($row['tren_nombre']); ?></h2>

    <table border="1">
        <tr><th>Nombre</th><td><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellidos']); ?></td></tr>
        <tr><th>Tipo de Carnet</th><td><?php echo htmlspecialchars($row['tipo_carnet']); ?></td></tr>
        <tr><th>Número de Licencia</th><td><?php echo htmlspecialchars($row['num_licencia']); ?></td></tr>
        <tr><th>Fecha de Caducidad</th><td><?php echo htmlspecialchars($row['fecha_caducidad']); ?></td></tr>
        <tr><th>Caducidad Profesional</th><td><?php echo htmlspecialchars($row['caducidad_profesional']); ?></td></tr>
        <tr><th>Fecha de Nacimiento</th><td><?php echo htmlspecialchars($row['fecha_nacimiento']); ?></td></tr>
        <tr><th>Fecha de Contratación</th><td><?php echo htmlspecialchars($row['fecha_contratacion']); ?></td></tr>
        <tr><th>Teléfono</th><td><?php echo htmlspecialchars($row['telefono']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($row['email']); ?></td></tr>
        <tr><th>Activo</th><td><?php echo $row['activo'] ? 'Sí' : 'No'; ?></td></tr>
    </table>

    <br>
    <!-- Enlace para cambiar de camionero -->
    <a href="buscar_camionero.php?tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $camionero_id; ?>" class="button">Cambiar Camionero</a>

    <br><br>
    <!-- Botón para volver a la lista de portes -->
    <a href="portes_trucks.php" class="button">Volver a la Lista de Portes</a>

    <?php
    // Cerrar conexión
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
