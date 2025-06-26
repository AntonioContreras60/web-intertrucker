<?php
session_start();
include 'conexion.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_creador_id = $_SESSION['usuario_id']; // Obtener el ID del usuario autenticado

    // Recibir y sanitizar los datos del formulario
    $mercancia_descripcion = $conn->real_escape_string($_POST['descripcion_mercancia']);
    $mercancia_conservacion = $conn->real_escape_string($_POST['conservacion_mercancia']);
    $adr = isset($_POST['adr_mercancia']) ? 1 : 0;
    $cantidad = isset($_POST['cantidad']) ? $conn->real_escape_string($_POST['cantidad']) : null;
    $peso_total = isset($_POST['peso_total']) ? $conn->real_escape_string($_POST['peso_total']) : null;
    $volumen_total = isset($_POST['volumen_total']) ? $conn->real_escape_string($_POST['volumen_total']) : null;
    $tipo_carga = isset($_POST['tipo_carga']) ? $conn->real_escape_string($_POST['tipo_carga']) : null;
    $observaciones = isset($_POST['observaciones']) ? $conn->real_escape_string($_POST['observaciones']) : null;

    $recogida_direccion = isset($_POST['recogida_direccion']) ? $conn->real_escape_string($_POST['recogida_direccion']) : null;
    $recogida_fecha = isset($_POST['recogida_fecha']) ? $conn->real_escape_string($_POST['recogida_fecha']) : null;
    $recogida_hora_inicio = isset($_POST['recogida_hora_inicio']) ? $conn->real_escape_string($_POST['recogida_hora_inicio']) : null;
    $recogida_hora_fin = isset($_POST['recogida_hora_fin']) ? $conn->real_escape_string($_POST['recogida_hora_fin']) : null;
    $observaciones_recogida = isset($_POST['observaciones_recogida']) ? $conn->real_escape_string($_POST['observaciones_recogida']) : null;

    $entrega_direccion = isset($_POST['entrega_direccion']) ? $conn->real_escape_string($_POST['entrega_direccion']) : null;
    $entrega_fecha = isset($_POST['entrega_fecha']) ? $conn->real_escape_string($_POST['entrega_fecha']) : null;
    $entrega_hora_inicio = isset($_POST['entrega_hora_inicio']) ? $conn->real_escape_string($_POST['entrega_hora_inicio']) : null;
    $entrega_hora_fin = isset($_POST['entrega_hora_fin']) ? $conn->real_escape_string($_POST['entrega_hora_fin']) : null;
    $observaciones_entrega = isset($_POST['observaciones_entrega']) ? $conn->real_escape_string($_POST['observaciones_entrega']) : null;

    $no_transbordos = isset($_POST['no_transbordos']) ? 1 : 0;
    $no_delegacion_transporte = isset($_POST['no_delegacion_transporte']) ? 1 : 0;
    $no_se_puede_remontar = isset($_POST['no_se_puede_remontar']) ? 1 : 0;

    $dimensiones_maximas = isset($_POST['dimensiones_maximas']) ? $conn->real_escape_string($_POST['dimensiones_maximas']) : null;

    // Consulta para insertar los datos en la base de datos incluyendo el ID del usuario creador
    $sql = "INSERT INTO portes (
                mercancia_descripcion, mercancia_conservacion, adr, cantidad, peso_total, volumen_total,
                tipo_carga, observaciones, localizacion_recogida, fecha_recogida, recogida_hora_inicio, recogida_hora_fin, observaciones_recogida,
                localizacion_entrega, fecha_entrega, entrega_hora_inicio, entrega_hora_fin, observaciones_entrega, no_transbordos, 
                no_delegacion_transporte, se_puede_remontar, dimensiones_maximas, usuario_creador_id
            ) VALUES (
                '$mercancia_descripcion', '$mercancia_conservacion', '$adr', '$cantidad', '$peso_total', '$volumen_total',
                '$tipo_carga', '$observaciones', '$recogida_direccion', '$recogida_fecha', '$recogida_hora_inicio', '$recogida_hora_fin', '$observaciones_recogida',
                '$entrega_direccion', '$entrega_fecha', '$entrega_hora_inicio', '$entrega_hora_fin', '$observaciones_entrega', '$no_transbordos', 
                '$no_delegacion_transporte', '$no_se_puede_remontar', '$dimensiones_maximas', '$usuario_creador_id'
            )";

    // Ejecutar la consulta y manejar el resultado
    if ($conn->query($sql) === TRUE) {
        $porte_id = $conn->insert_id; // Obtener el ID del nuevo porte

        // Código para insertar en la tabla historial_asignaciones cuando se crea el porte
        $sql_historial = "INSERT INTO historial_asignaciones (porte_id, usuario_creador_id, estado, fecha_oferta)
                          VALUES (?, ?, 'Creado', NOW())";
        $stmt = $conn->prepare($sql_historial);
        $stmt->bind_param("ii", $porte_id, $usuario_creador_id);
        $stmt->execute();

        // Verificar qué botón fue presionado
        if (isset($_POST['guardar_porte'])) {
            // Si se presionó el botón "Guardar" (estado creado)
            $mensaje = "Porte creado y guardado como 'Creado'.";

        } elseif (isset($_POST['ofrecer_porte'])) {
            // Si se presionó el botón "Ofrecer Directamente"
            header("Location: hacer_oferta.php?porte_id=$porte_id");
            exit();

        } elseif (isset($_POST['hacer_porte'])) {
            // Si se presionó el botón "Hacer" (para un camionero autónomo)
            header("Location: recogidas_entregas.php?porte_id=$porte_id");
            exit();
        }
    } else {
        $mensaje = "Error: " . $conn->error;
    }

    // Cerrar la conexión
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Porte</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div>
        <?php if (isset($mensaje)) echo $mensaje; ?>
        <br><a href="crear_porte.php">Volver</a>
    </div>
</body>
</html>
