<?php
$servername = getenv('DB_HOST') ?: 'db5016197746.hosting-data.io';
$username   = getenv('DB_USER') ?: 'dbu4085097';
$password   = getenv('DB_PASS') ?: '123intertruckerya';
$dbname     = getenv('DB_NAME') ?: 'dbs13181300';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Error de conexión PDO: ' . $e->getMessage());
}
?>
