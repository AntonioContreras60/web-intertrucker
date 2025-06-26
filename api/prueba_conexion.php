<?php
$host = 'localhost'; // Cambia según corresponda
$dbname = 'nombre_de_la_base_de_datos';
$username = 'usuario_de_la_base_de_datos';
$password = 'contraseña_de_la_base_de_datos';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    echo "Conexión exitosa a la base de datos.";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
