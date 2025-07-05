<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="styles.css"> <!-- Ajusta si tu CSS está en otro sitio -->
<link rel='stylesheet' href='/header.css'>
<script src='/header.js'></script>
</head>
<body>
<?php require_once $_SERVER["DOCUMENT_ROOT"]."/header.php"; ?>
<?php
include 'conexion.php';

// Obtener datos del formulario de registro
$nombre_usuario = $_POST['nombre'];
$email          = $_POST['email'];
$contrasena     = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
$telefono       = $_POST['telefono'];
$direccion      = $_POST['direccion'];
$tipo_usuario   = $_POST['tipo_usuario'];

// Verificar si el correo electrónico ya está registrado usando sentencias preparadas
$sql_verificar = "SELECT id FROM usuarios WHERE email = ?";
$stmt_ver = $conn->prepare($sql_verificar);
if (!$stmt_ver) {
    die("Error al preparar verificación de email: " . $conn->error);
}
$stmt_ver->bind_param("s", $email);
$stmt_ver->execute();
$res_ver = $stmt_ver->get_result();

if ($res_ver->num_rows > 0) {
    // Si el correo ya está registrado
    echo "Error: El correo electrónico ya está registrado.<br>";
    echo "<a href='registrar_usuario.php'>Volver al registro</a>";
} else {
    // Insertar el nuevo usuario con placeholders en lugar de concatenar
    $sql_insert = "
      INSERT INTO usuarios (nombre_usuario, email, contrasena, telefono, direccion, tipo_usuario)
      VALUES (?, ?, ?, ?, ?, ?)
    ";
    $stmt_ins = $conn->prepare($sql_insert);
    if (!$stmt_ins) {
        die("Error al preparar inserción de usuario: " . $conn->error);
    }
    $stmt_ins->bind_param("ssssss",
        $nombre_usuario,
        $email,
        $contrasena,
        $telefono,
        $direccion,
        $tipo_usuario
    );

    if ($stmt_ins->execute()) {
        // Iniciar sesión para el usuario recién registrado
        $_SESSION['usuario_id'] = $stmt_ins->insert_id;
        $_SESSION['nombre_usuario'] = $nombre_usuario;

        // Mostrar mensaje de éxito con un botón para continuar al perfil
        echo "<h1>Registro hecho con éxito</h1>";
        echo "<p>Bienvenido, $nombre_usuario. Tu registro se ha completado exitosamente.</p>";
        echo "<a href='perfil_usuario.php' class='button'>Continuar a mi perfil</a>";
    } else {
        echo "Error al insertar el usuario: " . $stmt_ins->error;
    }
    $stmt_ins->close();
}

$res_ver->free();
$stmt_ver->close();
$conn->close();

include 'footer.php'; // Incluir el pie de página, si procede
?>
</body>
</html>
