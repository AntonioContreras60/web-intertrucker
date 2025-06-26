<?php
function verificarOcrearTren($vehiculos_combinados, $conn) {
    // Paso 1: Obtener la marca y matrícula de cada vehículo
    $vehiculo_info = [];
    foreach ($vehiculos_combinados as $vehiculo_id) {
        $sql_vehiculo = "SELECT marca, matricula FROM vehiculos WHERE id = ?";
        $stmt_vehiculo = $conn->prepare($sql_vehiculo);
        $stmt_vehiculo->bind_param("i", $vehiculo_id);
        $stmt_vehiculo->execute();
        $result_vehiculo = $stmt_vehiculo->get_result();
        
        if ($result_vehiculo->num_rows > 0) {
            $vehiculo = $result_vehiculo->fetch_assoc();
            // Agregar la marca y matrícula al array
            $vehiculo_info[] = $vehiculo['marca'] . " " . $vehiculo['matricula'];
        }
    }
    
    // Paso 2: Concatenar todas las marcas y matrículas en un nombre de tren
    $tren_nombre = implode(' - ', $vehiculo_info);
    
    // Paso 3: Verificar si un tren con esta combinación ya existe
    $sql_verificar = "SELECT id FROM tren WHERE tren_nombre = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("s", $tren_nombre);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();

    if ($result_verificar->num_rows > 0) {
        // Si el tren ya existe, devolver el ID
        $tren = $result_verificar->fetch_assoc();
        return $tren['id'];
    } else {
        // Si el tren no existe, crear uno nuevo
        $sql_crear = "INSERT INTO tren (tren_nombre) VALUES (?)";
        $stmt_crear = $conn->prepare($sql_crear);
        $stmt_crear->bind_param("s", $tren_nombre);

        if ($stmt_crear->execute()) {
            return $stmt_crear->insert_id; // Devolver el ID del nuevo tren
        } else {
            return false; // Error al crear el tren
        }
    }
}

function guardarTrenEnPorte($tren_id, $porte_id, $conn) {
    $sql_insert_tren_vehiculos = "INSERT INTO tren_vehiculos (tren_id, porte_id, inicio_vehiculo_porte) VALUES (?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert_tren_vehiculos);
    $stmt_insert->bind_param("ii", $tren_id, $porte_id);
    
    if ($stmt_insert->execute()) {
        echo "Tren asignado al porte correctamente.";
    } else {
        echo "Error al asignar tren al porte: " . $stmt_insert->error;
    }
}
?>