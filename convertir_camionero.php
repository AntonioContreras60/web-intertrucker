<?php
session_start();
include 'conexion.php';

// Solo permitir acceso si el usuario es administrador
if ($_SESSION['rol'] !== 'administrador') {
    die("No tiene permiso para acceder a esta sección");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $dni = $_POST['dni'];
    $tipo_carnet = $_POST['tipo_carnet'];
    $num_licencia = $_POST['num_licencia'];
    $fecha_contratacion = $_POST['fecha_contratacion'];

    // Insertar en la tabla camioneros
    $sql = "INSERT INTO camioneros (usuario_id, dni, tipo_carnet, num_licencia, fecha_contratacion, activo) 
            VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $usuario_id, $dni, $tipo_carnet, $num_licencia, $fecha_contratacion);

    if ($stmt->execute()) {
        header("Location: detalles_colaborador.php?id=$usuario_id");
        exit();
    } else {
        die("Error al convertir en camionero: " . $conn->error);
    }
}
?>
