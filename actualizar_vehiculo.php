<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor']);
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = intval($_POST['vehiculo_id']);

    // Comprobar que el vehículo pertenece a la empresa del usuario
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

    $vehiculo_id            = intval($_POST['vehiculo_id']);
    $matricula              = trim($_POST['matricula'] ?? '');
    $marca                  = trim($_POST['marca'] ?? '');
    $modelo                 = trim($_POST['modelo'] ?? '');
    $ano_fabricacion        = $_POST['ano_fabricacion'] ?? null;
    $nivel_1                = $_POST['nivel_1'] ?? '';
    $nivel_2                = $_POST['nivel_2'] ?? '';
    $nivel_3                = $_POST['nivel_3'] ?? '';
    $capacidad              = $_POST['capacidad'] ?? null;
    $volumen                = $_POST['volumen'] ?? null;
    $capacidad_arrastre     = $_POST['capacidad_arrastre'] ?? null;
    $numero_ejes            = $_POST['numero_ejes'] ?? null;
    $temperatura_controlada = isset($_POST['temperatura_controlada']) ? 1 : 0;
    $forma_carga_lateral    = isset($_POST['forma_carga_lateral']) ? 1 : 0;
    $forma_carga_detras     = isset($_POST['forma_carga_detras']) ? 1 : 0;
    $forma_carga_arriba     = isset($_POST['forma_carga_arriba']) ? 1 : 0;
    $adr                    = isset($_POST['adr']) ? 1 : 0;
    $doble_conductor        = isset($_POST['doble_conductor']) ? 1 : 0;
    $plataforma_elevadora   = isset($_POST['plataforma_elevadora']) ? 1 : 0;
    $telefono               = trim($_POST['telefono'] ?? '');
    $observaciones          = trim($_POST['observaciones'] ?? '');
    $activo                 = isset($_POST['activo']) ? 1 : 0;

    $sql = "UPDATE vehiculos SET
                nivel_1 = ?,
                nivel_2 = ?,
                nivel_3 = ?,
                matricula = ?,
                marca = ?,
                modelo = ?,
                ano_fabricacion = ?,
                capacidad = ?,
                capacidad_arrastre = ?,
                volumen = ?,
                temperatura_controlada = ?,
                forma_carga_lateral = ?,
                forma_carga_detras = ?,
                forma_carga_arriba = ?,
                adr = ?,
                doble_conductor = ?,
                plataforma_elevadora = ?,
                numero_ejes = ?,
                telefono = ?,
                observaciones = ?,
                activo = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssssidddiiiiiiiissii",
            $nivel_1,
            $nivel_2,
            $nivel_3,
            $matricula,
            $marca,
            $modelo,
            $ano_fabricacion,
            $capacidad,
            $capacidad_arrastre,
            $volumen,
            $temperatura_controlada,
            $forma_carga_lateral,
            $forma_carga_detras,
            $forma_carga_arriba,
            $adr,
            $doble_conductor,
            $plataforma_elevadora,
            $numero_ejes,
            $telefono,
            $observaciones,
            $activo,
            $vehiculo_id
        );
        $stmt->execute();
        $stmt->close();
    }

    header('Location: ver_detalles_vehiculo.php?vehiculo_id=' . $vehiculo_id);
    exit();
}
?>
