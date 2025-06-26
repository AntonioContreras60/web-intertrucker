<?php
session_start();
include '../conexion.php';

$email      = $_POST['email']      ?? '';
$contrasena = $_POST['contrasena'] ?? '';

$stmt = $conn->prepare("
    SELECT id, contrasena, nombre_usuario
    FROM   usuarios
    WHERE  email = ? AND rol = 'superadmin'
    LIMIT  1
");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if (password_verify($contrasena, $row['contrasena'])) {
        $_SESSION['usuario_id']     = $row['id'];
        $_SESSION['nombre_usuario'] = $row['nombre_usuario'];
        $_SESSION['rol']            = 'superadmin';
        header('Location: dashboard.php');
        exit();
    }
}
header('Location: login.php?error=1');
