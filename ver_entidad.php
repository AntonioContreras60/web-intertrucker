<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

// Obtener el ID de la entidad desde la URL
$entidad_id = $_GET['entidad_id'];
$usuario_id = $_SESSION['usuario_id'];

// Obtener los detalles de la entidad
$sql_entidad = "SELECT nombre, direccion, telefono, email, cif, tipo, observaciones 
                FROM entidades 
                WHERE id = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql_entidad);
$stmt->bind_param("ii", $entidad_id, $usuario_id);
$stmt->execute();
$entidad = $stmt->get_result()->fetch_assoc();

// Guardar los cambios en la entidad
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $cif = $_POST['cif'];
    $tipo = $_POST['tipo'];
    $observaciones = $_POST['observaciones'];

    // Actualizar los detalles de la entidad en la base de datos
    $sql_actualizar_entidad = "UPDATE entidades 
                               SET nombre = ?, direccion = ?, telefono = ?, email = ?, cif = ?, tipo = ?, observaciones = ? 
                               WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql_actualizar_entidad);
    $stmt->bind_param("ssssssiii", $nombre, $direccion, $telefono, $email, $cif, $tipo, $observaciones, $entidad_id, $usuario_id);
    $stmt->execute();

    // Redirigir para evitar reenvío del formulario
    header("Location: ver_entidad.php?entidad_id=$entidad_id&guardado=1");
    exit();
}

// Eliminar entidad
if (isset($_GET['eliminar_entidad'])) {
    $sql_eliminar = "DELETE FROM entidades WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql_eliminar);
    $stmt->bind_param("ii", $entidad_id, $usuario_id);
    $stmt->execute();

    // Redirigir a My Network después de eliminar la entidad
    header("Location: my_network.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de la Entidad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <h1>Detalles de la Entidad</h1>

    <!-- Mensaje de éxito tras guardar -->
    <?php if (isset($_GET['guardado']) && $_GET['guardado'] == 1): ?>
        <p style="color: green;">Cambios guardados con éxito.</p>
    <?php endif; ?>

    <!-- Formulario para editar los detalles de la entidad -->
    <form method="POST" action="ver_entidad.php?entidad_id=<?= $entidad_id ?>">
        <label for="nombre"><strong>Nombre:</strong></label><br>
        <input type="text" name="nombre" value="<?= $entidad['nombre'] ?>" required><br>

        <label for="direccion"><strong>Dirección:</strong></label><br>
        <input type="text" name="direccion" value="<?= $entidad['direccion'] ?>"><br>

        <label for="telefono"><strong>Teléfono:</strong></label><br>
        <input type="tel" name="telefono" value="<?= $entidad['telefono'] ?>"><br>

        <label for="email"><strong>Email:</strong></label><br>
        <input type="email" name="email" value="<?= $entidad['email'] ?>"><br>

        <label for="cif"><strong>CIF/NIF:</strong></label><br>
        <input type="text" name="cif" value="<?= $entidad['cif'] ?>" required><br>

        <label for="tipo"><strong>Tipo:</strong></label><br>
        <input type="text" name="tipo" value="<?= $entidad['tipo'] ?>"><br>

        <label for="observaciones"><strong>Observaciones:</strong></label><br>
        <textarea name="observaciones" rows="5" cols="40"><?= $entidad['observaciones'] ?></textarea><br>

        <button type="submit">Guardar Cambios</button>
    </form>

    <!-- Opciones de Copiar y Eliminar -->
    <button onclick="copyEntidad()">Copiar Información</button>
    <a href="ver_entidad.php?entidad_id=<?= $entidad_id ?>&eliminar_entidad=1" 
       onclick="return confirm('¿Estás seguro de que quieres eliminar esta entidad?');">Eliminar Entidad</a>

    <!-- Botón para volver a My Network -->
    <br><br>
<!-- Botón para volver a Todos los Contactos -->
<br><br>
<a href="todos_contactos.php" class="button">Cerrar</a>

    <script>
        // Función para copiar los detalles de la entidad al portapapeles
        function copyEntidad() {
            let entidadInfo = "Nombre: <?= $entidad['nombre'] ?>\nDirección: <?= $entidad['direccion'] ?>\nTeléfono: <?= $entidad['telefono'] ?>\nEmail: <?= $entidad['email'] ?>\nCIF: <?= $entidad['cif'] ?>\nTipo: <?= $entidad['tipo'] ?>";
            navigator.clipboard.writeText(entidadInfo).then(() => {
                alert("Información copiada al portapapeles.");
            });
        }
    </script>

</body>
</html>
