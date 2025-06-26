<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

$grupo_id = $_GET['grupo_id'] ?? null; // Obtener el ID del grupo desde la URL
if (!$grupo_id) {
    die("ID del grupo no especificado.");
}

// Obtener el nombre del grupo
$sql_grupo = "SELECT nombre FROM grupos WHERE id = ?";
$stmt_grupo = $conn->prepare($sql_grupo);
$stmt_grupo->bind_param("i", $grupo_id);
$stmt_grupo->execute();
$resultado_grupo = $stmt_grupo->get_result();
$grupo = $resultado_grupo->fetch_assoc();

if (!$grupo) {
    die("Grupo no encontrado.");
}

// Modificar nombre del grupo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nuevo_nombre'])) {
    $nuevo_nombre = $_POST['nuevo_nombre'];

    // Verificar que el nuevo nombre no esté en uso
    $sql_verificar = "SELECT id FROM grupos WHERE nombre = ? AND id != ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("si", $nuevo_nombre, $grupo_id);
    $stmt_verificar->execute();
    $stmt_verificar->store_result();

    if ($stmt_verificar->num_rows > 0) {
        echo "<p>Error: El nombre del grupo ya está en uso. Elija otro.</p>";
    } else {
        // Actualizar nombre del grupo
        $sql_actualizar = "UPDATE grupos SET nombre = ? WHERE id = ?";
        $stmt_actualizar = $conn->prepare($sql_actualizar);
        $stmt_actualizar->bind_param("si", $nuevo_nombre, $grupo_id);
        if ($stmt_actualizar->execute()) {
            echo "<p>Nombre del grupo actualizado correctamente.</p>";
            $grupo['nombre'] = $nuevo_nombre; // Actualizar el nombre en la página
        } else {
            echo "<p>Error al actualizar el nombre del grupo.</p>";
        }
    }
}

// Eliminar grupo
if (isset($_GET['eliminar_grupo']) && $_GET['eliminar_grupo'] == 'true') {
    // Verificar que el grupo exista
    $sql_verificar_grupo = "SELECT id FROM grupos WHERE id = ?";
    $stmt_verificar_grupo = $conn->prepare($sql_verificar_grupo);
    $stmt_verificar_grupo->bind_param("i", $grupo_id);
    $stmt_verificar_grupo->execute();
    $stmt_verificar_grupo->store_result();

    if ($stmt_verificar_grupo->num_rows == 0) {
        die("Grupo no encontrado.");
    }

    // Eliminar contactos o entidades del grupo
    $sql_eliminar_contactos_entidades = "DELETE FROM grupo_contactos WHERE grupo_id = ?";
    $stmt_eliminar_contactos_entidades = $conn->prepare($sql_eliminar_contactos_entidades);
    $stmt_eliminar_contactos_entidades->bind_param("i", $grupo_id);
    $stmt_eliminar_contactos_entidades->execute();

    // Eliminar el grupo
    $sql_eliminar_grupo = "DELETE FROM grupos WHERE id = ?";
    $stmt_eliminar_grupo = $conn->prepare($sql_eliminar_grupo);
    $stmt_eliminar_grupo->bind_param("i", $grupo_id);
    if ($stmt_eliminar_grupo->execute()) {
        echo "<p>Grupo eliminado correctamente.</p>";
        header("Location: my_network.php");
        exit();
    } else {
        echo "<p>Error al eliminar el grupo.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactos en el Grupo: <?= htmlspecialchars($grupo['nombre']) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Contactos en el Grupo: <?= htmlspecialchars($grupo['nombre']) ?></h1>

<!-- Listado de contactos agregados al grupo -->
<h3>Contactos en el Grupo</h3>
<ul>
<?php
// Obtener contactos del grupo
$sql_contactos = "SELECT u.id, u.nombre_usuario, u.email FROM grupo_contactos gc 
                  JOIN usuarios u ON gc.contacto_id = u.id 
                  WHERE gc.grupo_id = ?";
$stmt_contactos = $conn->prepare($sql_contactos);
$stmt_contactos->bind_param("i", $grupo_id);
$stmt_contactos->execute();
$resultado_contactos = $stmt_contactos->get_result();

if ($resultado_contactos->num_rows > 0) {
    while ($contacto = $resultado_contactos->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($contacto['nombre_usuario']) . " (" . htmlspecialchars($contacto['email']) . ")";
        echo " <a href='eliminar_contacto_grupo.php?grupo_id=$grupo_id&contacto_id=" . $contacto['id'] . "'>Desagregar</a>";
        echo " <a href='ver_contacto.php?contacto_id=" . $contacto['id'] . "'>Ver</a></li>";
    }
} else {
    echo "<p>No hay contactos en este grupo aún.</p>";
}

// Obtener entidades del grupo
$sql_entidades = "SELECT e.id, e.nombre, e.email FROM grupo_contactos gc 
                  JOIN entidades e ON gc.entidad_id = e.id 
                  WHERE gc.grupo_id = ?";
$stmt_entidades = $conn->prepare($sql_entidades);
$stmt_entidades->bind_param("i", $grupo_id);
$stmt_entidades->execute();
$resultado_entidades = $stmt_entidades->get_result();

if ($resultado_entidades->num_rows > 0) {
    echo "<h4>Entidades en el Grupo:</h4>";
    while ($entidad = $resultado_entidades->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($entidad['nombre']) . " (" . htmlspecialchars($entidad['email']) . ")";
        echo " <a href='eliminar_contacto_grupo.php?grupo_id=$grupo_id&entidad_id=" . $entidad['id'] . "'>Desagregar</a>";
        echo " <a href='ver_entidad.php?entidad_id=" . $entidad['id'] . "'>Ver</a></li>";
    }
} else {
    echo "<p>No hay entidades en este grupo aún.</p>";
}
?>
</ul>

<!-- Buscador de contactos para agregar al grupo -->
<h3>Añadir más Contactos</h3>
<form method="POST" action="ver_grupo.php?grupo_id=<?= $grupo_id ?>">
    <input type="text" name="buscar_contactos" placeholder="Buscar por nombre o email">
    <button type="submit">Buscar Contacto</button>
</form>

<!-- Mostrar resultados de la búsqueda para agregar contactos al grupo -->
<?php
if (isset($_POST['buscar_contactos'])) {
    $busqueda = '%' . $_POST['buscar_contactos'] . '%';

    // Buscar contactos en la tabla de usuarios
    $sql_contactos = "
        SELECT u.id, u.nombre_usuario, u.email 
        FROM contactos c 
        JOIN usuarios u ON c.contacto_usuario_id = u.id 
        LEFT JOIN grupo_contactos gc ON u.id = gc.contacto_id AND gc.grupo_id = ?
        WHERE c.usuario_id = ? AND (u.nombre_usuario LIKE ? OR u.email LIKE ?) 
        AND gc.contacto_id IS NULL";
    
    $stmt_contactos = $conn->prepare($sql_contactos);
    $stmt_contactos->bind_param("iiss", $grupo_id, $_SESSION['usuario_id'], $busqueda, $busqueda);
    $stmt_contactos->execute();
    $resultado_contactos = $stmt_contactos->get_result();

    // Buscar entidades en la tabla de entidades
    $sql_entidades = "
        SELECT e.id, e.nombre, e.email 
        FROM entidades e
        LEFT JOIN grupo_contactos gc ON e.id = gc.entidad_id AND gc.grupo_id = ?
        WHERE e.usuario_id = ? AND (e.nombre LIKE ? OR e.email LIKE ?)
        AND gc.entidad_id IS NULL";
    
    $stmt_entidades = $conn->prepare($sql_entidades);
    $stmt_entidades->bind_param("iiss", $grupo_id, $_SESSION['usuario_id'], $busqueda, $busqueda);
    $stmt_entidades->execute();
    $resultado_entidades = $stmt_entidades->get_result();

    // Mostrar los resultados de contactos y entidades
    if ($resultado_contactos->num_rows > 0 || $resultado_entidades->num_rows > 0) {
        echo "<h3>Resultados de la búsqueda:</h3><ul>";

        // Mostrar contactos
        while ($contacto = $resultado_contactos->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($contacto['nombre_usuario']) . " (" . htmlspecialchars($contacto['email']) . ") - Contacto";
            // El parámetro es_entidad=0 para contactos (usuarios)
            echo " <a href='agregar_contacto_grupo.php?grupo_id=$grupo_id&contacto_id=" . $contacto['id'] . "&es_entidad=0'>Agregar</a></li>";
        }

        // Mostrar entidades
        while ($entidad = $resultado_entidades->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($entidad['nombre']) . " (" . htmlspecialchars($entidad['email']) . ") - Entidad";
            // El parámetro es_entidad=1 para entidades
            echo " <a href='agregar_contacto_grupo.php?grupo_id=$grupo_id&entidad_id=" . $entidad['id'] . "&es_entidad=1'>Agregar</a></li>";
        }

        echo "</ul>";
    } else {
        echo "<p>No se encontraron contactos ni entidades disponibles para agregar.</p>";
    }
}
?>

<!-- Formulario para modificar el nombre del grupo -->
<h3>Modificar Nombre del Grupo</h3>
<form method="POST" action="ver_grupo.php?grupo_id=<?= $grupo_id ?>">
    <input type="text" name="nuevo_nombre" value="<?= htmlspecialchars($grupo['nombre']) ?>" required>
    <button type="submit">Actualizar Nombre</button>
</form>

<!-- Confirmación para eliminar el grupo -->
<h3>Eliminar Grupo</h3>
<form method="GET" action="ver_grupo.php">
    <input type="hidden" name="grupo_id" value="<?= $grupo_id ?>">
    <button type="submit" name="eliminar_grupo" value="true" onclick="return confirm('¿Estás seguro de que deseas eliminar este grupo? Esta acción no se puede deshacer.');">Eliminar Grupo</button>
</form>

<!-- Botón para volver a My Network usando JavaScript -->
<h3><button onclick="window.location.href='my_network.php'">Volver a My Network</button></h3>

</body>
</html>
