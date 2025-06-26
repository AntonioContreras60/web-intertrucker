<?php
// Verificar si el archivo se está accediendo directamente
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    die("Acceso directo no permitido.");
}

// Verificar si ya se ha iniciado una sesión antes de iniciar una nueva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
$servername = "db5016197746.hosting-data.io"; 
$username = "dbu4085097"; 
$password = "123intertruckerya"; 
$dbname = "dbs13181300";

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Función para sanitizar entrada
if (!function_exists('sanitizar')) {
    function sanitizar($conn, $dato) {
        return mysqli_real_escape_string($conn, trim($dato));
    }
}

// Realizar una consulta simple para comprobar la conexión
$sql_test = "SELECT 1";
$result = $conn->query($sql_test);

if ($result) {
    // Conexión exitosa
    // echo "Conexión exitosa a la base de datos."; // Descomenta si deseas ver el mensaje de éxito
} else {
    // Si la consulta de prueba falla, mostrar el error
    die("Error en la consulta de prueba: " . $conn->error);
}

?>
