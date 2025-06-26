<?php
session_start();
include 'conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener ID del camión
$camion_id = $_GET['id'];

// Obtener datos del camión
$sql = "SELECT * FROM camiones WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $camion_id);
$stmt->execute();
$resultado = $stmt->get_result();
$camion = $resultado->fetch_assoc();

// Procesar la edición
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = $_POST['matricula'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $capacidad = $_POST['capacidad'];
    $tipo = $_POST['tipo'];
    $telefono = $_POST['telefono'];
    $ano_fabricacion = $_POST['ano_fabricacion'];
    $tipo_combustible = $_POST['tipo_combustible'];

    // Actualizar los datos del camión en la base de datos
    $sql = "UPDATE camiones SET matricula = ?, marca = ?, modelo = ?, capacidad = ?, tipo = ?, telefono = ?, ano_fabricacion = ?, tipo_combustible = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssdsisii', $matricula, $marca, $modelo, $capacidad, $tipo, $telefono, $ano_fabricacion, $tipo_combustible, $camion_id);

    if ($stmt->execute()) {
        header('Location: my_trucks.php');
        exit();
    } else {
        echo "Error al actualizar el camión: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Camión</title>
</head>
<body>

<h1>Editar Camión</h1>

<form method="POST">
    <input type="text" name="matricula" value="<?= htmlspecialchars($camion['matricula']) ?>" required>
    <input type="text" name="marca" value="<?= htmlspecialchars($camion['marca']) ?>" required>
    <input type="text" name="modelo" value="<?= htmlspecialchars($camion['modelo']) ?>" required>
    <input type="number" step="0.1" name="capacidad" value="<?= htmlspecialchars($camion['capacidad']) ?>" required>
    <input type="text" name="tipo" value="<?= htmlspecialchars($camion['tipo']) ?>" required>
    <input type="text" name="telefono" value="<?= htmlspecialchars($camion['telefono']) ?>">
    <input type="number" name="ano_fabricacion" value="<?= htmlspecialchars($camion['ano_fabricacion']) ?>">
    <input type="text" name="tipo_combustible" value="<?= htmlspecialchars($camion['tipo_combustible']) ?>">
    <button type="submit">Guardar Cambios</button>
</form>

</body>
</html>
