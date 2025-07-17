<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(["administrador","gestor"]);
// Asegúrate de incluir la conexión a la base de datos
include 'conexion.php';
$usuario_id = $_SESSION["usuario_id"];

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos enviados del formulario
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $direccion = $conn->real_escape_string($_POST['direccion']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $email = $conn->real_escape_string($_POST['email']);
    $cif = $conn->real_escape_string($_POST['cif']);
    $tipo = isset($_POST['tipo']) ? $conn->real_escape_string($_POST['tipo']) : null;
    $observaciones = isset($_POST['observaciones']) ? $conn->real_escape_string($_POST['observaciones']) : null;

    // Insertar los datos en la tabla 'entidades'
    $sql = "INSERT INTO entidades (nombre, direccion, telefono, email, cif, tipo, observaciones, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssssssi", $nombre, $direccion, $telefono, $email, $cif, $tipo, $observaciones, $usuario_id);
        if ($stmt->execute()) {
            echo "Entidad creada correctamente.";
        } else {
            echo "Error al crear la entidad: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
    }

    // Cerrar la conexión
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Entidad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div>
        <?php if (isset($mensaje)) echo $mensaje; ?>
        <br><a href="my_network.php">Volver a My Network</a>
    </div>
</body>
</html>
