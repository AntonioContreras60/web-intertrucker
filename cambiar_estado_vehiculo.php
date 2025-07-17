<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor']);
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = intval($_POST['vehiculo_id']);
    $estado_actual = isset($_POST['estado_actual']) ? (int)$_POST['estado_actual'] : 0;

    // Verificar que el vehículo pertenece a la empresa del usuario
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $chk = $conn->prepare(
        "SELECT v.id
           FROM vehiculos v
           JOIN usuarios u ON v.usuario_id = u.id
          WHERE v.id = ? AND u.admin_id = ?"
    );
    $chk->bind_param('ii', $vehiculo_id, $admin_id);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        http_response_code(403);
        exit('Acceso denegado');
    }
    $chk->close();
    $nuevo_estado = $estado_actual == 1 ? 0 : 1;

    $sql = "UPDATE vehiculos SET activo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $nuevo_estado, $vehiculo_id);
        if ($stmt->execute()) {
            // Redirigir de vuelta a la página principal de vehículos después de actualizar correctamente
            header('Location: my_trucks.php');
            exit();
        } else {
            echo "Error al actualizar el estado: " . $stmt->error;
        }
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
    }
}
?>
