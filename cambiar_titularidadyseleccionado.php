<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(["administrador","gestor"]);
include 'conexion.php'; // Conexión a la base de datos

// ======================================================
// CSRF: Generar token si no existe
// ======================================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Para ver errores (puedes quitarlo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Verificar que llega por POST (y que hay porte_id)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['porte_id'])) {
    echo "<p>Acceso inválido. Se requiere formulario por POST con 'porte_id'.</p>";
    exit;
}

// ======================================================
// CSRF: Verificar token
// ======================================================
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error: Token CSRF inválido o ausente.");
}

// Obtener el ID del usuario de sesión
$usuario_id = $_SESSION['usuario_id'];

// Obtener el ID del porte
$porte_id = (int)$_POST['porte_id'];

try {
$admin_id = $_SESSION["admin_id"] ?? 0;
$chk = $conn->prepare("SELECT p.id FROM portes p JOIN usuarios u ON p.usuario_creador_id=u.id WHERE p.id=? AND u.admin_id=? LIMIT 1");
$chk->bind_param("ii", $porte_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    http_response_code(403);
    exit("Acceso denegado");
}
$chk->close();
    // Iniciar una transacción
    $conn->begin_transaction();

    // Consultar el usuario actual que está a cargo del porte en la tabla `seleccionados_oferta`
    // (En tu snippet original, se ve que actualizas la tabla seleccionados_oferta con `usuario_id = $usuario_id`)
    // Insertar el cambio de titularidad en la tabla cambios_titularidad
    $sql_cambio = "
        INSERT INTO cambios_titularidad (usuario_id_1, usuario_id_2, porte_id, fecha) 
        VALUES (?, ?, ?, NOW())
    ";
    $stmt_cambio = $conn->prepare($sql_cambio);
    if (!$stmt_cambio) {
        throw new Exception("Error en la preparación de la consulta de cambio de titularidad: " . $conn->error);
    }
    $stmt_cambio->bind_param('iii', $usuario_id, $usuario_id, $porte_id);
    $stmt_cambio->execute();
    $stmt_cambio->close();

    // Actualizar el usuario receptor en la tabla seleccionados_oferta
    $sql_actualizar = "
        UPDATE seleccionados_oferta 
        SET usuario_id = ? 
        WHERE porte_id = ?
    ";
    $stmt_actualizar = $conn->prepare($sql_actualizar);
    if (!$stmt_actualizar) {
        throw new Exception("Error en la preparación de la consulta de actualización de titularidad: " . $conn->error);
    }
    $stmt_actualizar->bind_param('ii', $usuario_id, $porte_id);
    $stmt_actualizar->execute();
    $stmt_actualizar->close();

    // Confirmar transacción
    $conn->commit();

    echo "<p>Titularidad del porte cambiada correctamente.</p>";
    echo "<a href='portes_nuevos_recibidos_todos.php'>Volver a Portes Recibidos</a>";

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

// Cerrar la conexión
$conn->close();
