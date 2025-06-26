<?php
include 'conexion.php'; 

$termino = $_POST['termino'];

$sql = "SELECT codigo, nombre_es AS nombre 
        FROM monedas 
        WHERE nombre_es LIKE '%$termino%' 
           OR nombre_en LIKE '%$termino%' 
           OR nombre_zh LIKE '%$termino%'"; 

$resultado = $conn->query($sql);

if ($resultado === false) {
  die("Error en la consulta: " . $conn->error);
}

$monedas = array();
while ($row = $resultado->fetch_assoc()) {
  $monedas[] = $row;
}

echo json_encode($monedas);
?>