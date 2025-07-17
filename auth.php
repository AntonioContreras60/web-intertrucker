<?php
// auth.php - Funciones de autenticacion reutilizables

// Inicia la sesion si no esta iniciada
function ensure_session_started() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Redirige al login
function redirect_to_login() {
    header('Location: /Perfil/inicio_sesion.php');
    exit();
}

// Verifica que el usuario haya iniciado sesion
function require_login() {
    ensure_session_started();
    if (empty($_SESSION['usuario_id'])) {
        redirect_to_login();
    }
}

// Verifica que el usuario tenga alguno de los roles permitidos
function require_role($allowedRoles) {
    ensure_session_started();
    if (empty($_SESSION['usuario_id'])) {
        redirect_to_login();
    }
    $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    if (!in_array($_SESSION['rol'] ?? '', $allowed, true)) {
        http_response_code(403);
        echo 'Acceso denegado';
        exit();
    }
}

