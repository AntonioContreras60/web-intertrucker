<?php
// archivo: verificar-credenciales.php
header('Content-Type: application/json');

// 1) Leer el cuerpo del POST (que viene en JSON)
$json = file_get_contents("php://input");
if (!$json) {
    echo json_encode([
        "success" => false,
        "message" => "No se ha recibido JSON en el POST."
    ]);
    exit;
}

$data = json_decode($json, true);
if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "El contenido recibido no es JSON válido."
    ]);
    exit;
}

$email       = isset($data['email']) ? trim($data['email']) : '';
$contrasena  = isset($data['contrasena']) ? trim($data['contrasena']) : '';

// 2) Comprobamos que no falte nada
if (empty($email) || empty($contrasena)) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan email o contraseña."
    ]);
    exit;
}

// 3) Conexión PDO
include_once __DIR__ . '/../conexion.php';
if (!$pdo) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión a la base de datos"
    ]);
    exit;
}

// 5) Buscar al usuario por email
$stmt = $pdo->prepare("
    SELECT *
    FROM usuarios
    WHERE email = :mail
    LIMIT 1
");
$stmt->execute([':mail' => $email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    // No existe ese email
    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado."
    ]);
    exit;
}

// 6) Verificar contraseña (hash en 'contrasena')
$storedHash = $row['contrasena'] ?? '';
if (!password_verify($contrasena, $storedHash)) {
    echo json_encode([
        "success" => false,
        "message" => "Contraseña incorrecta."
    ]);
    exit;
}

// Hasta aquí, credenciales correctas
$usuarioId = (int)$row['id'];

// 7) Buscar si es camionero
$camioneroId = 0;
$camioneroStmt = $pdo->prepare("SELECT id FROM camioneros WHERE usuario_id = :uid LIMIT 1");
$camioneroStmt->execute([':uid' => $usuarioId]);
$camRow = $camioneroStmt->fetch(PDO::FETCH_ASSOC);
if ($camRow) {
    $camioneroId = (int)$camRow['id'];
}

// 8) Buscar si ya tiene tren activo
$trenId     = 0;
$inicioTren = '';
$finTren    = '';

if ($camioneroId > 0) {
    $tcStmt = $pdo->prepare("
        SELECT tren_id, inicio_tren_camionero, fin_tren_camionero
        FROM tren_camionero
        WHERE camionero_id = :cid
          AND fin_tren_camionero IS NULL
        LIMIT 1
    ");
    $tcStmt->execute([':cid' => $camioneroId]);
    $tcRow = $tcStmt->fetch(PDO::FETCH_ASSOC);

    if ($tcRow) {
        $trenId     = (int)$tcRow['tren_id'];
        $inicioTren = $tcRow['inicio_tren_camionero'] ?? '';
        $finTren    = $tcRow['fin_tren_camionero'] ?? '';
    }
}

// 9) Obtener tren_nombre si corresponde
$trenNombre = '';
if ($trenId > 0) {
    $tnStmt = $pdo->prepare("
        SELECT tren_nombre
        FROM tren
        WHERE id = :tid
        LIMIT 1
    ");
    $tnStmt->execute([':tid' => $trenId]);
    $tnRow = $tnStmt->fetch(PDO::FETCH_ASSOC);
    if ($tnRow) {
        $trenNombre = $tnRow['tren_nombre'];
    }
}

// 10) Generar un token para la sesión (32 hex)
try {
    $tokenSesion = bin2hex(random_bytes(16));  // p.ej. "7e2c1af8..."

    // Guardarlo en la columna 'token_sesion' del usuario
    $upd = $pdo->prepare("
        UPDATE usuarios
        SET token_sesion = :ts
        WHERE id = :uid
        LIMIT 1
    ");
    $upd->execute([
        ':ts'  => $tokenSesion,
        ':uid' => $usuarioId
    ]);

    // Mapear rol a un identificador numérico para la app
    $rolMap = [
        'administrador' => 1,
        'gestor'        => 2,
        'camionero'     => 3,
        'asociado'      => 4,
        'superadmin'    => 5
    ];
    $rolNombre = $row['rol'] ?? '';
    $rolId = $rolMap[$rolNombre] ?? 0;

    // Respuesta final
    echo json_encode([
        "success" => true,
        "message" => "Credenciales válidas",
        "data" => [
            "usuario_id"   => (int)$usuarioId,
            "rol"          => $rolNombre,
            "rol_id"       => $rolId,
            "camionero_id" => (int)$camioneroId,
            "tren_id"      => (int)$trenId,
            "tren_nombre"  => $trenNombre,
            "email"        => $row['email'] ?? '',
            "nombre"       => $row['nombre_usuario'] ?? '',
            "inicio_tren"  => $inicioTren,
            "fin_tren"     => $finTren,

            // Asegurarnos de llamarlo "token_sesion"
            "token_sesion" => $tokenSesion
        ]
    ]);
    exit;

} catch (Exception $e) {
    // Si algo falla al generar o guardar el token
    echo json_encode([
        "success" => false,
        "message" => "Error generando token_sesion: " . $e->getMessage()
    ]);
    exit;
}
