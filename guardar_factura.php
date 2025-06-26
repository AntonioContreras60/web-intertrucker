<?php
session_start();
include 'conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar Factura - InterTrucker</title>
    <link rel="stylesheet" href="styles.css"> <!-- Enlace al archivo CSS -->
</head>
<body>
<?php
// Verificar que el usuario está autenticado
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo "<div class='mensaje-error'>Error: Usuario no autenticado. Por favor, inicia sesión.</div>";
    echo "<div class='boton-volver'><a href='facturas.php'>Volver</a></div>";
    exit;
}

// Identificar al usuario que registra la factura
$usuario_id = $_SESSION['usuario_id'];

// Recoger datos enviados desde el formulario
$fecha = $_POST['fecha'] ?? null;
$tipo = $_POST['tipo'] ?? null;
$cantidad = $_POST['cantidad'] ?? null;
$observaciones = $_POST['observaciones'] ?? null;

// Validación de datos básicos
if (empty($fecha) || empty($tipo) || empty($cantidad)) {
    echo "<div class='mensaje-error'>Error: Todos los campos obligatorios (fecha, tipo, cantidad) deben estar completos.</div>";
    echo "<div class='boton-volver'><a href='facturas.php'>Volver</a></div>";
    exit;
}

// Manejo de la foto subida
$foto = null;
if (!empty($_FILES['foto']['name'])) {
    $directorio_subida = 'uploads/facturas/';
    $nombre_archivo = time() . "_" . basename($_FILES['foto']['name']);
    $ruta_archivo = $directorio_subida . $nombre_archivo;

    // Crear el directorio si no existe
    if (!is_dir($directorio_subida)) {
        mkdir($directorio_subida, 0755, true);
    }

    // Mover la foto al directorio de destino
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_archivo)) {
        $foto = $ruta_archivo;
    } else {
        echo "<div class='mensaje-error'>Error al subir la foto. Verifica los permisos del servidor.</div>";
        echo "<div class='boton-volver'><a href='facturas.php'>Volver</a></div>";
        exit;
    }
}

// Insertar la factura en la base de datos
$sql_insertar = "INSERT INTO facturas (usuario_id, fecha, tipo, cantidad, foto, observaciones)
                 VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql_insertar);
$stmt->bind_param("issdss", $usuario_id, $fecha, $tipo, $cantidad, $foto, $observaciones);

if ($stmt->execute()) {
    echo "<div class='mensaje-exito'>Factura registrada exitosamente.</div>";
    echo "<div class='boton-volver'><a href='facturas.php'>Volver</a></div>";
} else {
    echo "<div class='mensaje-error'>Error al registrar la factura: " . htmlspecialchars($stmt->error) . "</div>";
    echo "<div class='boton-volver'><a href='facturas.php'>Volver</a></div>";
}

$stmt->close();
$conn->close();
?>
</body>
</html>
