<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor']);
include 'conexion.php'; // Conexión a la base de datos

// Verificar si los parámetros necesarios están presentes
if (!isset($_GET['tren_id']) || !isset($_GET['camionero_anterior_id']) || !isset($_GET['nuevo_camionero_id'])) {
    die("Error: No se recibieron los parámetros necesarios.");
}

$tren_id = (int)$_GET['tren_id'];
$camionero_anterior_id = (int)$_GET['camionero_anterior_id'];
$nuevo_camionero_id = (int)$_GET['nuevo_camionero_id'];
$admin_id = $_SESSION['admin_id'] ?? 0;

// Verificar que el tren pertenece a la empresa
$chk = $conn->prepare(
    "SELECT t.id FROM tren t
     JOIN tren_vehiculos tv ON tv.tren_id = t.id
     JOIN vehiculos v ON tv.vehiculo_id = v.id
     JOIN usuarios u ON v.usuario_id = u.id
    WHERE t.id = ? AND u.admin_id = ? LIMIT 1"
);
$chk->bind_param('ii', $tren_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    die('Acceso denegado');
}
$chk->close();

// Verificar que el nuevo camionero pertenece a la empresa
$chk = $conn->prepare(
    "SELECT c.id FROM camioneros c JOIN usuarios u ON c.usuario_id=u.id WHERE c.id=? AND u.admin_id=?"
);
$chk->bind_param('ii', $nuevo_camionero_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    die('Acceso denegado');
}
$chk->close();
$fecha_actual = date("Y-m-d H:i:s"); // Fecha actual

// --- Actualizar el camionero anterior poniendo la fecha de fin ---
$sql_actualizar = "UPDATE tren_camionero 
                   SET fin_tren_camionero = ? 
                   WHERE tren_id = ? AND camionero_id = ? AND fin_tren_camionero IS NULL";
$stmt_actualizar = $conn->prepare($sql_actualizar);
$stmt_actualizar->bind_param("sii", $fecha_actual, $tren_id, $camionero_anterior_id);
if (!$stmt_actualizar->execute()) {
    die("Error al actualizar el camionero anterior: " . $conn->error);
}

// --- Verificar si la relación ya existe para evitar duplicados ---
$sql_verificar = "SELECT id FROM tren_camionero 
                  WHERE tren_id = ? AND camionero_id = ? AND fin_tren_camionero IS NULL";
$stmt_verificar = $conn->prepare($sql_verificar);
$stmt_verificar->bind_param("ii", $tren_id, $nuevo_camionero_id);
$stmt_verificar->execute();
$result_verificar = $stmt_verificar->get_result();

$mensaje = "";
if ($result_verificar->num_rows === 0) {
    // Si no existe, insertar la nueva relación
    $sql_insertar = "INSERT INTO tren_camionero (tren_id, camionero_id, inicio_tren_camionero) 
                     VALUES (?, ?, ?)";
    $stmt_insertar = $conn->prepare($sql_insertar);
    $stmt_insertar->bind_param("iis", $tren_id, $nuevo_camionero_id, $fecha_actual);
    if ($stmt_insertar->execute()) {
        $mensaje = "Camionero actualizado correctamente.";
    } else {
        $mensaje = "Error al insertar el nuevo camionero: " . $conn->error;
    }
    $stmt_insertar->close();
} else {
    $mensaje = "La relación entre el tren y el camionero ya existe. No se realizaron cambios.";
}

// Cerrar las conexiones
$stmt_actualizar->close();
$stmt_verificar->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Camionero</title>
    <link rel="stylesheet" href="styles.css"> <!-- Vincula tus estilos -->
</head>
<body>
    <h1>Resultado de la Actualización</h1>
    <p><?php echo htmlspecialchars($mensaje); ?></p>

    <!-- Botón para ir a los detalles del camionero -->
    <a href="camionero_detalles.php?tren_id=<?php echo $tren_id; ?>&camionero_id=<?php echo $nuevo_camionero_id; ?>" class="button">
        Ver Detalles del Camionero
    </a>

    <!-- Botón para volver a portes_trucks -->
    <br><br>
    <a href="portes_trucks.php" class="button">Volver a la Lista de Portes</a>
</body>
</html>
