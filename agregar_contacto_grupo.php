<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

if (isset($_GET['grupo_id']) && isset($_GET['contacto_id'])) {
    $grupo_id = $_GET['grupo_id'];
    $contacto_id = $_GET['contacto_id'];

    // Verificar si es una entidad o un contacto (usuario)
    $es_entidad = isset($_GET['es_entidad']) ? $_GET['es_entidad'] : false;

    // Verificar que el contacto_id o entidad_id existe
    if (!$es_entidad) {
        // Verificar si el contacto existe en la tabla usuarios
        $sql_verificar = "SELECT id FROM usuarios WHERE id = ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("i", $contacto_id);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->get_result();

        if ($resultado->num_rows == 0) {
            // No existe el contacto
            echo "<p>Error: El contacto con ID $contacto_id no existe en la tabla de usuarios.</p>";
            exit();
        }
    }

    if ($es_entidad) {
        // Si es una entidad, insertar en la columna entidad_id y dejar contacto_id como NULL
        $sql_insert = "INSERT INTO grupo_contactos (grupo_id, contacto_id, entidad_id) VALUES (?, NULL, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $grupo_id, $contacto_id);  // contacto_id es NULL, entidad_id se llena
    } else {
        // Si es un contacto (usuario), insertar en la columna contacto_id y dejar entidad_id como NULL
        $sql_insert = "INSERT INTO grupo_contactos (grupo_id, contacto_id, entidad_id) VALUES (?, ?, NULL)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $grupo_id, $contacto_id);  // entidad_id es NULL, contacto_id se llena
    }

    // Ejecutar la consulta
    if ($stmt_insert->execute()) {
        // Redirigir de vuelta a ver_grupo.php automáticamente
        header("Location: ver_grupo.php?grupo_id=" . $grupo_id);
        exit();
    } else {
        // Mostrar el error específico
        echo "<p>Error al agregar el contacto o la entidad: " . $stmt_insert->error . "</p>";
    }
} else {
    echo "<p>Error: Datos no recibidos.</p>";
}
?>
