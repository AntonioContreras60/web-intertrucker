<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
/****************************************************
 * autologin.php
 * ---------------------------------
 * 1) Recibe usuario_id (por POST)
 * 2) Valida que existe en la BD
 * 3) Genera un token temporal y lo guarda en autologin_tokens
 * 4) Devuelve JSON con la URL mágica para autologin
 ****************************************************/

header('Content-Type: application/json; charset=UTF-8');

// --- 1) Recibir el usuario_id ---
$usuarioId = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
if ($usuarioId < 1) {
    echo json_encode([
        "success" => false,
        "message" => "usuario_id faltante o inválido"
    ]);
    exit;
}

// --- 2) Conectar a la base de datos ---
include_once __DIR__ . '/../conexion.php';
if (!$pdo) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión a la BD"
    ]);
    exit;
}

// --- 3) Validar que el usuario existe en la tabla usuarios ---
try {
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE id=? LIMIT 1");
    $stmtCheck->execute([$usuarioId]);
    $userRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
        echo json_encode([
            "success" => false,
            "message" => "Usuario no encontrado en la BD con id=$usuarioId"
        ]);
        exit;
    }
} catch (Exception $ex) {
    echo json_encode([
        "success" => false,
        "message" => "Error consultando usuario: " . $ex->getMessage()
    ]);
    exit;
}

// --- 4) Generar el token y calcular fechas ---
try {
    $token = bin2hex(random_bytes(16)); // 32 caracteres hex
    $fechaCreado = date('Y-m-d H:i:s');
    // Ajusta la validez a tu gusto (aquí +1 minute)
    $fechaExpira = date('Y-m-d H:i:s', strtotime('+1 minute'));
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error generando token: " . $e->getMessage()
    ]);
    exit;
}

// --- 5) Insertar registro en autologin_tokens ---
try {
    $sqlInsert = "INSERT INTO autologin_tokens (token, usuario_id, fecha_creado, fecha_expira, usado)
                  VALUES (:token, :usuario_id, :creado, :expira, 0)";
    $stmtIns = $pdo->prepare($sqlInsert);
    $stmtIns->execute([
        ':token'      => $token,
        ':usuario_id' => $usuarioId,
        ':creado'     => $fechaCreado,
        ':expira'     => $fechaExpira
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error insertando token en la BD: " . $e->getMessage()
    ]);
    exit;
}

// --- 6) Construir la URL mágica ---
$autologinUrl = "https://www.intertrucker.net/api/autologin_mecanismo.php?tk=" . urlencode($token);

// --- 7) Responder en JSON ---
echo json_encode([
    "success" => true,
    "url" => $autologinUrl
]);
exit;
