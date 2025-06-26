<?php
session_start();
include 'conexion.php';

// Solo permitir acceso si el usuario es administrador
if ($_SESSION['rol'] !== 'administrador') {
    die("No tiene permiso para realizar esta acción.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $usuario_id = $_POST['usuario_id'];
    $nombre_usuario = $_POST['nombre_usuario'];
    $apellidos = $_POST['apellidos'];
    $telefono = $_POST['telefono'];
    $cif = $_POST['cif'];
    $convertir_camionero = isset($_POST['convertir_camionero']);

    // Actualizar información del usuario
    $sql_usuario = "UPDATE usuarios SET nombre_usuario = ?, apellidos = ?, telefono = ?, cif = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_usuario);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }
    $stmt->bind_param("ssssi", $nombre_usuario, $apellidos, $telefono, $cif, $usuario_id);
    $stmt->execute();

    // Verificar si se debe convertir en camionero
    if ($convertir_camionero) {
        $fecha_contratacion = $_POST['fecha_contratacion'];
        $tipo_carnet = $_POST['tipo_carnet'];
        $fecha_caducidad = $_POST['fecha_caducidad'];
        $num_licencia = $_POST['num_licencia'];
        $caducidad_profesional = $_POST['caducidad_profesional'];

        // Insertar datos en la tabla camioneros
        $sql_camionero = "INSERT INTO camioneros (usuario_id, tipo_carnet, num_licencia, fecha_caducidad, caducidad_profesional, fecha_contratacion, activo) 
                          VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql_camionero);
        if (!$stmt) {
            die("Error al preparar la consulta de camionero: " . $conn->error);
        }
        $stmt->bind_param("isssss", $usuario_id, $tipo_carnet, $num_licencia, $fecha_caducidad, $caducidad_profesional, $fecha_contratacion);
        $stmt->execute();
    }

    // Redirigir de nuevo a detalles_colaborador.php
    header("Location: detalles_colaborador.php?id=$usuario_id");
    exit();
} else {
    die("Método no permitido.");
}
?>
