<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Conexión a la base de datos
$servername = "localhost"; // Cambiar si no es localhost
$username = "TU_USUARIO";  // Usuario de la base de datos
$password = "TU_CONTRASEÑA"; // Contraseña de la base de datos
$dbname = "intertrucker_db"; // Nombre de la base de datos

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error al conectar con la base de datos."]);
    exit;
}

// Leer los datos enviados
$input = json_decode(file_get_contents("php://input"), true);

$email = $input['email'] ?? '';
$contrasena = $input['contrasena'] ?? '';

// Consulta para verificar el usuario
$sql = "SELECT id, email, rol FROM usuarios WHERE email = ? AND contrasena = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $contrasena);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
    echo json_encode(["success" => true, "usuario" => $usuario]);
} else {
    echo json_encode(["success" => false, "message" => "Credenciales incorrectas."]);
}

// Cerrar conexión
$stmt->close();
$conn->close();
?>