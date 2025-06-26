<?php
include '../conexion.php';
session_start();

// Mostrar errores (para depuración; quita en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si se llegó por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir y sanitizar datos
    $nombre_usuario  = $_POST['nombre_usuario']; // O “nombre_empresa”? Ajusta según tu forma real
    $email           = $_POST['email'];
    $contrasena      = $_POST['contrasena'];
    $tipo_usuario    = $_POST['tipo_usuario'];
    $telefono        = $_POST['telefono'];
    $cif             = $_POST['cif'];

    // Encriptar la contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Sentencia preparada
    $sql = "INSERT INTO usuarios (nombre_usuario, email, contrasena, tipo_usuario, telefono, cif)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    // Enlazar parámetros (todos string => "ssssss")
    $stmt->bind_param("ssssss",
        $nombre_usuario,
        $email,
        $contrasena_hash,
        $tipo_usuario,
        $telefono,
        $cif
    );

    // Ejecutar
    if ($stmt->execute()) {
        // Obtener el ID del nuevo usuario
        $usuario_id = $stmt->insert_id;

        // Redirigir a “añadir_direccion_usuario.php” con ?usuario_id=...
        header("Location: añadir_direccion_usuario.php?usuario_id=" . $usuario_id);
        exit();
    } else {
        echo "Error al registrar el usuario: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Método no permitido o falta de datos.";
}

$conn->close();
?>
