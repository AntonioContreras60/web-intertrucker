<?php
/******************************************************
 * autologin_mecanismo.php
 * ----------------------------------------------------
 * 1) Recibe ?tk=XYZ (token)
 * 2) Valida en autologin_tokens
 * 3) Comprueba que no esté usado ni caducado
 * 4) Marca usado=1
 * 5) Crea la sesión (usuario_id, admin_id, nombre_usuario, etc.)
 * 6) Redirige a la página Manager
 ******************************************************/

// 1) Recibir el token
$token = isset($_GET['tk']) ? trim($_GET['tk']) : '';
if (empty($token)) {
    echo "Falta token (tk) en la URL.";
    exit;
}

// 2) Conectar a la base de datos (ajusta credenciales a tu hosting)
$dbHost = "db5016197746.hosting-data.io";
$dbUser = "dbu4085097";
$dbPass = "123intertruckerya";
$dbName = "dbs13181300";

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    echo "Error de conexión a la BD: " . $e->getMessage();
    exit;
}

// 3) Buscar el token en autologin_tokens
try {
    $sql = "SELECT id, usuario_id, fecha_creado, fecha_expira, usado
            FROM autologin_tokens
            WHERE token=? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo "Token inválido o no encontrado.";
        exit;
    }

    $tokenId     = (int)$row['id'];
    $usuarioId   = (int)$row['usuario_id'];
    $fechaExpira = $row['fecha_expira'];
    $usado       = (int)$row['usado'];
    $fechaAhora  = date('Y-m-d H:i:s');

    if ($usado === 1) {
        echo "Este token ya ha sido utilizado. (Autologin expirado)";
        exit;
    }

    if ($fechaAhora > $fechaExpira) {
        echo "El token ha caducado. (Expiró en: $fechaExpira)";
        exit;
    }

    // 4) Marcar el token como usado=1
    $sqlUpdate = "UPDATE autologin_tokens SET usado=1 WHERE id=? LIMIT 1";
    $stmt2 = $pdo->prepare($sqlUpdate);
    $stmt2->execute([$tokenId]);

    // 5) Crear la sesión y asignar usuario_id
    session_start();
    $_SESSION['usuario_id'] = $usuarioId;

    // 5a) Opcional: obtener admin_id, nombre_usuario, etc.
    try {
        $sqlUsr = "SELECT admin_id, nombre_usuario 
                   FROM usuarios 
                   WHERE id=? 
                   LIMIT 1";
        $stmtUsr = $pdo->prepare($sqlUsr);
        $stmtUsr->execute([$usuarioId]);
        $usrData = $stmtUsr->fetch(PDO::FETCH_ASSOC);

        if ($usrData) {
            // Ajusta estos campos según existan en la tabla 'usuarios'
            $_SESSION['admin_id']        = (int)$usrData['admin_id'];
            $_SESSION['nombre_usuario']  = $usrData['nombre_usuario'] ?? '';
        }
    } catch (Exception $exUsr) {
        echo "Error cargando admin_id / nombre_usuario => " . $exUsr->getMessage();
        exit;
    }

    // 6) Redirigir a la página Manager
    // Ajusta la ruta final (a la que tu sistema requiera)
    header("Location: https://www.intertrucker.net/portes_nuevos_recibidos.php");
    exit;

} catch (Exception $e) {
    echo "Error consultando/iniciando autologin: " . $e->getMessage();
    exit;
}
