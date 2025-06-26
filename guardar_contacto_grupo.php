<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

// Verificar si se recibió el grupo y los contactos
if (isset($_POST['grupo_id']) && isset($_POST['contactos'])) {
    $grupo_id = $_POST['grupo_id'];
    $contactos = $_POST['contactos']; // Array con IDs de los contactos seleccionados

    // Iterar sobre cada contacto seleccionado para agregarlo al grupo
    foreach ($contactos as $contacto_id) {
        // Preparar la consulta para insertar el contacto en el grupo
        $sql_insert = "INSERT INTO grupo_contactos (grupo_id, contacto_id) VALUES (?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $grupo_id, $contacto_id);
        $stmt_insert->execute();
    }

    // Redirigir de nuevo a la página del grupo
    header("Location: ver_grupo.php?grupo_id=" . $grupo_id);
    exit();
} else {
    echo "<p>Error: No se recibieron contactos o grupo.</p>";
}
?>
