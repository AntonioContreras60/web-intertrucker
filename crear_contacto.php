<?php
session_start();
include 'conexion.php'; // Incluye la conexión a la base de datos

// Habilitar visualización de errores para diagnosticar el problema
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el parámetro y la sesión existen
if (isset($_GET['añadir_contacto']) && isset($_SESSION['usuario_id'])) {
    // Sanear los inputs de usuario para evitar inyecciones SQL
    $contacto_usuario_id = intval($_GET['añadir_contacto']); // ID del usuario que es el contacto
    $usuario_id = intval($_SESSION['usuario_id']); // ID del usuario actual (el que añade el contacto)

    // Asegurarse de que los IDs no sean valores vacíos o inválidos
    if ($contacto_usuario_id > 0 && $usuario_id > 0) {
        // Preparar la consulta para insertar en la tabla contactos
        $sql = "INSERT INTO contactos (usuario_id, contacto_usuario_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            // Mostrar el error si la preparación de la consulta falla
            echo "Error en la preparación de la consulta: " . $conn->error;
            exit();
        }

        // Vincular parámetros para prevenir inyección SQL
        $stmt->bind_param("ii", $usuario_id, $contacto_usuario_id);

        // Ejecutar la consulta y manejar el resultado
        if ($stmt->execute()) {
            echo "Contacto añadido correctamente.";
        } else {
            echo "Error al añadir el contacto: " . $stmt->error;
        }

        // Cerrar la declaración
        $stmt->close();
    } else {
        echo "Error: ID de usuario o contacto no válido.";
    }
} else {
    echo "Error: Datos insuficientes para añadir contacto.";
}

// Cerrar la conexión a la base de datos
$conn->close();
?>
