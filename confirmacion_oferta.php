<?php
session_start();
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : "Operación completada.";
unset($_SESSION['mensaje']); // Limpiar el mensaje después de mostrarlo
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación</title>
</head>
<body>
    <h2><?php echo htmlspecialchars($mensaje); ?></h2>
    <a href="hacer_oferta.php">Volver a la Página Principal</a>
</body>
</html>
