<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InterTrucker - Dashboard</title>
    <link rel="stylesheet" href="styles.css"> <!-- Mantén el enlace correcto al archivo CSS -->
</head>
<body>
    <!-- Incluir el header con el menú de navegación -->
    <?php include 'header.php'; ?>

    <!-- Contenido principal con los botones alineados verticalmente -->
<main style="max-width: 300px; margin: 20px auto;">
    <button onclick="window.location.href='my_network.php'" class="btn btn-full" style="display: block; width: 100%; margin-bottom: 20px; font-size: 2em; padding: 20px; background-color: #28a745; color: white; border-radius: 5px; font-weight: bold;">Mis contactos</button> 
    <button onclick="window.location.href='my_trucks'" class="btn btn-full" style="display: block; width: 100%; margin-bottom: 20px; font-size: 2em; padding: 20px; background-color: #28a745; color: white; border-radius: 5px; font-weight: bold;">Mis Vehículos</button>
    <button onclick="window.location.href='my_truckers.php'" class="btn btn-full" style="display: block; width: 100%; margin-bottom: 20px; font-size: 2em; padding: 20px; background-color: #28a745; color: white; border-radius: 5px; font-weight: bold;">Mis Conductores</button>
    <button onclick="window.location.href='Perfil/perfil_usuario.php'" class="btn btn-full" style="display: block; width: 100%; margin-bottom: 20px; font-size: 2em; padding: 20px; background-color: #28a745; color: white; border-radius: 5px; font-weight: bold;">Mi Perfil</button>
    <button onclick="window.location.href='/Perfil/logout.php'" class="btn btn-full" style="display: block; width: 100%; margin-bottom: 20px; font-size: 2em; padding: 20px; background-color: #28a745; color: white; border-radius: 5px; font-weight: bold;">Cerrar sesión</button>
</main>


</body>
</html>
</php>