<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InterTrucker - Gestión eficiente de portes</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
            color: #333;
        }

        header {
            background-color: #001C80;
            padding: 20px 0;
            text-align: center;
        }

        .logo img {
            max-width: 250px;
        }

        .login-button {
            display: inline-block;
            padding: 12px 25px;
            margin-top: 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            font-size: 18px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .login-button:hover {
            background-color: #005bb5;
        }

        main {
            max-width: 1000px;
            margin: auto;
            padding: 40px 20px;
        }

        h1, h2, h3 {
            color: #001C80;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        section {
            margin-bottom: 40px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        ul {
            margin: 15px 0;
            padding-left: 25px;
        }

        ul li {
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .detalle-movil {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="imagenes/logos/logo2.png" alt="InterTrucker Logo">
        </div>
    </header>
    
    <main>
        <h1>Bienvenido a InterTrucker</h1>
        
        <div style="text-align: center;">
            <a href="/Perfil/inicio_sesion.php" class="login-button">Iniciar sesión o darse de alta</a>
        </div>

        <section>
            <h2>¿Qué es InterTrucker?</h2>
            <p>Una plataforma para gestionar tus portes manera de ágil y eficiente, y registrar las recogidas y entregas. 
            </p>
        </section>

        <section>
            <h2>Beneficios clave</h2>
            <ul>
                <li>Agiliza la gestión de portes:
                <ul>
                    <li>Crea y asigna portes a tus camioneros u ofrécelos a tus contactos.</li>
                    <li>Acepta o rechaza los portes que te ofrezcan tus contactos.</li>
                </ul>
                </li>
                <li>Facilita el registro detallado de recogidas, entregas y gastos.</li>
                <li>Almacenamieno prolongado de la información.</li>
                <li>App móvil que funciona sin conexión, ideal para zonas con poca cobertura.</li>
        </section>

        <section>
            <h2>Estructura de la plataforma</h2>
            <ul>
                <li><strong>Web (Gestores):</strong> gestión en tiempo real.</li>
                <li><strong>App móvil (Camioneros):</strong> registro de datos incluso sin cobertura.</li>
            </ul>
        </section>

        <section>
            <h2>Funcionalidades detalladas</h2>

            <h3>Web (Gestores)</h3>
            <ul>
                <li><strong>Crear y compartir portes</strong> con contactos.</li>
                <li><strong>Asignar portes</strong> a camioneros específicos.</li>
                <li><strong>Trabajo colaborativo</strong> entre múltiples gestores.</li>
                <li><strong>Gestión interna</strong> de vehículos y personal.</li>
                <li><strong>Comunicación eficiente</strong> con camioneros y expedidores.</li>
                <li><strong>Registro de facturas</strong> asociadas a los portes.</li>
                <li><strong>Gestión del almacén</strong> con listado claro de recogidas y entregas.</li>
            </ul>

            <h3>App (Camioneros)</h3>
            <ul>
                <li><strong>Acceso inmediato</strong> a portes asignados.</li>
                <li><strong>Registro fácil de recogidas y entregas</strong>:
                    <ul>
                        <li>Geolocalización precisa con fecha/hora.</li>
                        <li>Evidencias multimedia: fotos, vídeos y documentos.</li>
                        <li>Descripciones detalladas y firmas digitales.</li>
                    </ul>
                </li>
                <li><strong>Búsqueda rápida de direcciones</strong> mediante mapas integrados.</li>
                <li><strong>Registro simplificado de facturas</strong> con evidencia fotográfica.</li>
            </ul>
        </section>

    </main>

    <?php include 'footer.php'; ?>

    <script src="sqlite-helper.js"></script>
</body>
</html>
