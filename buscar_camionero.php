<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario está en sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No se ha definido el ID del usuario en la sesión.");
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario en sesión

// Verificar si el tren_id y camionero_id actual están en la URL
if (!isset($_GET['tren_id']) || !isset($_GET['camionero_id'])) {
    die("Error: No se recibieron los parámetros necesarios.");
}

$tren_id = (int)$_GET['tren_id'];
$camionero_actual_id = (int)$_GET['camionero_id'];

// --- Obtener el admin_id del usuario ---
$sql_admin_id = "SELECT admin_id FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql_admin_id);
if (!$stmt) {
    die("Error al obtener el admin_id: " . $conn->error);
}
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Error: Usuario no encontrado.");
}
$admin_id = $result->fetch_assoc()['admin_id'];
$stmt->close();

// --- Validar que el tren pertenece al mismo admin_id ---
$sql_validar_tren = "
    SELECT t.id 
    FROM tren t
    JOIN tren_camionero tc ON t.id = tc.tren_id
    JOIN camioneros c ON tc.camionero_id = c.id
    JOIN usuarios u ON c.usuario_id = u.id
    WHERE t.id = ? AND u.admin_id = ?
";
$stmt = $conn->prepare($sql_validar_tren);
if (!$stmt) {
    die("Error en la validación del tren: " . $conn->error);
}
$stmt->bind_param("ii", $tren_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: El tren no pertenece a la empresa o no existe.");
}
$stmt->close();

// --- Consulta para obtener los camioneros activos del mismo admin_id ---
$sql_camioneros = "
    SELECT 
        c.id, 
        u.nombre_usuario, 
        u.apellidos 
    FROM 
        camioneros c
    JOIN 
        usuarios u ON c.usuario_id = u.id
    WHERE 
        u.admin_id = ? 
        AND c.activo = 1 
        AND c.id != ?
";

$stmt = $conn->prepare($sql_camioneros);
if (!$stmt) {
    die("Error en la preparación de la consulta SQL: " . $conn->error);
}
$stmt->bind_param("ii", $admin_id, $camionero_actual_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Camionero</title>
    <link rel="stylesheet" href="styles.css"> <!-- Vincula tus estilos -->
</head>
<body>

    <h1>Buscar Camionero</h1>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Seleccionar</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nombre_usuario'] . ' ' . $row['apellidos']); ?></td>
                        <td>
                            <a href="actualizar_camionero.php?tren_id=<?php echo $tren_id; ?>&nuevo_camionero_id=<?php echo $row['id']; ?>&camionero_anterior_id=<?php echo $camionero_actual_id; ?>">
                                Seleccionar
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No se encontraron camioneros activos disponibles.</p>
    <?php endif; ?>

    <!-- Botón para volver a la lista de portes -->
    <br>
    <a href="portes_trucks.php" class="button">Volver a la Lista de Portes</a>

    <?php
    // Cerrar la conexión
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
