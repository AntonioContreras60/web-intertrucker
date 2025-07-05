<?php
session_start();
include 'conexion.php';

// Solo permitir acceso si el usuario es administrador y no gestor
if ($_SESSION['rol'] !== 'administrador' || $_SESSION['es_gestor']) {
    die("<p style='font-size: 3rem; font-weight: bold;'>No tiene permiso para acceder a esta sección</p>");
}

// Obtener el ID del administrador
$admin_id = $_SESSION['usuario_id'];

// Consulta para obtener los gestores y verificar si son camioneros
$sql = "SELECT u.id, u.nombre_usuario, u.apellidos, u.estado, 
               CASE WHEN c.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS es_camionero
        FROM usuarios u
        LEFT JOIN camioneros c ON u.id = c.usuario_id
        WHERE u.admin_id = ? AND u.rol = 'gestor'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Gestores</title>
    <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="/header.css">
  <script src="/header.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <h1>Gestión de Gestores</h1>
    <p>Un gestor tiene las mismas funciones que un administrador, salvo la de administrar gestores.<br>
    Para diferenciar los portes gestionados por un gestor del resto de gestores dentro de la misma empresa, se creará un submenú en 'Portes Nuevos'.<br>
    Los gestores pueden "tomar" portes de otros gestores o administrador de la misma empresa.
</p>
    
    <a href="agregar_gestor.php">
    <button type="button">➕ Añadir Nuevo Gestor</button>
</a>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Nombre</th>
            <th>Apellidos</th>
            <th>Estado</th>
            <th>¿Camionero?</th>
            <th>Acciones</th>
        </tr>
        <?php while ($colaborador = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($colaborador['nombre_usuario']); ?></td>
            <td><?php echo htmlspecialchars($colaborador['apellidos']); ?></td>
            <td><?php echo htmlspecialchars($colaborador['estado']); ?></td>
            <td><?php echo $colaborador['es_camionero']; ?></td>
            <td>
                <!-- Ver detalles -->
                <form action="detalles_colaborador.php" method="GET" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $colaborador['id']; ?>">
                    <button type="submit">Detalles</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    </body>
</html>
