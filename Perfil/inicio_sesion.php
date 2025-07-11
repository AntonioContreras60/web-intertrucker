<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InterTrucker - Iniciar Sesión</title>
    <style>
        /* ======== Cabecera y estilos principales (igual que index.php) ======== */
        header {
            background-color: #001C80; /* Color exacto del logotipo */
            padding: 20px 0;
            text-align: center;
        }
        .logo img {
            max-width: 200px;
        }

        /* Estilo para el botón de iniciar sesión */
        .login-button {
            display: inline-block;
            margin-top: 10px;
            padding: 15px 30px;
            font-size: 1em;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        /* Estilo general de la página */
        main {
            padding: 20px;
        }

        h1 {
            color: #007bff;
            text-align: center;
        }

        /* ======== Estilos propios de la página de inicio de sesión ======== */
        form {
            max-width: 400px;
            margin: 0 auto; /* Centra el formulario horizontalmente */
            text-align: left; /* Ajusta según tus preferencias */
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 1em;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            margin: 0 10px;
            color: #007bff;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<?php $error = $_GET['error'] ?? ''; ?>
<body>
    <header>
        <div class="logo">
            <!-- Aquí se integra el logotipo, igual que en index.php -->
            <img src="/imagenes/logos/logo2.png" alt="InterTrucker Logo">
        </div>
    </header>

    <main>
        <h1>Iniciar Sesión</h1>
        <?php if ($error === 'rol_no_autorizado'): ?>
            <p style="color:red; text-align:center;">Solo administradores y gestores pueden acceder a la web.</p>
        <?php endif; ?>
        
        <form action="procesar_sesion.php" method="post">
            <label for="email">Correo electrónico:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" required>
            
            <input type="submit" value="Iniciar Sesión">
        </form>

        <div class="links">
            <p>¿Olvidaste tu contraseña? 
                <a href="solicitar_recuperacion.php">Recuperar contraseña</a>
            </p>
            <p>¿Eres nuevo aquí? 
                <a href="registro.php">Registrarse</a>
            </p>
        </div>
    </main>
<br><br><br><br>
    <!-- Igual que en index.php -->
    <?php include '../footer.php'; ?>

    <!-- Si usas sqlite-helper.js, inclúyelo aquí. Ajusta la ruta según corresponda -->
    <script src="../sqlite-helper.js"></script>
</body>
</html>
