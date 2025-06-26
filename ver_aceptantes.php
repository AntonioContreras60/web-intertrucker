<?php
// Incluir la conexión a la base de datos
include 'db_connection.php';

// Obtener el ID del porte
$porte_id = $_GET['porte_id'];

// Obtener la lista de aceptantes
$aceptantes = mysqli_query($conn, "SELECT u.id, u.nombre FROM usuarios u 
    JOIN ofertas o ON u.id = o.destinatario_id 
    WHERE o.porte_id = $porte_id AND o.estado = 'aceptado'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Asignar el porte al aceptante seleccionado
    $aceptante_id = $_POST['aceptante_id'];
    $sql = "UPDATE portes SET asignado_a = $aceptante_id, estado = 'asignado' WHERE id = $porte_id";
    mysqli_query($conn, $sql);

    // Redirigir a la lista de portes asignados
    header('Location: portes_asignados.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar Aceptante</title>
</head>
<body>
    <h1>Seleccionar Aceptante para el Porte</h1>
    
    <form action="ver_aceptantes.php?porte_id=<?php echo $porte_id; ?>" method="POST">
        <fieldset>
            <legend>Usuarios que Han Aceptado la Oferta</legend>

            <?php
            // Listar los usuarios aceptantes
            while ($row = mysqli_fetch_assoc($aceptantes)) {
                echo "<input type='radio' name='aceptante_id' value='{$row['id']}'> {$row['nombre']}<br>";
            }
            ?>
        </fieldset>

        <button type="submit">Asignar Porte</button>
    </form>
</body>
</html>