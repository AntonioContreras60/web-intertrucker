<?php
session_start();

// 1) Leer user_id y token_sesion desde GET
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$token_sesion = $_GET['token_sesion'] ?? '';

// 2) Verificar datos básicos
if ($user_id <= 0 || empty($token_sesion)) {
    die("Datos inválidos");
}

// 3) Conexión a la BD (ajusta tus credenciales si cambian)
try {
    $pdo = new PDO(
        "mysql:host=db5016197746.hosting-data.io;dbname=dbs13181300;charset=utf8mb4",
        "dbu4085097",
        "123intertruckerya"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 4) Buscar token_sesion, admin_id, y nombre_usuario en la tabla usuarios
$stmt = $pdo->prepare("
    SELECT token_sesion, admin_id, nombre_usuario
    FROM usuarios
    WHERE id = :uid
    LIMIT 1
");
$stmt->execute([':uid' => $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    // No existe un usuario con ese ID
    die("Token inválido o expirado");
}

// 5) Comparar con el token que recibimos
if ($row['token_sesion'] === $token_sesion) {
    // 6) Crear la sesión en PHP
    $_SESSION['usuario_id']     = $user_id;
    $_SESSION['admin_id']       = $row['admin_id'];       // si tu panel requiere admin_id
    $_SESSION['nombre_usuario'] = $row['nombre_usuario']; // para mostrarlo en header

    // 7) Redirigir a portes_nuevos_recibidos.php
    header("Location: portes_nuevos_recibidos.php");
    exit;
} else {
    die("Token inválido o expirado");
}
