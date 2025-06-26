<?php
session_start();
include 'conexion.php';  // Ajusta si tu archivo de conexión se llama distinto

// ======================================================
// CSRF: Generar token si no existe
// ======================================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Para depuración (puedes comentar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1) Verificar sesión y POST
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Acceso no autorizado o uso inválido.");
}

// ======================================================
// CSRF: Verificar token
// ======================================================
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error: Token CSRF inválido o ausente.");
}

// Verificar que se haya enviado porte_id
if (!isset($_POST['porte_id'])) {
    die("Error: No se proporcionó porte_id.");
}

$usuario_id = $_SESSION['usuario_id'];
$porte_id   = (int)$_POST['porte_id'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // 2) Verificar que el porte existe y obtener su usuario_creador_id
    $sql_porte = "SELECT usuario_creador_id FROM portes WHERE id = ?";
    $stmt_porte = $conn->prepare($sql_porte);
    if (!$stmt_porte) {
        throw new Exception("Error en prepare de portes: " . $conn->error);
    }
    $stmt_porte->bind_param('i', $porte_id);
    $stmt_porte->execute();
    $result_porte = $stmt_porte->get_result();

    if ($result_porte->num_rows === 0) {
        throw new Exception("El porte con id=$porte_id no existe.");
    }

    $row_porte          = $result_porte->fetch_assoc();
    $usuario_creador_id = (int)$row_porte['usuario_creador_id'];
    $stmt_porte->close();

    // 3) Obtener el admin_id del usuario creador del porte
    $sql_admin_creador = "SELECT admin_id FROM usuarios WHERE id = ? LIMIT 1";
    $stmt_admin_creador = $conn->prepare($sql_admin_creador);
    if (!$stmt_admin_creador) {
        throw new Exception("Error en prepare de admin_id creador: " . $conn->error);
    }
    $stmt_admin_creador->bind_param('i', $usuario_creador_id);
    $stmt_admin_creador->execute();
    $result_admin_creador = $stmt_admin_creador->get_result();
    if ($result_admin_creador->num_rows === 0) {
        throw new Exception("No se encontró el usuario creador con id=$usuario_creador_id.");
    }
    $row_creador      = $result_admin_creador->fetch_assoc();
    $admin_id_creador = (int)$row_creador['admin_id'];
    $stmt_admin_creador->close();

    // 4) Obtener el admin_id del usuario actual (sesión)
    $sql_admin_user = "SELECT admin_id FROM usuarios WHERE id = ? LIMIT 1";
    $stmt_admin_user = $conn->prepare($sql_admin_user);
    if (!$stmt_admin_user) {
        throw new Exception("Error en prepare de admin_id usuario sesión: " . $conn->error);
    }
    $stmt_admin_user->bind_param('i', $usuario_id);
    $stmt_admin_user->execute();
    $result_admin_user = $stmt_admin_user->get_result();
    if ($result_admin_user->num_rows === 0) {
        throw new Exception("No se encontró el usuario en sesión con id=$usuario_id.");
    }
    $row_user       = $result_admin_user->fetch_assoc();
    $admin_id_sesion= (int)$row_user['admin_id'];
    $stmt_admin_user->close();

    // 5) Insertar un registro en cambios_titularidad (siempre)
    $fecha_cambio = date('Y-m-d H:i:s');

    // "usuario_id_1" = creador actual, "usuario_id_2" = usuario en sesión (que lo toma)
    $sql_cambio = "
      INSERT INTO cambios_titularidad (usuario_id_1, usuario_id_2, porte_id, fecha)
      VALUES (?, ?, ?, ?)
    ";
    $stmt_cambio = $conn->prepare($sql_cambio);
    if (!$stmt_cambio) {
        throw new Exception("Error en prepare de cambios_titularidad: " . $conn->error);
    }
    $stmt_cambio->bind_param('iiis', $usuario_creador_id, $usuario_id, $porte_id, $fecha_cambio);
    $stmt_cambio->execute();

    if ($stmt_cambio->affected_rows <= 0) {
        throw new Exception("No se pudo registrar el cambio de titularidad en la tabla cambios_titularidad.");
    }
    $stmt_cambio->close();

    // 6) Verificar si ambos usuarios pertenecen a la misma empresa
    if ($admin_id_creador === $admin_id_sesion) {
        // === CASO COMPLETO: misma empresa => transferencia total ===

        // (a) Actualizar porte: usuario_creador_id
        $sql_actualizar_portes = "UPDATE portes SET usuario_creador_id = ? WHERE id = ?";
        $stmt_portes = $conn->prepare($sql_actualizar_portes);
        if (!$stmt_portes) {
            throw new Exception("Error en prepare update portes: " . $conn->error);
        }
        $stmt_portes->bind_param('ii', $usuario_id, $porte_id);
        $stmt_portes->execute();
        $stmt_portes->close();

        // (b) Update ofertas_varios
        $sql_ofertas_varios = "UPDATE ofertas_varios SET usuario_id = ? WHERE porte_id = ?";
        $stmt_ov = $conn->prepare($sql_ofertas_varios);
        if (!$stmt_ov) {
            throw new Exception("Error en prepare update ofertas_varios: " . $conn->error);
        }
        $stmt_ov->bind_param('ii', $usuario_id, $porte_id);
        $stmt_ov->execute();
        $stmt_ov->close();

        // (c) Update seleccionados_oferta
        $sql_sel_oferta = "UPDATE seleccionados_oferta SET usuario_id = ?, ofertante_id = ? WHERE porte_id = ?";
        $stmt_so = $conn->prepare($sql_sel_oferta);
        if (!$stmt_so) {
            throw new Exception("Error en prepare update seleccionados_oferta: " . $conn->error);
        }
        // Asumimos ofertante_id = $usuario_id también
        $stmt_so->bind_param('iii', $usuario_id, $usuario_id, $porte_id);
        $stmt_so->execute();
        $stmt_so->close();

        // (d) Update porte_tren
        $sql_porte_tren = "UPDATE porte_tren SET usuario_id = ? WHERE porte_id = ?";
        $stmt_pt = $conn->prepare($sql_porte_tren);
        if (!$stmt_pt) {
            throw new Exception("Error en prepare update porte_tren: " . $conn->error);
        }
        $stmt_pt->bind_param('ii', $usuario_id, $porte_id);
        $stmt_pt->execute();
        $stmt_pt->close();

        $mensaje = "Titularidad COMPLETA: el porte se transfirió a tu usuario (misma empresa).";
    } else {
        // === CASO PARCIAL: distinta empresa => no se actualiza usuario_creador_id en portes
        $sql_ofertas_varios_parcial = "
          UPDATE ofertas_varios
          SET usuario_id = ?
          WHERE porte_id = ?
            AND usuario_id != ?
        ";
        $stmt_ov_parcial = $conn->prepare($sql_ofertas_varios_parcial);
        if (!$stmt_ov_parcial) {
            throw new Exception("Error update ofertas_varios parcial: " . $conn->error);
        }
        $stmt_ov_parcial->bind_param('iii', $usuario_id, $porte_id, $usuario_id);
        $stmt_ov_parcial->execute();
        $stmt_ov_parcial->close();

        $mensaje = "Titularidad PARCIAL: el porte sigue con otro creador (empresa distinta).";
    }

    // 7) Confirmar transacción
    $conn->commit();

    // Mensaje final
    echo "<p>$mensaje</p>";
    echo "<p>Se registró el cambio en 'cambios_titularidad'.</p>";

    // Enviar al referer o a portes_nuevos_recibidos.php
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (empty($referer)) {
        $referer = 'portes_nuevos_recibidos.php';
    }
    $sep = (strpos($referer, '?') === false) ? '?' : '&';
    $referer .= $sep . '_t=' . time();

    echo "<a href='".htmlspecialchars($referer)."'
             style='display:inline-block; margin-top:10px; padding:10px 20px;
                    background-color:#007bff; color:#fff; text-decoration:none;
                    border-radius:5px; font-size:16px;'>
             Volver
          </a>";

} catch (Exception $e) {
    $conn->rollback();
    die("<p>Error: " . $e->getMessage() . "</p>");
} finally {
    $conn->close();
}
