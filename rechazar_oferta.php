<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo "Error: Usuario no autenticado.";
    exit;
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado

// Verificar los datos recibidos
if (!isset($_POST['oferta_id'])) {
    echo "<p>Error: No se recibió el identificador de la oferta.</p>";
    echo "<a href='portes_nuevos_recibidos.php'><button>Volver</button></a>";
    exit;
}

$oferta_id = intval($_POST['oferta_id']);

// Actualizar el estado de la oferta a "rechazado"
try {
    // Preparar la consulta para cambiar el estado a 'rechazado'
    $sql_rechazar = "UPDATE ofertas_varios SET estado_oferta = 'rechazado' WHERE id = ? AND usuario_id = ?";
    $stmt_rechazar = $conn->prepare($sql_rechazar);

    if ($stmt_rechazar === false) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    // Vincular los parámetros y ejecutar la consulta
    $stmt_rechazar->bind_param("ii", $oferta_id, $usuario_id);
    $stmt_rechazar->execute();

    if ($stmt_rechazar->affected_rows > 0) {
        // Mostrar mensaje de éxito
        echo "<p>La oferta fue rechazada exitosamente.</p>";
    } else {
        throw new Exception("No se pudo rechazar la oferta. Es posible que la oferta no exista o ya haya sido procesada.");
    }

    // Cerrar la declaración
    $stmt_rechazar->close();
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Cerrar la conexión
$conn->close();
?>

<!-- Botón para volver -->
<a href="portes_nuevos_recibidos.php"><button>Volver</button></a>

<link rel="stylesheet" href="styles.css">
