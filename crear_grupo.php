<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    echo "Error: No has iniciado sesión.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_grupo = $_POST['nombre'];
    $usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado

    // Verificar si el grupo ya existe (sin distinción entre mayúsculas y minúsculas)
    $sql_verificar = "SELECT id FROM grupos WHERE LOWER(nombre) = LOWER(?)";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("s", $nombre_grupo);
    $stmt_verificar->execute();
    $stmt_verificar->store_result();

    if ($stmt_verificar->num_rows > 0) {
        // Si el nombre del grupo ya existe, mostrar un mensaje de error
        $mensaje_error = "El nombre del grupo ya existe. Por favor, elige otro nombre.";
    } else {
        // Insertar el nuevo grupo
        $sql = "INSERT INTO grupos (nombre, usuario_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nombre_grupo, $usuario_id);

        if ($stmt->execute()) {
            $mensaje_exito = "Grupo creado correctamente.";
        } else {
            $mensaje_error = "Error al crear el grupo: " . $stmt->error;
        }

        // Cerrar la declaración de inserción
        $stmt->close();
    }

    // Cerrar la declaración de verificación
    $stmt_verificar->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Grupo</title>
</head>
<body>

<h1>Crear un Nuevo Grupo</h1>

<!-- Mostrar mensajes de error o éxito -->
<?php if (isset($mensaje_error)): ?>
    <p style="color: red;"><?= $mensaje_error ?></p>
<?php endif; ?>

<?php if (isset($mensaje_exito)): ?>
    <p style="color: green;"><?= $mensaje_exito ?></p>
<?php endif; ?>

<!-- Formulario para crear grupo -->
<form method="POST" action="crear_grupo.php">
    <label for="nombre">Nombre del Grupo:</label><br>
    <input type="text" name="nombre" required><br><br>
    <button type="submit">Crear Grupo</button>
</form>

<!-- Botón para regresar a "My Network" -->
<br>
<form action="my_network.php" method="get">
    <button type="submit">Volver a My Network</button>
</form>

</body>
</html>
