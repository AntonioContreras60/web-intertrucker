<?php
require_once __DIR__ . '/../auth.php';

function require_api_login() {
    ensure_session_started();
    if (!empty($_SESSION['usuario_id'])) {
        return;
    }

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $token = $matches[1];

        $conn = new mysqli(
            getenv('DB_HOST'),
            getenv('DB_USER'),
            getenv('DB_PASS'),
            getenv('DB_NAME')
        );
        if (!$conn->connect_error) {
            $stmt = $conn->prepare('SELECT id, rol, admin_id, nombre_usuario FROM usuarios WHERE token_sesion = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $token);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $_SESSION['usuario_id'] = (int)$row['id'];
                    $_SESSION['rol'] = $row['rol'] ?? '';
                    $_SESSION['admin_id'] = (int)($row['admin_id'] ?? 0);
                    $_SESSION['nombre_usuario'] = $row['nombre_usuario'] ?? '';
                    $stmt->close();
                    $conn->close();
                    return;
                }
                $stmt->close();
            }
            $conn->close();
        }
    }

    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
