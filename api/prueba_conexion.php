<?php
require_once __DIR__ . "/api_auth.php";
require_api_login();
header("Access-Control-Allow-Origin: https://www.intertrucker.net");
include_once __DIR__ . '/../conexion.php';
if ($pdo) {
    echo "Conexión exitosa a la base de datos.";
} else {
    echo "Error de conexión";
}
?>
