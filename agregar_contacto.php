<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

if (isset($_POST['contacto_id'])) {
    $contacto_usuario_id = $_POST['contacto_id']; // Este es el ID del contacto que estás añadiendo
    $usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado

    // Insertar contacto en la tabla con los nombres de columnas correctos
    $sql = "INSERT INTO contactos (usuario_id, contacto_usuario_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "Error al preparar la consulta: " . $conn->error;
        exit();
    }

    $stmt->bind_param("ii", $usuario_id, $contacto_usuario_id);

    if ($stmt->execute()) {
        echo "Contacto añadido correctamente.";
    } else {
        echo "Error al añadir el contacto.";
    }

    // Cerrar la declaración y la conexión
    $stmt->close();
    $conn->close();

    // Redirigir de vuelta a My Network
    header("Location: my_network.php");
    exit();
}
?>
