<?php
// Inicia la sesión para mantener la información del usuario si es necesario
session_start();

// Obtiene el código de error de los parámetros de la URL
$error_code = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : 'error_generico';

// Mapeo de códigos de error a mensajes
$error_messages = [
    'datos_faltantes' => 'Faltan datos necesarios para realizar esta acción. Por favor, verifica la información ingresada.',
    'conexion_bd' => 'Hubo un problema al conectarse a la base de datos. Inténtalo más tarde.',
    'acceso_no_autorizado' => 'No tienes permiso para acceder a esta sección.',
    'tipo_evento_no_proporcionado' => 'No se proporcionó el tipo de evento necesario para continuar.',
    'error_generico' => 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo más tarde.'
];

// Obtiene el mensaje de error correspondiente
$error_message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : $error_messages['error_generico'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8d7da;
            color: #721c24;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .error-container {
            background-color: #f5c6cb;
            padding: 20px;
            border: 1px solid #f1b0b7;
            border-radius: 8px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 24px;
        }

        p {
            margin: 10px 0;
        }

        a {
            color: #0056b3;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>¡Ups! Algo salió mal.</h1>
        <p><?php echo $error_message; ?></p>
        <p>
            <a href="javascript:history.back();">Volver atrás</a> | 
            <a href="index.php">Ir al inicio</a>
        </p>
    </div>
</body>
</html>
