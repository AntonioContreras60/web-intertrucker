<?php
session_start();

/*────────  CAMBIO NUEVO:  asegurar Path=/ y eliminar la vieja  ────────*/
if (isset($_COOKIE[session_name()])) {
    // 1) Sobrescribimos la cookie actual con Path=/
    if (PHP_VERSION_ID >= 70300) {
        setcookie(session_name(), session_id(), [
            'expires'  => 0,
            'path'     => '/',
            'domain'   => $_SERVER['HTTP_HOST'],
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        // Compatibilidad PHP 5.x-7.2
        setcookie(session_name(), session_id(), 0, '/');
    }

    // 2) Borramos la antigua limitada a /Perfil (si existe)
    setcookie(session_name(), '', time() - 3600, '/Perfil');
}
/*───────────────────────────────────────────────────────────────────────*/

include_once '../conexion.php';   // Conexión a la base de datos

$email      = $_POST['email'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

/* --- Lógica original intacta ------------------------------------------------ */
$stmt = $conn->prepare("SELECT id, contrasena, nombre_usuario, rol, admin_id FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if (password_verify($contrasena, $row['contrasena'])) {
        $_SESSION['usuario_id']     = $row['id'];
        $_SESSION['nombre_usuario'] = $row['nombre_usuario'];
        $_SESSION['rol']            = $row['rol'];
        $_SESSION['admin_id']       = $row['admin_id'];

        if ($row['rol'] === 'camionero') {
            $stmt_camionero = $conn->prepare("SELECT id FROM camioneros WHERE usuario_id = ?");
            $stmt_camionero->bind_param('i', $row['id']);
            $stmt_camionero->execute();
            $rc = $stmt_camionero->get_result();

            if ($rc->num_rows > 0) {
                $camionero_id = $rc->fetch_assoc()['id'];

                $stmt_tren = $conn->prepare("
                    SELECT tren_id
                    FROM tren_camionero
                    WHERE camionero_id = ? AND fin_tren_camionero IS NULL
                    LIMIT 1
                ");
                $stmt_tren->bind_param('i', $camionero_id);
                $stmt_tren->execute();
                $rt = $stmt_tren->get_result();

                if ($rt->num_rows > 0) {
                    $tren_id = $rt->fetch_assoc()['tren_id'];
                    header("Location: /tren_portes_camionero.php?tren_id=$tren_id");
                    exit;
                } else {
                    header("Location: /error.php?error=no_tren_asignado");
                    exit;
                }
            } else {
                header("Location: /error.php?error=no_perfil_camionero");
                exit;
            }

        } else { // admin o gestor
            header("Location: /portes_nuevos_recibidos.php");
            exit;
        }

    } else { // contraseña incorrecta
        header("Location: inicio_sesion.php?error=contraseña_incorrecta");
        exit;
    }

} else { // email no registrado
    header("Location: inicio_sesion.php?error=email_no_registrado");
    exit;
}

$stmt->close();
$conn->close();
?>
