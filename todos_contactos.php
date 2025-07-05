<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario está en sesión
if (!isset($_SESSION['usuario_id'])) {
    echo "Error: No has iniciado sesión.";
    exit();
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario en sesión

echo "<p>Usuario ID: $usuario_id</p>";

// Obtener los contactos del usuario autenticado
$sql_contactos = "SELECT u.id, u.nombre_usuario AS nombre, u.email 
                  FROM contactos 
                  JOIN usuarios u ON contactos.contacto_usuario_id = u.id
                  WHERE contactos.usuario_id = ?";
$stmt_contactos = $conn->prepare($sql_contactos);

if (!$stmt_contactos) {
    echo "Error en la preparación de la consulta de contactos: " . $conn->error;
    exit();
}

$stmt_contactos->bind_param("i", $usuario_id);

if (!$stmt_contactos->execute()) {
    echo "Error en la ejecución de la consulta de contactos: " . $stmt_contactos->error;
    exit();
}

$resultado_contactos = $stmt_contactos->get_result();

echo "<p>Número de contactos obtenidos: " . $resultado_contactos->num_rows . "</p>";

if ($resultado_contactos->num_rows === 0) {
    echo "<p>No tienes contactos de usuarios aún.</p>";
}

// Obtener todas las entidades asociadas al usuario
$sql_entidades = "SELECT id, nombre, tipo, email 
                  FROM entidades 
                  WHERE usuario_id = ?";
$stmt_entidades = $conn->prepare($sql_entidades);

if (!$stmt_entidades) {
    echo "Error en la preparación de la consulta de entidades: " . $conn->error;
    exit();
}

$stmt_entidades->bind_param("i", $usuario_id);

if (!$stmt_entidades->execute()) {
    echo "Error en la ejecución de la consulta de entidades: " . $stmt_entidades->error;
    exit();
}

$resultado_entidades = $stmt_entidades->get_result();

echo "<p>Número de contactos creados por mi: " . $resultado_entidades->num_rows . "</p>";

if ($resultado_entidades->num_rows === 0) {
    echo "<p>No tienes entidades aún.</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos mis Contactos y Entidades</title>
    <link rel="stylesheet" href="styles.css">
<link rel='stylesheet' href='/header.css'>
<script src='/header.js'></script>
</head>
<body>
<?php require_once $_SERVER["DOCUMENT_ROOT"]."/header.php"; ?>

    <h1>Todos mis Contactos</h1>

    <!-- Mostrar los contactos usuarios -->
    <h2>Contactos de Usuarios</h2>
    <?php if ($resultado_contactos->num_rows > 0): ?>
        <ul>
            <?php while ($contacto = $resultado_contactos->fetch_assoc()): ?>
                <li class="contacto">
                    <span class="nombre"><?= htmlspecialchars($contacto['nombre']) ?></span> (<?= htmlspecialchars($contacto['email']) ?>)
                    <a href="ver_contacto.php?contacto_id=<?= htmlspecialchars($contacto['id']) ?>" class="button">Ver</a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No tienes contactos de usuarios aún.</p>
    <?php endif; ?>

    <!-- Mostrar las entidades -->
    <h2>Contactos Creados por mi</h2>
    <?php if ($resultado_entidades->num_rows > 0): ?>
        <ul>
            <?php while ($entidad = $resultado_entidades->fetch_assoc()): ?>
                <li class="entidad">
                    <span class="nombre"><?= htmlspecialchars($entidad['nombre']) ?></span> (<?= htmlspecialchars($entidad['tipo']) ?>)
                    <a href="ver_entidad.php?entidad_id=<?= htmlspecialchars($entidad['id']) ?>" class="button">Ver</a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No tienes entidades aún.</p>
    <?php endif; ?>

    <!-- Enlace para volver a My Network -->
    <a href="my_network.php" class="btn">Volver a My Network</a>

</body>
</html>
