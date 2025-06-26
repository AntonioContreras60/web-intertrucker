<?php
include '../conexion.php';
session_start();

/* ---------- Mostrar errores en desarrollo (quitar en producción) ---------- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ---------- Procesar el formulario ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ====== 1) Recoger y sanear los datos ====== */
    $nombre_empresa = trim($_POST['nombre_empresa']);
    $coincide_admin = $_POST['coincide_admin'] ?? 'si';

    // Nombre y apellidos del administrador
    if ($coincide_admin === 'si') {
        $nombre_usuario = $nombre_empresa;
        $apellidos      = '';
    } else {
        $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
        $apellidos      = trim($_POST['apellidos'] ?? '');
    }

    $email          = trim($_POST['email']);
    $contrasena     = $_POST['contrasena'];
    $confContrasena = $_POST['confirmar_contrasena'];
    $cif            = trim($_POST['cif']);

    /* --- Dirección fiscal --- */
    $nombre_via        = trim($_POST['nombre_via']);
    $numero_via        = trim($_POST['numero_via']);
    $complemento       = trim($_POST['complemento'] ?? '');
    $codigo_postal     = trim($_POST['codigo_postal']);
    $ciudad            = trim($_POST['ciudad']);
    $estado_provincia  = trim($_POST['estado_provincia']);
    $pais              = trim($_POST['pais']);
    $telefono          = trim($_POST['telefono']);

    /* --- ¿También camionero? --- */
    $es_camionero = isset($_POST['es_camionero']);
    $tipo_carnet  = trim($_POST['tipo_carnet'] ?? '');

    /* ====== 2) Validaciones ====== */
    $errores = [];

    // 2.1 Nombre de empresa
    if ($nombre_empresa === '' ||
        !preg_match("/^[\p{L}\p{N}\s\.\,\-\&\(\)\']+$/u", $nombre_empresa)) {
        $errores[] = 'Nombre de empresa no válido.';
    }

    // 2.2 Nombre/apellidos si no coinciden
    if ($coincide_admin === 'no') {
        if ($nombre_usuario !== '' &&
            !preg_match("/^[\p{L}\p{N}\s\.\,\-\&\(\)\']+$/u", $nombre_usuario)) {
            $errores[] = 'Nombre de administrador no válido.';
        }
        if ($apellidos !== '' &&
            !preg_match("/^[\p{L}\p{N}\s\.\,\-\&\(\)\']+$/u", $apellidos)) {
            $errores[] = 'Apellidos no válidos.';
        }
    }

    // 2.3 Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Correo electrónico no válido.';
    }

    // 2.4 Contraseña
    if (strlen($contrasena) < 8 || $contrasena !== $confContrasena) {
        $errores[] = 'Las contraseñas no coinciden o son demasiado cortas.';
    }

    // 2.5 CIF
    if ($cif === '' || !preg_match('/^[A-Za-z0-9]+$/', $cif)) {
        $errores[] = 'CIF no válido.';
    }

    // 2.6 Dirección fiscal mínima
    if ($nombre_via === '' || $codigo_postal === '' || $ciudad === '' || $pais === '') {
        $errores[] = 'Debes completar la dirección fiscal (calle, CP, ciudad y país).';
    }

    // 2.7 Teléfono
    if ($telefono === '' || !preg_match('/^\+?[0-9\s\-]{7,15}$/', $telefono)) {
        $errores[] = 'Teléfono no válido.';
    }

    // 2.8 Carnet si es camionero
    if ($es_camionero) {
        if ($tipo_carnet === '' ||
            !preg_match('/^[A-Za-z0-9\+\-\.]{1,10}$/', $tipo_carnet)) {
            $errores[] = 'Tipo de carnet no válido.';
        }
    }

    /* ----- Detener si hay errores ----- */
    if (!empty($errores)) {
        foreach ($errores as $err) {
            echo "<p style='color:red;'>$err</p>";
        }
        exit;
    }

    /* ====== 3) Utilidades ====== */
    function iso3($paisTxt) {
        // Mapeo reducido; amplíalo según tus necesidades
        $map = [
            'España'=>'ESP','Francia'=>'FRA','Portugal'=>'PRT','Alemania'=>'DEU',
            'Estados Unidos'=>'USA','México'=>'MEX','Brasil'=>'BRA',
            'Argentina'=>'ARG','Colombia'=>'COL'
        ];
        return $map[$paisTxt] ?? 'UNK';
    }
    $codigo_pais_iso3 = iso3($pais);

    $token_verificacion = bin2hex(random_bytes(32));
    $contrasena_hash    = password_hash($contrasena, PASSWORD_DEFAULT);

    /* ====== 4) Transacción: usuario + dirección + (opcional) camionero ====== */
    $conn->begin_transaction();

    try {
        /* 4.1 Insertar usuario */
        $stmtU = $conn->prepare("
            INSERT INTO usuarios
              (nombre_empresa, nombre_usuario, apellidos, email, contrasena,
               cif, telefono, rol, token_verificacion, email_verificado)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, 'administrador', ?, 0)
        ");
        if (!$stmtU) throw new Exception("Prepare usuarios: {$conn->error}");

        $stmtU->bind_param(
            'ssssssss',
            $nombre_empresa,
            $nombre_usuario,
            $apellidos,
            $email,
            $contrasena_hash,
            $cif,
            $telefono,
            $token_verificacion
        );
        if (!$stmtU->execute()) throw new Exception("Execute usuarios: {$stmtU->error}");

        $usuario_id = $stmtU->insert_id;
        $stmtU->close();

        /* 4.2 Insertar dirección fiscal */
        $stmtD = $conn->prepare("
            INSERT INTO direcciones
              (nombre_via, numero, complemento, ciudad, estado_provincia,
               codigo_postal, pais, region, tipo_direccion,
               usuario_id, codigo_pais)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, '', 'fiscal', ?, ?)
        ");
        if (!$stmtD) throw new Exception("Prepare direcciones: {$conn->error}");

        /* ---- CADENA DE TIPOS CORREGIDA: 7s + i + s = 9 tipos ---- */
        $stmtD->bind_param(
            'sssssssis',
            $nombre_via,
            $numero_via,
            $complemento,
            $ciudad,
            $estado_provincia,
            $codigo_postal,
            $pais,
            $usuario_id,
            $codigo_pais_iso3
        );
        if (!$stmtD->execute()) throw new Exception("Execute direcciones: {$stmtD->error}");
        $stmtD->close();

        /* 4.3 Insertar camionero si procede */
        if ($es_camionero) {
            $stmtC = $conn->prepare("
                INSERT INTO camioneros (usuario_id, tipo_carnet, activo)
                VALUES (?, ?, 1)
            ");
            if (!$stmtC) throw new Exception("Prepare camioneros: {$conn->error}");
            $stmtC->bind_param('is', $usuario_id, $tipo_carnet);
            if (!$stmtC->execute()) throw new Exception("Execute camioneros: {$stmtC->error}");
            $stmtC->close();
        }

        /* 4.4 Confirmar transacción */
        $conn->commit();
        echo "<p>Usuario y dirección fiscal registrados con éxito.</p>";

        /* ====== 5) Correo de verificación ====== */
        $verification_link = "https://intertrucker.net/verificar_email.php?token=$token_verificacion";
        $subject = 'Verifica tu cuenta en InterTrucker';
        $message = "Hola $nombre_usuario,\n\nHaz clic en el siguiente enlace para verificar tu cuenta:\n$verification_link\n\nGracias.";
        $headers = "From: info@intertrucker.net\r\n";

        $mail_ok = mail($email, $subject, $message, $headers);

        if ($mail_ok) {
            echo "<p>Revisa tu correo (también la carpeta spam) para verificar tu cuenta.</p>";
            echo "<a href='inicio_sesion.php'><button>Iniciar sesión</button></a>";
        } else {
            /* depuración básica */
            echo "<p style='color:red;'>No se pudo enviar el correo de verificación.</p>";
            $lastErr = error_get_last();
            if ($lastErr) {
                echo "<pre style='color:red;'>Detalles: ".print_r($lastErr, true)."</pre>";
            }
        }


    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>Error al registrar: {$e->getMessage()}</p>";
    }

    /* ====== 6) Cerrar conexión ====== */
    $conn->close();
}
?>
