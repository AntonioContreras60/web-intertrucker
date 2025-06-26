<?php
// Iniciar sesión y conexión a la base de datos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php';

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
echo "Conexión establecida con éxito.<br>";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "Método POST recibido.<br>";

        // Recoger los datos del formulario
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
        echo "Usuario ID: " . $usuario_id . "<br>";

        $nivel_1 = isset($_POST['nivel_1']) ? $_POST['nivel_1'] : '';
        $nivel_2 = isset($_POST['nivel_2']) ? $_POST['nivel_2'] : '';
        $nivel_3 = isset($_POST['nivel_3']) ? $_POST['nivel_3'] : '';
        $matricula = isset($_POST['matricula']) ? $_POST['matricula'] : '';
        $marca = isset($_POST['marca']) ? $_POST['marca'] : '';
        $modelo = isset($_POST['modelo']) ? $_POST['modelo'] : '';
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

        // Formas de carga
        $forma_carga_lateral = isset($_POST['forma_carga_lateral']) ? 1 : 0;
        $forma_carga_detras = isset($_POST['forma_carga_detras']) ? 1 : 0;
        $forma_carga_arriba = isset($_POST['forma_carga_arriba']) ? 1 : 0;

        // Campo 'activo'
        $activo = 1; // Valor por defecto para que el vehículo esté activo

        // Mostrar los parámetros antes de preparar la consulta
        echo "<pre>Parámetros Antes del bind_param:\n";
        print_r([
            'usuario_id' => $usuario_id, 'nivel_1' => $nivel_1, 'nivel_2' => $nivel_2, 'nivel_3' => $nivel_3, 
            'matricula' => $matricula, 'marca' => $marca, 'modelo' => $modelo, 
            'ano_fabricacion' => $ano_fabricacion, 'capacidad' => $capacidad, 
            'capacidad_arrastre' => $capacidad_arrastre, 'largo' => $largo, 
            'ancho' => $ancho, 'alto' => $alto, 'volumen' => $volumen, 
            'adr' => $adr, 'doble_conductor' => $doble_conductor, 
            'plataforma_elevadora' => $plataforma_elevadora, 'temperatura_controlada' => $temperatura_controlada, 
            'forma_carga_lateral' => $forma_carga_lateral, 'forma_carga_detras' => $forma_carga_detras, 
            'forma_carga_arriba' => $forma_carga_arriba, 'numero_ejes' => $numero_ejes, 
            'telefono' => $telefono, 'observaciones' => $observaciones, 'activo' => $activo
        ]);
        echo "</pre>";

        // Ajustar la consulta SQL
        $sql = "INSERT INTO vehiculos 
                (usuario_id, nivel_1, nivel_2, nivel_3, matricula, marca, modelo, ano_fabricacion, capacidad, capacidad_arrastre, largo, ancho, alto, volumen, adr, doble_conductor, plataforma_elevadora, temperatura_controlada, forma_carga_lateral, forma_carga_detras, forma_carga_arriba, numero_ejes, telefono, observaciones, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            echo "Consulta preparada correctamente.<br>";
            
            // Vincular parámetros a la consulta
            $stmt->bind_param("isssssdddssdddiiiiiiiiissi", 
                $usuario_id, $nivel_1, $nivel_2, $nivel_3, $matricula, $marca, $modelo, $ano_fabricacion, 
                $capacidad, $capacidad_arrastre, $largo, $ancho, $alto, $volumen, 
                $adr, $doble_conductor, $plataforma_elevadora, $temperatura_controlada, 
                $forma_carga_lateral, $forma_carga_detras, $forma_carga_arriba, $numero_ejes, 
                $telefono, $observaciones, $activo);

            if ($stmt->execute()) {
                echo "Vehículo añadido exitosamente.";
            } else {
                echo "Error al agregar el vehículo: " . $stmt->error;
            }
        } else {
            echo "Error en la preparación de la consulta: " . $conn->error;
        }
    } else {
        echo "No se recibió el método POST.<br>";
    }
} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage();
}
?>
