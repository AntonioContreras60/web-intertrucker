<?php
session_start();
include '../conexion.php';

// Mostrar errores (solo para depuración; quítalo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar que el usuario esté en sesión y obtener el ID de la dirección
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    echo "Acceso no autorizado.";
    exit();
}

$usuario_id   = (int)$_SESSION['usuario_id'];
$direccion_id = (int)$_GET['id'];

// 1) Comprobar que la dirección pertenece al usuario y obtener datos
$sql_select = "SELECT *
               FROM direcciones
               WHERE id = ?
                 AND usuario_id = ?
               LIMIT 1";
$stmt_sel = $conn->prepare($sql_select);
if (!$stmt_sel) {
    die("Error al preparar la consulta SELECT: " . $conn->error);
}
$stmt_sel->bind_param("ii", $direccion_id, $usuario_id);
$stmt_sel->execute();
$resultado = $stmt_sel->get_result();

if ($resultado->num_rows === 0) {
    echo "Dirección no encontrada o acceso no autorizado.";
    exit();
}

$direccion = $resultado->fetch_assoc();
$stmt_sel->close();

// 2) Procesar el formulario si se envía una actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_via       = $_POST['nombre_via'];
    $numero           = $_POST['numero'];
    $complemento      = $_POST['complemento'];
    $ciudad           = $_POST['ciudad'];
    $estado_provincia = $_POST['estado_provincia'];
    $codigo_postal    = $_POST['codigo_postal'];
    $pais             = $_POST['pais'];
    $tipo_direccion   = $_POST['tipo_direccion'];

    // Usar placeholders en el UPDATE
    $sql_update = "UPDATE direcciones
                   SET nombre_via = ?,
                       numero = ?,
                       complemento = ?,
                       ciudad = ?,
                       estado_provincia = ?,
                       codigo_postal = ?,
                       pais = ?,
                       tipo_direccion = ?
                   WHERE id = ?
                     AND usuario_id = ?";
    $stmt_up = $conn->prepare($sql_update);
    if (!$stmt_up) {
        die("Error al preparar el UPDATE: " . $conn->error);
    }

    // bind_param => orden: 8 strings + 2 int
    $stmt_up->bind_param(
        "ssssssssii",
        $nombre_via,
        $numero,
        $complemento,
        $ciudad,
        $estado_provincia,
        $codigo_postal,
        $pais,
        $tipo_direccion,
        $direccion_id,
        $usuario_id
    );

    if ($stmt_up->execute()) {
        echo "Dirección actualizada correctamente.";
    } else {
        echo "Error al actualizar la dirección: " . $stmt_up->error;
    }

    $stmt_up->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Dirección</title>
</head>
<body>
    <h1>Modificar Dirección</h1>
    <form method="POST">
        <label for="nombre_via">Calle:</label><br>
        <input type="text" id="nombre_via" name="nombre_via"
               value="<?= htmlspecialchars($direccion['nombre_via']) ?>" required><br>

        <label for="numero">Número:</label><br>
        <input type="text" id="numero" name="numero"
               value="<?= htmlspecialchars($direccion['numero']) ?>" required><br>

        <label for="complemento">Complemento:</label><br>
        <input type="text" id="complemento" name="complemento"
               value="<?= htmlspecialchars($direccion['complemento']) ?>"><br>

        <label for="ciudad">Ciudad:</label><br>
        <input type="text" id="ciudad" name="ciudad"
               value="<?= htmlspecialchars($direccion['ciudad']) ?>" required><br>

        <label for="estado_provincia">Estado/Provincia:</label><br>
        <input type="text" id="estado_provincia" name="estado_provincia"
               value="<?= htmlspecialchars($direccion['estado_provincia']) ?>" required><br>

        <label for="codigo_postal">Código Postal:</label><br>
        <input type="text" id="codigo_postal" name="codigo_postal"
               value="<?= htmlspecialchars($direccion['codigo_postal']) ?>" required><br>

        <label for="pais">País:</label><br>
        <input type="text" id="pais" name="pais"
               value="<?= htmlspecialchars($direccion['pais']) ?>" required><br>

        <label for="tipo_direccion">Tipo de Dirección:</label><br>
        <select id="tipo_direccion" name="tipo_direccion" required>
            <option value="fiscal"
                <?= $direccion['tipo_direccion'] === 'fiscal' ? 'selected' : '' ?>>
                Fiscal
            </option>
            <option value="recogida_entrega"
                <?= $direccion['tipo_direccion'] === 'recogida_entrega' ? 'selected' : '' ?>>
                Recogida/Entrega
            </option>
        </select><br><br>

        <button type="submit">Guardar Cambios</button>
    </form>

    <a href="gestionar_direcciones_usuario.php"
       style="display: block; margin-top: 20px;">
       Volver a Gestionar Direcciones
    </a>
</body>
</html>
