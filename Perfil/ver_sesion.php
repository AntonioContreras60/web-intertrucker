<?php
// Usar el mismo path para guardar sesiones en TODA la web
ini_set('session.save_path', '/tmp');

session_start();
header('Content-Type: text/plain; charset=utf-8');

echo "ID de sesión actual: " . session_id() . "\n\n";

echo "Contenido de \$_SESSION:\n";
print_r($_SESSION);
