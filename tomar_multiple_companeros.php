<?php
session_start();
include 'conexion.php';

// ================================
// Generar token CSRF si no existe
// ================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ================================
// Verificaciones iniciales
// ================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    die("Error: Acceso no autorizado o método inválido.");
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error: Token CSRF inválido o ausente.");
}

// ================================
// Reunir IDs de porte
// ================================
$porte_ids = [];
foreach ($_POST as $key => $val) {
    if (strpos($key, 'porte_id') === 0) {
        if (is_array($val)) {
            foreach ($val as $v) {
                $pid = (int)$v;
                if ($pid > 0) { $porte_ids[$pid] = $pid; }
            }
        } else {
            $pid = (int)$val;
            if ($pid > 0) { $porte_ids[$pid] = $pid; }
        }
    }
}
$porte_ids = array_values($porte_ids);

if (empty($porte_ids)) {
    die("No se recibieron IDs de porte válidos.");
}

$usuario_id = (int)$_SESSION['usuario_id'];
$exitos = 0;
$errores = [];

foreach ($porte_ids as $porte_id) {
    $conn->begin_transaction();
    try {
        // 1) Verificar el porte y obtener usuario_creador_id
        $sql = "SELECT usuario_creador_id FROM portes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error prepare portes: " . $conn->error);
        $stmt->bind_param('i', $porte_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) throw new Exception("Porte id=$porte_id inexistente");
        $row = $res->fetch_assoc();
        $usuario_creador_id = (int)$row['usuario_creador_id'];
        $stmt->close();

        // 2) Obtener admin_id del creador
        $sql = "SELECT admin_id FROM usuarios WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error prepare admin creador: " . $conn->error);
        $stmt->bind_param('i', $usuario_creador_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) throw new Exception("Usuario creador id=$usuario_creador_id no encontrado");
        $row = $res->fetch_assoc();
        $admin_id_creador = (int)$row['admin_id'];
        $stmt->close();

        // 3) Obtener admin_id del usuario actual
        $sql = "SELECT admin_id FROM usuarios WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error prepare admin usuario: " . $conn->error);
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) throw new Exception("Usuario sesión id=$usuario_id no encontrado");
        $row = $res->fetch_assoc();
        $admin_id_sesion = (int)$row['admin_id'];
        $stmt->close();

        // 4) Insertar registro en cambios_titularidad
        $fecha_cambio = date('Y-m-d H:i:s');
        $sql = "INSERT INTO cambios_titularidad (usuario_id_1, usuario_id_2, porte_id, fecha) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error prepare cambios_titularidad: " . $conn->error);
        $stmt->bind_param('iiis', $usuario_creador_id, $usuario_id, $porte_id, $fecha_cambio);
        $stmt->execute();
        if ($stmt->affected_rows <= 0) throw new Exception("Fallo inserción cambios_titularidad");
        $stmt->close();

        if ($admin_id_creador === $admin_id_sesion) {
            // Transferencia total
            $sql = "UPDATE portes SET usuario_creador_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Error update portes: " . $conn->error);
            $stmt->bind_param('ii', $usuario_id, $porte_id);
            $stmt->execute();
            $stmt->close();

            $sql = "UPDATE ofertas_varios SET usuario_id = ? WHERE porte_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Error update ofertas_varios: " . $conn->error);
            $stmt->bind_param('ii', $usuario_id, $porte_id);
            $stmt->execute();
            $stmt->close();

            $sql = "UPDATE seleccionados_oferta SET usuario_id = ?, ofertante_id = ? WHERE porte_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Error update seleccionados_oferta: " . $conn->error);
            $stmt->bind_param('iii', $usuario_id, $usuario_id, $porte_id);
            $stmt->execute();
            $stmt->close();

            $sql = "UPDATE porte_tren SET usuario_id = ? WHERE porte_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Error update porte_tren: " . $conn->error);
            $stmt->bind_param('ii', $usuario_id, $porte_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Transferencia parcial
            $sql = "UPDATE ofertas_varios SET usuario_id = ? WHERE porte_id = ? AND usuario_id != ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Error update ofertas_varios parcial: " . $conn->error);
            $stmt->bind_param('iii', $usuario_id, $porte_id, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        $exitos++;
    } catch (Exception $e) {
        $conn->rollback();
        $errores[] = "ID $porte_id: " . $e->getMessage();
    }
}

$total = count($porte_ids);

echo "<p>Transferencias exitosas: $exitos de $total</p>";
if (!empty($errores)) {
    echo "<p>Errores:</p><ul>";
    foreach ($errores as $err) {
        echo "<li>".htmlspecialchars($err)."</li>";
    }
    echo "</ul>";
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'portes_nuevos_recibidos.php';
$sep = (strpos($referer, '?')===false) ? '?' : '&';
$referer .= $sep . '_t=' . time();

echo "<a href='".htmlspecialchars($referer)."'".
     " style='display:inline-block; margin-top:10px; padding:10px 20px;".
     " background-color:#007bff; color:#fff; text-decoration:none;".
     " border-radius:5px; font-size:16px;'>Volver</a>";

$conn->close();
?>
