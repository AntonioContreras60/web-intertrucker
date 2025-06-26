<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php'; // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $usuario_id = $_SESSION['usuario_id']; // Asegúrate de que el usuario esté autenticado
        $nivel_1 = $_POST['nivel_1'];
        $nivel_2 = $_POST['nivel_2'];
        $nivel_3 = $_POST['nivel_3'];
        $matricula = $_POST['matricula'];
        $marca = $_POST['marca'];
        $modelo = $_POST['modelo'];
        $ano_fabricacion = isset($_POST['ano_fabricacion']) ? $_POST['ano_fabricacion'] : NULL;
        $capacidad = isset($_POST['capacidad']) ? $_POST['capacidad'] : NULL;
        $capacidad_arrastre = isset($_POST['capacidad_arrastre']) ? $_POST['capacidad_arrastre'] : NULL;
        $largo = isset($_POST['largo']) ? $_POST['largo'] : NULL;
        $ancho = isset($_POST['ancho']) ? $_POST['ancho'] : NULL;
        $alto = isset($_POST['alto']) ? $_POST['alto'] : NULL;
        $volumen = isset($_POST['volumen']) ? $_POST['volumen'] : NULL;
        $numero_ejes = isset($_POST['numero_ejes']) ? $_POST['numero_ejes'] : NULL;
        $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : NULL;

        // Características adicionales
        $adr = isset($_POST['adr']) ? 1 : 0;
        $doble_conductor = isset($_POST['doble_conductor']) ? 1 : 0;
        $plataforma_elevadora = isset($_POST['plataforma_elevadora']) ? 1 : 0;
        $temperatura_controlada = isset($_POST['temperatura_controlada']) ? 1 : 0;
        $telefono = isset($_POST['telefono']) ? $_POST['telefono'] : '';

        // Campo 'activo'
        $activo = 1; // Valor por defecto para que el vehículo esté activo

        // Consulta SQL
        $sql = "INSERT INTO vehiculos 
            (usuario_id, nivel_1, nivel_2, nivel_3, matricula, marca, modelo, ano_fabricacion, capacidad, capacidad_arrastre, largo, ancho, alto, volumen, adr, doble_conductor, plataforma_elevadora, temperatura_controlada, numero_ejes, telefono, observaciones, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("isssssdddddddiiiiisssi", 
                $usuario_id, $nivel_1, $nivel_2, $nivel_3, $matricula, $marca, $modelo, 
                $ano_fabricacion, $capacidad, $capacidad_arrastre, $largo, $ancho, $alto, $volumen, 
                $adr, $doble_conductor, $plataforma_elevadora, $temperatura_controlada, 
                $numero_ejes, $telefono, $observaciones, $activo
            );

            if ($stmt->execute()) {
                echo "Vehículo añadido exitosamente.";
            } else {
                echo "Error al agregar el vehículo: " . $stmt->error;
            }
        } else {
            echo "Error en la preparación de la consulta: " . $conn->error;
        }
    } catch (Exception $e) {
        echo "Excepción: " . $e->getMessage();
    }
} else {
    echo "No se recibió el método POST.";
}

// Botón Volver siempre presente
echo "<div style='text-align: center; margin-top: 20px;'>
          <a href='my_trucks.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Volver a la Gestión de Vehículos</a>
      </div>";
?>
