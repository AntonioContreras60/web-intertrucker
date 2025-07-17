<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor']);
include 'db_connection.php';

$porte_id = isset($_GET['porte_id']) ? (int)$_GET['porte_id'] : 0;
$admin_id = $_SESSION['admin_id'] ?? 0;
$chk = $conn->prepare("SELECT p.id FROM portes p JOIN usuarios u ON p.usuario_creador_id=u.id WHERE p.id=? AND u.admin_id=?");
$chk->bind_param('ii', $porte_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    http_response_code(403);
    exit('Acceso denegado');
}
$chk->close();

$stmt_acept = $conn->prepare("SELECT u.id, u.nombre FROM usuarios u JOIN ofertas o ON u.id = o.destinatario_id WHERE o.porte_id = ? AND o.estado = 'aceptado'");
$stmt_acept->bind_param('i', $porte_id);
$stmt_acept->execute();
$aceptantes = $stmt_acept->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aceptante_id = (int)$_POST['aceptante_id'];
    $stmt_up = $conn->prepare("UPDATE portes SET asignado_a=?, estado='asignado' WHERE id=?");
    $stmt_up->bind_param('ii', $aceptante_id, $porte_id);
    $stmt_up->execute();
    $stmt_up->close();
    header('Location: portes_asignados.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar Aceptante</title>
</head>
<body>
    <h1>Seleccionar Aceptante para el Porte</h1>
    <form action="ver_aceptantes.php?porte_id=<?php echo $porte_id; ?>" method="POST">
        <fieldset>
            <legend>Usuarios que Han Aceptado la Oferta</legend>
            <?php while ($row = mysqli_fetch_assoc($aceptantes)) { ?>
                <input type='radio' name='aceptante_id' value='<?php echo $row['id']; ?>'> <?php echo $row['nombre']; ?><br>
            <?php } ?>
        </fieldset>
        <button type="submit">Asignar Porte</button>
    </form>
</body>
</html>
