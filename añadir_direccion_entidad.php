<?php
session_start(); // Si quieres mantener la sesión para roles/CSRF, etc.
include 'conexion.php'; // Conexión a la base de datos

// Mostrar errores (para depuración; puedes quitarlo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos del formulario
    $nombre_via         = $_POST['nombre_via'];
    $numero             = $_POST['numero'];
    $complemento        = $_POST['complemento'];
    $ciudad             = $_POST['ciudad'];
    $estado_provincia   = $_POST['estado_provincia'];
    $codigo_postal      = $_POST['codigo_postal'];
    $telefono_contacto  = $_POST['telefono_contacto'];
    $email_contacto     = $_POST['email_contacto'];
    $tipo_direccion     = $_POST['tipo_direccion'];

    // Recibir el ID de la entidad por GET (según tu snippet)
    // Si realmente es un número, puedes forzar a int:
    $entidad_id         = (int)$_GET['entidad_id'];

    // Preparar la sentencia INSERT con placeholders
    $sql = "INSERT INTO direcciones 
            (nombre_via, 
             numero, 
             complemento, 
             ciudad, 
             estado_provincia, 
             codigo_postal, 
             pais, 
             region, 
             tipo_direccion, 
             entidad_id, 
             telefono_contacto, 
             email_contacto)
            VALUES 
            (?, ?, ?, ?, ?, ?, 'USA', ?, ?, ?, ?, ?)";

    // Prepara la query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    // bind_param => 11 placeholders en total. 
    // Revisa el orden de los '?':
    // 1) $nombre_via
    // 2) $numero
    // 3) $complemento
    // 4) $ciudad
    // 5) $estado_provincia
    // 6) $codigo_postal
    // 7) $estado_provincia (para 'region')
    // 8) $tipo_direccion
    // 9) $entidad_id
    // 10) $telefono_contacto
    // 11) $email_contacto

    $stmt->bind_param(
        "sssssssssss",
        $nombre_via,
        $numero,
        $complemento,
        $ciudad,
        $estado_provincia,
        $codigo_postal,
        $estado_provincia,
        $tipo_direccion,
        $entidad_id,
        $telefono_contacto,
        $email_contacto
    );

    // Ejecutar
    if ($stmt->execute()) {
        echo "Dirección añadida correctamente.";

        // Preguntar si quiere añadir otra dirección o finalizar
        echo "
        <form method='GET' action='añadir_direccion_entidad.php'>
            <input type='hidden' name='entidad_id' value='$entidad_id'>
            <button type='submit'>Añadir otra dirección</button>
        </form>";
        
        // Botón para finalizar con la redirección a my_network.php
        echo "<button onclick=\"window.location.href='my_network.php'\">Finalizar</button>";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Dirección Entidad</title>
</head>
<body>
    <h2>Añadir Dirección</h2>
    <form method="POST" action="añadir_direccion_entidad.php?entidad_id=<?php echo $_GET['entidad_id']; ?>">
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

        <label for="telefono_contacto">Teléfono de Contacto:</label><br>
        <input type="text" id="telefono_contacto" name="telefono_contacto" required><br>

        <label for="email_contacto">Email de Contacto:</label><br>
        <input type="email" id="email_contacto" name="email_contacto" required><br>

        <label for="tipo_direccion">Tipo de Dirección:</label><br>
        <select id="tipo_direccion" name="tipo_direccion">
            <option value="fiscal">Fiscal</option>
            <option value="recogida_entrega">Recogida/Entrega</option>
        </select><br>

        <button type="submit">Guardar Dirección</button>
    </form>
</body>
</html>
