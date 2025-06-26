<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado

if (isset($_POST['busqueda'])) {
    $busqueda = '%' . $_POST['busqueda'] . '%';
    
    // Buscar usuarios por nombre o email, asegurando que no sean ya contactos del usuario autenticado
    $sql = "SELECT u.id, u.nombre, u.email 
            FROM usuarios u
            LEFT JOIN contactos c ON u.id = c.contacto__usuario_id AND c.usuario_id = ?
            WHERE (u.nombre LIKE ? OR u.email LIKE ?) AND u.id != ? AND c.contacto__usuario_id IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $usuario_id, $busqueda, $busqueda, $usuario_id);
    $stmt->execute();
    $resultado_contactos = $stmt->get_result();

    // Si la solicitud es AJAX (verificamos el encabezado HTTP_X_REQUESTED_WITH)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Formato para AJAX
        if ($resultado_contactos->num_rows > 0) {
            while ($contacto = $resultado_contactos->fetch_assoc()) {
                echo "<div>" . htmlspecialchars($contacto['nombre']) . " (" . htmlspecialchars($contacto['email']) . ") ";
                echo "<button type='button' onclick='agregarSeleccionado(" . $contacto['id'] . ", \"" . $contacto['nombre'] . "\", \"contacto\")'>Seleccionar</button></div>";
            }
        } else {
            echo "<p>No se encontraron contactos.</p>";
        }
    } else {
        // Formato para carga de página normal
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Buscar Contactos</title>
        </head>
        <body>

        <form method="POST" action="buscar_contactos.php">
            <input type="text" name="busqueda" placeholder="Buscar por nombre o email">
            <button type="submit">Buscar</button>
        </form>

        <?php
        if ($resultado_contactos->num_rows > 0) {
            echo "<ul>";
            while ($contacto = $resultado_contactos->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($contacto['nombre']) . " - " . htmlspecialchars($contacto['email']);
                echo " <a href='editar_contacto.php?id=" . $contacto['id'] . "'>Editar</a>";
                echo " <a href='eliminar_contacto.php?id=" . $contacto['id'] . "'>Eliminar</a>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No se encontraron contactos.</p>";
        }
        ?>
        </body>
        </html>
        <?php
    }
}
?>
