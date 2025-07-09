<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo "No se encontró la sesión de usuario. Por favor, inicia sesión.";
    exit();
}

include '../conexion.php';

// Mostrar errores (solo para depuración; quítalo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tomamos el usuario_id de la sesión
$usuario_id = (int)$_SESSION['usuario_id'];

// Preparar la consulta con placeholders
$sql_direcciones = "
    SELECT id, nombre_via, numero, complemento, ciudad, estado_provincia,
           codigo_postal, pais, tipo_direccion
    FROM direcciones
    WHERE usuario_id = ?
";
$stmt = $conn->prepare($sql_direcciones);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_direcciones = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Direcciones</title>
</head>
<body>
    <h1>Gestionar mis Direcciones</h1>

    <?php if ($result_direcciones->num_rows > 0): ?>
        <ul>
            <?php while ($direccion = $result_direcciones->fetch_assoc()): ?>
                <li>
                    Calle: <?= htmlspecialchars($direccion['nombre_via']) . ", " . htmlspecialchars($direccion['numero']) ?><br>
                    <?= !empty($direccion['complemento'])
                         ? "Complemento: " . htmlspecialchars($direccion['complemento']) . "<br>" 
                         : '' ?>
                    Ciudad: <?= htmlspecialchars($direccion['ciudad']) ?><br>
                    Estado/Provincia: <?= htmlspecialchars($direccion['estado_provincia']) ?><br>
                    Código Postal: <?= htmlspecialchars($direccion['codigo_postal']) ?><br>
                    País: <?= htmlspecialchars($direccion['pais']) ?><br>
                    Tipo de Dirección:
                    <?= $direccion['tipo_direccion'] === 'fiscal' ? 'Fiscal' : 'Recogida/Entrega' ?><br>
                    <a href="modificar_direccion.php?id=<?= $direccion['id'] ?>">Modificar</a> |
                    <a href="eliminar_direccion.php?id=<?= $direccion['id'] ?>"
                       onclick="return confirm('¿Seguro que quieres eliminar esta dirección?');">
                       Eliminar
                    </a>
                </li>
                <hr>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No tienes direcciones registradas.</p>
    <?php endif; ?>

    <!-- Enlace para añadir nueva dirección -->
    <a href="añadir_direccion_usuario.php?usuario_id=<?= $usuario_id ?>">Añadir Nueva Dirección</a><br><br>

    <!-- Botón para volver al perfil del usuario -->
    <button onclick="window.location.href='perfil_usuario.php'"
            style="padding: 10px 20px; background-color: #007bff; color: white;
                   border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
        Volver al Perfil
    </button>
</body>
</html>
<?php
$stmt->close();
$conn->close();
