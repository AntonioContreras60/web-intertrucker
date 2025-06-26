<?php
session_start(); // si deseas mantener sesión
include '../conexion.php'; // Ajusta si tu ruta es distinta

// Inicializamos una variable para saber si la dirección fue añadida
$direccion_anadida = false;

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos del formulario
    $nombre_via         = $_POST['nombre_via'];
    $numero             = $_POST['numero'];
    $complemento        = $_POST['complemento'];
    $ciudad             = $_POST['ciudad'];
    $estado_provincia   = $_POST['estado_provincia'];
    $codigo_postal      = $_POST['codigo_postal'];
    $pais               = $_POST['pais'];
    $telefono_contacto  = $_POST['telefono_contacto'];
    $email_contacto     = $_POST['email_contacto'];
    $tipo_direccion     = $_POST['tipo_direccion'];
    // Recibir el ID del usuario por GET
    $usuario_id         = (int)$_GET['usuario_id'];

    // Inserción de la dirección con placeholders
    $sql = "INSERT INTO direcciones
           (nombre_via, numero, complemento, ciudad, estado_provincia,
            codigo_postal, pais, region, tipo_direccion, usuario_id,
            telefono_contacto, email_contacto)
           VALUES
           (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en prepare: " . $conn->error);
    }

    // Observa que 'region' aquí se rellena de nuevo con $estado_provincia
    $stmt->bind_param(
        "ssssssssssss",
        $nombre_via,
        $numero,
        $complemento,
        $ciudad,
        $estado_provincia,
        $codigo_postal,
        $pais,
        $estado_provincia,
        $tipo_direccion,
        $usuario_id,
        $telefono_contacto,
        $email_contacto
    );

    if ($stmt->execute()) {
        $direccion_anadida = true;
    } else {
        echo "Error al añadir la dirección: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!-- Formulario para añadir direcciones -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Dirección Usuario</title>
</head>
<body>
    <?php if ($direccion_anadida): ?>
        <p>Dirección añadida correctamente.</p>
        <form method="GET" action="añadir_direccion_usuario.php">
            <input type="hidden" name="usuario_id" value="<?php echo $_GET['usuario_id']; ?>">
            <button type="submit">Añadir otra dirección</button>
        </form>
        <br>
        <button onclick="window.location.href='perfil_usuario.php'">Finalizar</button>
    <?php else: ?>
        <h2>Añadir Dirección</h2>
        <form method="POST" action="añadir_direccion_usuario.php?usuario_id=<?php echo $_GET['usuario_id']; ?>">
            <label for="nombre_via">Calle:</label><br>
            <input type="text" id="nombre_via" name="nombre_via" required><br>

            <label for="numero">Número:</label><br>
            <input type="text" id="numero" name="numero" required><br>

            <label for="complemento">Complemento:</label><br>
            <input type="text" id="complemento" name="complemento"><br>

            <label for="ciudad">Ciudad:</label><br>
            <input type="text" id="ciudad" name="ciudad" required><br>

            <label for="estado_provincia">Estado/Provincia:</label><br>
            <input type="text" id="estado_provincia" name="estado_provincia" required><br>

            <label for="codigo_postal">Código Postal:</label><br>
            <input type="text" id="codigo_postal" name="codigo_postal" required><br>

            <label for="pais">País:</label><br>
            <input type="text" id="pais" name="pais" required><br>

            <label for="telefono_contacto">Teléfono de Contacto:</label><br>
            <input type="text" id="telefono_contacto" name="telefono_contacto" required><br>

            <label for="email_contacto">Email de Contacto:</label><br>
            <input type="email" id="email_contacto" name="email_contacto" required><br>

            <label for="tipo_direccion">Tipo de Dirección:</label><br>
            <select id="tipo_direccion" name="tipo_direccion" required>
                <option value="fiscal">Fiscal</option>
                <option value="recogida_entrega">Recogida/Entrega</option>
            </select><br>

            <button type="submit">Guardar Dirección</button>
        </form>
    <?php endif; ?>
</body>
</html>
