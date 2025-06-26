<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado

if (isset($_POST['busqueda'])) {
    $busqueda = '%' . $_POST['busqueda'] . '%';
    
    // Consulta para buscar grupos
    $sql = "SELECT id, nombre FROM grupos WHERE nombre LIKE ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $busqueda, $usuario_id);
    $stmt->execute();
    $resultado_grupos = $stmt->get_result();

    // Si la solicitud es AJAX (verificamos el encabezado HTTP_X_REQUESTED_WITH)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Formato para AJAX
        if ($resultado_grupos->num_rows > 0) {
            while ($grupo = $resultado_grupos->fetch_assoc()) {
                echo "<div>" . htmlspecialchars($grupo['nombre']) . " 
                <button type='button' onclick='seleccionarGrupo(" . $grupo['id'] . ")'>Seleccionar Grupo</button></div>";
            }
        } else {
            echo "<p>No se encontraron grupos.</p>";
        }
    } else {
        // Formato para carga de página normal
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Buscar Grupos</title>
        </head>
        <body>

        <form method="POST" action="buscar_grupos.php">
            <input type="text" name="busqueda" placeholder="Buscar grupos por nombre">
            <button type="submit">Buscar</button>
        </form>

        <?php
        if ($resultado_grupos->num_rows > 0) {
            echo "<ul>";
            while ($grupo = $resultado_grupos->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($grupo['nombre']);
                echo " <a href='editar_grupo.php?id=" . $grupo['id'] . "'>Editar</a>";
                echo " <a href='eliminar_grupo.php?id=" . $grupo['id'] . "'>Eliminar</a>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No se encontraron grupos.</p>";
        }
        ?>
        </body>
        </html>
        <?php
    }
}
?>
