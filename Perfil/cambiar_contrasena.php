<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="/header.css">
  <script src="/header.js"></script>
</head>
<body>
    <?php
    include 'conexion.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; // Incluir el encabezado con el menú

    $usuario_id = $_SESSION['usuario_id'];
    ?>

    <h1>Cambiar Contraseña</h1>
    <form action="guardar_contrasena.php" method="post">
        <label for="contrasena_actual">Contraseña Actual:</label>
        <input type="password" id="contrasena_actual" name="contrasena_actual" required><br>

        <label for="nueva_contrasena">Nueva Contraseña:</label>
        <input type="password" id="nueva_contrasena" name="nueva_contrasena" required><br>

        <label for="confirmar_contrasena">Confirmar Nueva Contraseña:</label>
        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required><br>

        <input type="submit" value="Cambiar Contraseña">
    </form>

    <?php
    include 'footer.php'; // Incluir el pie de página
    ?>
</body>
</html>
