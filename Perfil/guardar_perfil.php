<?php
session_start();
include '../conexion.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    die("No tiene permiso para realizar esta acción.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $usuario_id = $_SESSION['usuario_id'];
    $nombre_usuario = $_POST['nombre_usuario'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $cif = $_POST['cif'];

    // Validar si los datos no están vacíos
    if (empty($nombre_usuario) || empty($apellidos) || empty($email) || empty($telefono) || empty($cif)) {
        die("Error: Todos los campos del usuario son obligatorios.");
    }

    // Actualizar información del usuario
    $sql_usuario = "UPDATE usuarios SET nombre_usuario = ?, apellidos = ?, email = ?, telefono = ?, cif = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_usuario);

    if (!$stmt) {
        die("Error al preparar la consulta de usuario: " . $conn->error);
    }

    $stmt->bind_param("sssssi", $nombre_usuario, $apellidos, $email, $telefono, $cif, $usuario_id);
    if (!$stmt->execute()) {
        die("Error al actualizar la información del usuario: " . $stmt->error);
    }

    // Verificar si se quiere convertir en camionero
    if (isset($_POST['convertir_camionero'])) {
        $tipo_carnet = $_POST['tipo_carnet'];
        $fecha_caducidad = $_POST['fecha_caducidad'];
        $num_licencia = $_POST['num_licencia'];
        $caducidad_profesional = $_POST['caducidad_profesional'];
        $fecha_contratacion = date("Y-m-d");

        // Validar datos de camionero
        if (empty($tipo_carnet) || empty($fecha_caducidad) || empty($num_licencia)) {
            die("Error: Los campos de camionero son obligatorios.");
        }

        // Verificar si el usuario ya existe en la tabla camioneros
        $sql_check = "SELECT id FROM camioneros WHERE usuario_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        if (!$stmt_check) {
            die("Error al preparar la consulta de verificación: " . $conn->error);
        }
        $stmt_check->bind_param("i", $usuario_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            // Insertar nuevo registro en la tabla camioneros
            $sql_insert = "INSERT INTO camioneros (usuario_id, tipo_carnet, fecha_caducidad, num_licencia, caducidad_profesional, fecha_contratacion, activo) 
                           VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt_insert = $conn->prepare($sql_insert);

            if (!$stmt_insert) {
                die("Error al preparar la consulta de inserción: " . $conn->error);
            }

            $stmt_insert->bind_param("isssss", $usuario_id, $tipo_carnet, $fecha_caducidad, $num_licencia, $caducidad_profesional, $fecha_contratacion);
            if ($stmt_insert->execute()) {
                echo "Usuario creado correctamente como camionero.";
            } else {
                die("Error al insertar los datos en camioneros: " . $stmt_insert->error);
            }
        } else {
            echo "El usuario ya es camionero.";
        }
    }

    // Redirigir al perfil del usuario
    header("Location: perfil_usuario.php");
    exit();
} else {
    die("Método no permitido.");
}
?>
