<?php
session_start();
include 'conexion.php'; // Ajusta la ruta si tu archivo de conexión está en otro directorio

// ======================================================
// CSRF: Generar token si no existe
// ======================================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Mostrar errores (puedes desactivarlo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Verificar método y que porte_id llegue
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['porte_id'])) {
    echo "<p>Acceso inválido: Se requiere POST con porte_id.</p>";
    exit();
}

// ======================================================
// CSRF: Verificar token
// ======================================================
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error: Token CSRF inválido o ausente.");
}

$porte_id = (int)$_POST['porte_id'];

try {
    $conn->begin_transaction();

    // 1) Consultar la oferta pendiente
    $sql_oferta_actual = "
        SELECT usuario_id
        FROM ofertas_varios
        WHERE porte_id = ?
          AND estado_oferta = 'pendiente'
    ";
    $stmt_oferta_actual = $conn->prepare($sql_oferta_actual);
    $stmt_oferta_actual->bind_param("i", $porte_id);
    $stmt_oferta_actual->execute();
    $result_oferta_actual = $stmt_oferta_actual->get_result();

    if ($result_oferta_actual->num_rows > 0) {
        $row_oferta_actual = $result_oferta_actual->fetch_assoc();
        $usuario_actual_id = $row_oferta_actual['usuario_id'];

        // 2) Insertar el cambio de titularidad en cambios_titularidad
        $sql_insert_cambio = "
            INSERT INTO cambios_titularidad (usuario_id_1, usuario_id_2, porte_id)
            VALUES (?, ?, ?)
        ";
        $stmt_insert_cambio = $conn->prepare($sql_insert_cambio);
        $stmt_insert_cambio->bind_param("iii", $usuario_actual_id, $usuario_id, $porte_id);
        $stmt_insert_cambio->execute();
        $stmt_insert_cambio->close();

        // 3) Actualizar el usuario a cargo en ofertas_varios
        $sql_update_oferta = "
            UPDATE ofertas_varios
            SET usuario_id = ?
            WHERE porte_id = ?
              AND estado_oferta = 'pendiente'
        ";
        $stmt_update_oferta = $conn->prepare($sql_update_oferta);
        $stmt_update_oferta->bind_param("ii", $usuario_id, $porte_id);
        $stmt_update_oferta->execute();
        $stmt_update_oferta->close();

        echo "Cambio de titularidad realizado con éxito.";
    } else {
        echo "Error: No se encontró ninguna oferta pendiente para este porte.";
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: No se pudo realizar el cambio de titularidad. " . $e->getMessage();
}

$stmt_oferta_actual->close();
$conn->close();
