<?php
session_start();
include 'conexion.php';

$usuario_id = $_SESSION['usuario_id']; // Usuario que está haciendo la oferta

if (isset($_POST['nombre_usuario']) && isset($_POST['porte_id'])) {
    $busqueda = "%" . $_POST['nombre_usuario'] . "%";
    $porte_id = $_POST['porte_id'];

    // Consulta que selecciona únicamente los contactos del usuario y obtiene el nombre del usuario desde la tabla `usuarios`
    $sql = "
        SELECT u.id AS contacto_usuario_id, u.nombre_usuario 
        FROM contactos c
        JOIN usuarios u ON c.contacto_usuario_id = u.id
        WHERE c.usuario_id = ? 
          AND u.nombre_usuario LIKE ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div>
                    <form method='POST' action='enviar_oferta.php' style='display: inline;'>
                        <strong>" . htmlspecialchars($row['nombre_usuario']) . "</strong>
                        <input type='hidden' name='usuario_destinatario_id' value='" . $row['contacto_usuario_id'] . "'>
                        <input type='hidden' name='porte_id' value='" . htmlspecialchars($porte_id) . "'>
                        <input type='number' name='precio_oferta' placeholder='Precio EUR' required>
                        <button type='submit'>Ofrecer</button>
                    </form>
                  </div>";
        }
    } else {
        echo "<p>No se encontraron contactos con ese nombre.</p>";
    }

    $stmt->close();
}
?>
