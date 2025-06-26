<?php 
session_start();
include 'conexion.php';

// Obtener el ID del porte desde la URL
$porte_id = $_GET['id'];

// Verificar que el porte_id esté presente
if (!isset($porte_id) || empty($porte_id)) {
    die("Error: El porte_id no está presente en la URL.");
}

// Consultar la información del porte
$sql_porte = "SELECT mercancia_descripcion, fecha_recogida, localizacion_recogida, localizacion_entrega 
              FROM portes WHERE id = ?";
$stmt_porte = $conn->prepare($sql_porte);
$stmt_porte->bind_param("i", $porte_id);
$stmt_porte->execute();
$result_porte = $stmt_porte->get_result();

if ($result_porte->num_rows > 0) {
    $porte = $result_porte->fetch_assoc();
    echo "<h1>Detalles del Porte</h1>";
    echo "<strong>Mercancía:</strong> " . $porte['mercancia_descripcion'] . "<br>";
    echo "<strong>Fecha de recogida:</strong> " . $porte['fecha_recogida'] . "<br>";
    echo "<strong>Lugar de recogida:</strong> " . $porte['localizacion_recogida'] . "<br>";
    echo "<strong>Lugar de entrega:</strong> " . $porte['localizacion_entrega'] . "<br><br>";
} else {
    echo "No se encontraron detalles del porte.";
}

// Consultar los detalles de la oferta
$sql_ofrecidos = "SELECT u.nombre AS nombre_usuario, o.estado_oferta, o.id AS oferta_id
                  FROM ofertas_varios o
                  JOIN usuarios u ON o.usuario_id = u.id
                  WHERE o.porte_id = ? AND o.ofertante_id = ?";
$stmt_ofrecidos = $conn->prepare($sql_ofrecidos);
$stmt_ofrecidos->bind_param("ii", $porte_id, $_SESSION['usuario_id']);
$stmt_ofrecidos->execute();
$result_ofrecidos = $stmt_ofrecidos->get_result();

if ($result_ofrecidos->num_rows > 0) {
    echo "<h2>Ofrecido a los siguientes usuarios:</h2>";
    echo "<form method='post' action='seleccionar_oferta.php' id='form_seleccionar'>"; // Formulario

    // Enviar el porte_id
    echo "<input type='hidden' name='porte_id' value='$porte_id'>";

    while ($row = $result_ofrecidos->fetch_assoc()) {
        $nombre_usuario = $row['nombre_usuario']; // Nombre del usuario
        $oferta_id = $row['oferta_id'];

        // Mostrar el estado de la oferta
        echo "<li>" . $nombre_usuario . " - Estado: " . $row['estado_oferta'];

        // Enviar el id de la oferta seleccionada y las demás aceptadas
        if ($row['estado_oferta'] == 'aceptado') {
            echo "<input type='hidden' name='ofertas_aceptadas[]' value='" . $oferta_id . "'>";
        }

        // Mostrar botón "Seleccionar" solo para los usuarios que aceptaron
        if ($row['estado_oferta'] == 'aceptado') {
            // Cambiamos el onclick para incluir el nombre del usuario
            echo "<button type='button' onclick='confirmarSeleccion(\"$nombre_usuario\", \"$oferta_id\");'>Seleccionar</button>";
        }

        echo "</li>";
    }

    echo "</form>";
} else {
    echo "<p>No se ha ofrecido a ningún usuario todavía.</p>";
}
?>

<!-- Botón para volver a la página anterior -->
<button onclick="window.history.back()">Volver</button>

<!-- Confirmación antes de seleccionar una oferta con el nombre del usuario -->
<script>
function confirmarSeleccion(nombreUsuario, ofertaId) {
    var confirmacion = confirm("¿Estás seguro de que deseas seleccionar a " + nombreUsuario + "?");
    if (confirmacion) {
        // Si el usuario confirma, enviar el formulario con la oferta seleccionada
        var form = document.getElementById('form_seleccionar');
        var inputOfertaId = document.createElement('input');
        inputOfertaId.setAttribute('type', 'hidden');
        inputOfertaId.setAttribute('name', 'oferta_id');
        inputOfertaId.setAttribute('value', ofertaId);
        form.appendChild(inputOfertaId);
        form.submit();
    }
}
</script>
