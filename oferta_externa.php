<?php
/* 
include 'conexion.php';

// Obtener el token de la URL
if (!isset($_GET['token'])) {
    echo "Error: Token no proporcionado.";
    exit;
}

$token = $_GET['token'];

// Buscar la oferta externa correspondiente al token
$sql_oferta_externa = "SELECT * FROM ofertas_externas WHERE token = ? AND estado = 'pendiente'";
$stmt_oferta_externa = $conn->prepare($sql_oferta_externa);
$stmt_oferta_externa->bind_param("s", $token);
$stmt_oferta_externa->execute();
$result_oferta_externa = $stmt_oferta_externa->get_result();

if ($result_oferta_externa->num_rows > 0) {
    $oferta_externa = $result_oferta_externa->fetch_assoc();
    // Mostrar los detalles del porte al destinatario
    echo "<h3>Detalles del Porte</h3>";
    echo "<p>ID del Porte: " . htmlspecialchars($oferta_externa['porte_id']) . "</p>";
    // Mostrar más información relevante...

    // Formulario para aceptar o rechazar la oferta
    echo "<form method='POST' action='oferta_externa.php?token=" . htmlspecialchars($token) . "'>";
    echo "<button type='submit' name='aceptar'>Aceptar Oferta</button>";
    echo "<button type='submit' name='rechazar'>Rechazar Oferta</button>";
    echo "</form>";

} else {
    echo "Error: Oferta inválida o ya no está disponible.";
    exit;
}
$stmt_oferta_externa->close();

// Manejar la aceptación o rechazo de la oferta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aceptar'])) {
        // Actualizar el estado de la oferta externa a 'aceptada'
        $sql_aceptar = "UPDATE ofertas_externas SET estado = 'aceptada', fecha_aceptacion = NOW() WHERE token = ?";
        $stmt_aceptar = $conn->prepare($sql_aceptar);
        $stmt_aceptar->bind_param("s", $token);
        $stmt_aceptar->execute();
        echo "<p>Oferta aceptada. El ofertante será notificado.</p>";
    } elseif (isset($_POST['rechazar'])) {
        // Actualizar el estado de la oferta externa a 'rechazada'
        $sql_rechazar = "UPDATE ofertas_externas SET estado = 'rechazada' WHERE token = ?";
        $stmt_rechazar = $conn->prepare($sql_rechazar);
        $stmt_rechazar->bind_param("s", $token);
        $stmt_rechazar->execute();
        echo "<p>Oferta rechazada. Gracias por su respuesta.</p>";
    }
}
?> 
*/

