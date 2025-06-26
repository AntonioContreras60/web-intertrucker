<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InterTrucker - Iniciar Sesión</title>
    <style>
        /* Estilo para la barra de navegación */
        nav {
            background-color: #007bff;
            padding: 15px; 
        }
        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center; /* Centra el enlace de InterTrucker */
            align-items: center;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 2em; /* Tamaño de fuente aumentado */
            font-weight: bold;
        }

        /* Estilo para el formulario de inicio de sesión */
        form {
            max-width: 300px;
            margin: 20px auto; /* Alineación central del formulario */
        }
        form label {
            display: block;
            margin-bottom: 5px; /* Espacio entre la etiqueta y el campo */
        }
        form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px; /* Espacio entre los campos */
        }
        form input[type="submit"] {
            padding: 10px 20px;
            font-size: 1.2em;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<header>
    <nav>
        <ul>
            <li>
                <a href="/" style="color: white; text-decoration: none;">InterTrucker</a>
            </li>
        </ul>
    </nav>
</header>

<h1>Iniciar Sesión</h1>

<!-- Formulario de inicio de sesión -->
<form action="procesar_sesion.php" method="post">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    
    <label for="contrasena">Contraseña:</label>
    <input type="password" id="contrasena" name="contrasena" required>
    
    <input type="submit" value="Iniciar Sesión">
</form>

<p>¿Olvidaste tu contraseña?</p>
<a href="reset_password.php">Restablecer contraseña</a><br>

<p>¿Nuevo usuario?</p>
<a href="registro.php">Registrarse</a>

<?php
// Pie de página
include '../footer.php';
?>
</body>
</html>
