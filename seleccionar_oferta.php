<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo "Error: Usuario no autenticado.";
    exit;
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado

// Capturar los valores recibidos desde POST
$porte_id = $_POST['porte_id'] ?? null;
$ofertas_varios_id = $_POST['ofertas_varios_id'] ?? null;
$ofertas_externas_id = $_POST['ofertas_externas_id'] ?? null;

// Depuración para verificar valores de POST
echo "Porte ID recibido: " . htmlspecialchars($porte_id ?? 'No recibido') . "<br>";
echo "Ofertas Varios ID recibido: " . htmlspecialchars($ofertas_varios_id ?? 'No recibido') . "<br>";
echo "Ofertas Externas ID recibido: " . htmlspecialchars($ofertas_externas_id ?? 'No recibido') . "<br>";

// Verificar si se ha recibido el ID del porte y de la oferta específica (ofertas_varios_id o ofertas_externas_id)
if ($porte_id && ($ofertas_varios_id || $ofertas_externas_id)) {
    
    // Determinar el tipo de oferta en función de cuál ID se ha recibido
    $is_varios = !empty($ofertas_varios_id);
    $oferta_id = $is_varios ? $ofertas_varios_id : $ofertas_externas_id;
    
    // Mostrar valores recibidos desde POST para depuración
    echo "Porte ID desde POST: " . htmlspecialchars($porte_id) . "<br>";
    echo "Oferta ID desde POST: " . htmlspecialchars($oferta_id) . "<br>";
    echo "Tabla de origen: " . ($is_varios ? "ofertas_varios" : "ofertas_externas") . "<br>";
    
    // Paso 1: Actualizar todas las ofertas aceptadas del mismo porte_id a "no_seleccionado" en ambas tablas
    $sql_update_ofertas_varios = "UPDATE ofertas_varios SET estado_oferta = 'no_seleccionado' WHERE porte_id = ? AND estado_oferta = 'aceptado'";
    $stmt_update_varios = $conn->prepare($sql_update_ofertas_varios);
    $stmt_update_varios->bind_param("i", $porte_id);
    $stmt_update_varios->execute();
    $stmt_update_varios->close();

    $sql_update_ofertas_externas = "UPDATE ofertas_externas SET estado = 'no_seleccionado' , token= null  WHERE porte_id = ? AND estado = 'aceptado'";
    $stmt_update_externas = $conn->prepare($sql_update_ofertas_externas);
    $stmt_update_externas->bind_param("i", $porte_id);
    $stmt_update_externas->execute();
    $stmt_update_externas->close();

    // Paso 2: Actualizar el estado de la oferta seleccionada a "seleccionado" en la tabla correspondiente
    if ($is_varios) {
        $sql_seleccionar_oferta = "UPDATE ofertas_varios SET estado_oferta = 'seleccionado' WHERE id = ? AND porte_id = ? ";
    } else {
       $sql_seleccionar_oferta = "UPDATE ofertas_externas SET estado = 'seleccionado' WHERE id = ? AND porte_id = ? ";
    }

    $stmt_seleccionar_oferta = $conn->prepare($sql_seleccionar_oferta);
    $stmt_seleccionar_oferta->bind_param("ii", $oferta_id, $porte_id);
    $stmt_seleccionar_oferta->execute();

    // Verificar si se realizó la actualización
    if ($stmt_seleccionar_oferta->affected_rows > 0) {
        echo "<p>La oferta ha sido seleccionada exitosamente.</p>";
    } else {
        echo "<p>Error: No se pudo seleccionar la oferta. Verifique que esté en estado 'aceptado'.</p>";
    }

    $stmt_seleccionar_oferta->close();
} else {
    echo "<p>Error: No se recibió el ID de la oferta o el ID del porte.</p>";
}

// Cerrar la conexión a la base de datos
$conn->close();

echo "<a href='hacer_oferta.php?porte_id=" . htmlspecialchars($porte_id) . "'><button>Volver a Hacer Oferta</button></a>";
?>
