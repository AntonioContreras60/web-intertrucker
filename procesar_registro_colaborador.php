<?php
session_start();
include 'conexion.php';

// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar el token de invitación
$token = $_GET['token'] ?? null;
if (!$token) {
    die("<p style='font-size: 24px; color: red; font-weight: bold;'>Token de invitación no válido.</p>");
}

// Obtener el admin_id y el email del token
$sql_token = "SELECT admin_id, email FROM invitaciones WHERE token = ? AND fecha_invitacion >= NOW() - INTERVAL 48 HOUR";
$stmt = $conn->prepare($sql_token);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<p style='font-size: 24px; color: red; font-weight: bold;'>La invitación no es válida o ha caducado.</p>");
}

$invitacion = $result->fetch_assoc();
$admin_id = $invitacion['admin_id'];
$email = $invitacion['email'];

// Recibir los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $telefono = trim($_POST['telefono']);
    $cif = trim($_POST['cif']);
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

    // Insertar el colaborador como usuario en la base de datos
    $sql_insert = "INSERT INTO usuarios (nombre_usuario, email, contrasena, telefono, rol, admin_id, cif, email_verificado) VALUES (?, ?, ?, ?, 'gestor', ?, ?, 1)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sssssi", $nombre_usuario, $email, $contrasena, $telefono, $admin_id, $cif);

    if ($stmt_insert->execute()) {
        // Obtener el ID del usuario recién creado
        $usuario_id = $stmt_insert->insert_id;

        // Insertar la dirección
        $nombre_via = trim($_POST['nombre_via']);
        $numero = trim($_POST['numero']);
        $complemento = trim($_POST['complemento']);
        $ciudad = trim($_POST['ciudad']);
        $estado_provincia = trim($_POST['estado_provincia']);
        $codigo_postal = trim($_POST['codigo_postal']);
        $pais = trim($_POST['pais']);
        $region = trim($_POST['region']);

        $sql_direccion = "INSERT INTO direcciones 
            (nombre_via, numero, complemento, ciudad, estado_provincia, codigo_postal, pais, region, usuario_id, tipo_direccion) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'fiscal')";
        $stmt_direccion = $conn->prepare($sql_direccion);
        $stmt_direccion->bind_param("ssssssssi", $nombre_via, $numero, $complemento, $ciudad, $estado_provincia, $codigo_postal, $pais, $region, $usuario_id);

        if ($stmt_direccion->execute()) {
            echo "<p style='font-size: 3rem; font-weight: bold;'>Registro exitoso. Dirección añadida correctamente.</p>";
        } else {
            echo "<p style='font-size: 3rem; font-weight: bold;'>Error al guardar la dirección: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='font-size: 3rem; font-weight: bold;'>Error al registrar el colaborador: " . $conn->error . "</p>";
    }

    // Botón de redirección a inicio de sesión
    echo "<a href='/Perfil/inicio_sesion.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; font-size: 1.5rem; font-weight: bold; text-decoration: none; background-color: #4CAF50; color: white; border-radius: 5px;'>Ir a Inicio de Sesión</a>";
}
?>
