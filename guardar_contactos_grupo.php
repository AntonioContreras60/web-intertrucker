<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

if (isset($_POST['contactos']) && isset($_GET['grupo_id'])) {
    $grupo_id = $_GET['grupo_id'];

    foreach ($_POST['contactos'] as $contacto_id) {
        // Insertar los contactos seleccionados en el grupo
        $sql = "INSERT INTO grupo_contactos (grupo_id, contacto_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $grupo_id, $contacto_id);
        $stmt->execute();
    }

    // Redirigir de vuelta a la página del grupo
    header("Location: ver_grupo.php?grupo_id=" . $grupo_id);
    exit();
}
?>
