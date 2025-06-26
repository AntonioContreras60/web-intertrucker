<?php
session_start();
include 'conexion.php';

// Verificar si se ha recibido el grupo_id
if (!isset($_POST['grupo_id'])) {
    echo json_encode(['error' => 'No se recibió el ID del grupo']);
    exit;
}

$grupo_id = $_POST['grupo_id'];

// Obtener los contactos que pertenecen al grupo seleccionado
$sql = "SELECT u.id, u.nombre_usuario AS nombre 
        FROM usuarios u
        JOIN grupo_contactos gc ON u.id = gc.contacto_id
        WHERE gc.grupo_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $grupo_id);
$stmt->execute();
$resultado = $stmt->get_result();

$contactos = [];
while ($contacto = $resultado->fetch_assoc()) {
    $contactos[] = $contacto;
}

// Enviar los datos en formato JSON
echo json_encode(['contactos' => $contactos]);
?>
