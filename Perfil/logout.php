<?php
session_start();

// Verificar si el logout ha sido confirmado
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    session_unset();
    session_destroy();

    // Redirigir a la página de inicio después de cerrar sesión
    header('Location: /index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Cerrar Sesión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        .confirm-box {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: inline-block;
            max-width: 300px;
            width: 90%;
        }
        a {
            display: block;
            text-decoration: none;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            font-weight: bold;
            color: white;
            font-size: 1.2em;
        }
        .yes {
            background-color: #28a745;
        }
        .no {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="confirm-box">
        <h2>¿Estás seguro de que quieres cerrar sesión?</h2>
        <a href="logout.php?confirm=yes" class="yes">Sí, cerrar sesión</a>
        <a href="javascript:history.back()" class="no">No, volver atrás</a>
    </div>
</body>
</html>
