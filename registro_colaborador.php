<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Colaborador</title>
</head>
<body>
    <?php
    include 'conexion.php';

    // Obtener el token de la URL
    $token = $_GET['token'] ?? null;
    if (!$token) {
        die("Token de invitación no válido.");
    }

    // Verificar el token en la base de datos
    $sql_token = "SELECT admin_id, email FROM invitaciones WHERE token = ? AND fecha_invitacion >= NOW() - INTERVAL 48 HOUR";
    $stmt = $conn->prepare($sql_token);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("La invitación no es válida o ha caducado.");
    }

    $invitacion = $result->fetch_assoc();
    $email = $invitacion['email'];
    ?>

    <h1>Registro de Gestor</h1>
    <form action="procesar_registro_colaborador.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
        
        <!-- Nombre Completo del Colaborador -->
        <label for="nombre_usuario">Nombre Completo:</label><br>
        <input type="text" id="nombre_usuario" name="nombre_usuario" 
               required minlength="2" maxlength="255" 
               pattern="^[\p{L}\s]+$" 
               title="El nombre solo puede contener letras y espacios en cualquier idioma."><br><br>

        <!-- Correo Electrónico (precargado y de solo lectura) -->
        <label for="email">Correo Electrónico:</label><br>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" readonly><br><br>

        <!-- Número de Identificación Fiscal (CIF) -->
        <label for="cif">Número de Identificación Fiscal (CIF):</label><br>
        <input type="text" id="cif" name="cif" required 
               pattern="^[A-Za-z0-9]+$" title="El CIF debe contener solo letras y números."><br><br>

        <!-- Contraseña -->
        <label for="contrasena">Contraseña:</label><br>
        <input type="password" id="contrasena" name="contrasena" 
               required minlength="8" 
               title="La contraseña debe tener al menos 8 caracteres."><br><br>

        <!-- Confirmación de Contraseña -->
        <label for="confirmar_contrasena">Confirmar Contraseña:</label><br>
        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" 
               required minlength="8" 
               title="Por favor, confirma la contraseña."><br><br>

        <!-- Teléfono -->
        <label for="telefono">Teléfono de Contacto:</label><br>
        <input type="tel" id="telefono" name="telefono" 
               required pattern="^\+?[0-9\s\-]{7,15}$" 
               title="El teléfono debe contener entre 7 y 15 dígitos, incluyendo prefijo opcional."><br><br>

        <!-- Dirección -->
        <h2>Dirección del Gestor</h2>

        <label for="nombre_via">Calle:</label><br>
        <input type="text" id="nombre_via" name="nombre_via" required><br><br>

        <label for="numero">Número:</label><br>
        <input type="text" id="numero" name="numero" required><br><br>

        <label for="complemento">Complemento:</label><br>
        <input type="text" id="complemento" name="complemento"><br><br>

        <label for="ciudad">Ciudad:</label><br>
        <input type="text" id="ciudad" name="ciudad" required><br><br>

        <label for="estado_provincia">Estado/Provincia:</label><br>
        <input type="text" id="estado_provincia" name="estado_provincia" required><br><br>

        <label for="codigo_postal">Código Postal:</label><br>
        <input type="text" id="codigo_postal" name="codigo_postal" required><br><br>

        <label for="pais">País:</label><br>
        <input type="text" id="pais" name="pais" required><br><br>

        <label for="region">Región:</label><br>
        <input type="text" id="region" name="region"><br><br>

        <button type="submit">Registrarse</button>
    </form>

    <!-- Validación de Contraseña en el Navegador -->
    <script>
        document.querySelector("form").addEventListener("submit", function(event) {
            const contrasena = document.getElementById("contrasena").value;
            const confirmarContrasena = document.getElementById("confirmar_contrasena").value;

            if (contrasena !== confirmarContrasena) {
                alert("Las contraseñas no coinciden. Por favor, revísalas.");
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
