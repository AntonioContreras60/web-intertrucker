<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /Perfil/inicio_sesion.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id']; // Usuario en sesión

// Filtrar y obtener la lista de camioneros
$filtro_camionero = "";
if (isset($_POST['filtro_camionero']) && $_POST['filtro_camionero'] !== "") {
    $filtro_camionero = $_POST['filtro_camionero'];
    $sql_camioneros = "
        SELECT 
            u.id AS usuario_id, 
            u.nombre_usuario, 
            u.email, 
            c.id AS camionero_id, 
            c.tipo_carnet, 
            c.num_licencia, 
            c.fecha_nacimiento, 
            c.fecha_contratacion, 
            c.activo
        FROM usuarios u
        JOIN camioneros c ON c.usuario_id = u.id
        WHERE u.admin_id = (SELECT admin_id FROM usuarios WHERE id = ?)
          AND (u.nombre_usuario LIKE ? OR u.apellidos LIKE ?);
    ";
    $stmt_camioneros = $conn->prepare($sql_camioneros);
    $search_camionero = '%' . $filtro_camionero . '%';
    $stmt_camioneros->bind_param("iss", $usuario_id, $search_camionero, $search_camionero);
} else {
    $sql_camioneros = "
        SELECT 
            u.id AS usuario_id, 
            u.nombre_usuario, 
            u.email, 
            c.id AS camionero_id, 
            c.tipo_carnet, 
            c.num_licencia, 
            c.fecha_nacimiento, 
            c.fecha_contratacion, 
            c.activo
        FROM usuarios u
        JOIN camioneros c ON c.usuario_id = u.id
        WHERE u.admin_id = (SELECT admin_id FROM usuarios WHERE id = ?);
    ";
    $stmt_camioneros = $conn->prepare($sql_camioneros);
    $stmt_camioneros->bind_param("i", $usuario_id);
}

if (!$stmt_camioneros->execute()) {
    die("Error al ejecutar la consulta de camioneros: " . $stmt_camioneros->error);
}
$resultado_camioneros = $stmt_camioneros->get_result();

// Cambiar estado (activo/no activo) de un camionero
if (isset($_GET['toggle_id']) && isset($_GET['estado'])) {
    $camionero_id = $_GET['toggle_id'];
    $nuevo_estado = ($_GET['estado'] == 1) ? 0 : 1; // Alternar el estado

    $sql_toggle = "
        UPDATE camioneros 
        SET activo = ? 
        WHERE id = ? 
          AND usuario_id = (
              SELECT id FROM usuarios 
              WHERE admin_id = (
                  SELECT admin_id FROM usuarios WHERE id = ?
              )
          );
    ";
    $stmt_toggle = $conn->prepare($sql_toggle);
    $stmt_toggle->bind_param("iii", $nuevo_estado, $camionero_id, $usuario_id);

    if ($stmt_toggle->execute()) {
        header('Location: my_truckers.php');
        exit();
    } else {
        die("Error al cambiar el estado del camionero: " . $stmt_toggle->error);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>My Truckers</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Ajustes para que la tabla se vea más espaciosa */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1rem;
        }
        thead {
            background-color: #f5f5f5;
        }
        th {
            text-align: left;
            padding: 10px 15px;
        }
        td {
            border: 1px solid #ccc;
            padding: 10px 15px;
        }

        /* Clases para el toggle de estado */
        .toggle-button {
            text-decoration: none;
            padding: 6px 12px;
            color: #fff;
            border-radius: 4px;
        }
        .toggle-button.activo {
            background-color: #4caf50; /* Verde */
        }
        .toggle-button.no-activo {
            background-color: #f44336; /* Rojo */
        }

        h1, h2 {
            margin-bottom: 0.5rem;
        }
        .button-primary {
            background-color: #007bff;
            border: none;
            padding: 8px 14px;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        .button-primary:hover {
            background-color: #0056b3;
        }
        .filter-form, .add-button {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <h1>Gestionar Camioneros</h1>

    <!-- Botón para ir a la página de añadir camionero -->
    <h2>Añadir Camionero</h2>
    <button class="button-primary" onclick="window.location.href='agregar_camionero.php';">
        Añadir Camionero
    </button>
  
    <!-- Filtro y listado de camioneros -->
    <h2>Filtrar Camioneros</h2>
    <form method="POST" action="my_truckers.php" class="filter-form">
        <input type="text" name="filtro_camionero" placeholder="Buscar por nombre o apellidos..." 
               value="<?= htmlspecialchars($filtro_camionero) ?>">
        <button type="submit" class="button-primary">Buscar</button>
    </form>

    <h2>Listado de Camioneros</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Carnet</th>
                <th>Número de Licencia</th>
                <th>Fecha de Nacimiento</th>
                <th>Fecha de Contratación</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($camionero = $resultado_camioneros->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($camionero['nombre_usuario']) ?></td>
                    <td><?= htmlspecialchars($camionero['email']) ?></td>
                    <td><?= htmlspecialchars($camionero['tipo_carnet']) ?></td>
                    <td><?= htmlspecialchars($camionero['num_licencia']) ?></td>
                    <td><?= htmlspecialchars($camionero['fecha_nacimiento']) ?></td>
                    <td><?= htmlspecialchars($camionero['fecha_contratacion']) ?></td>
                    <td>
                        <span class="toggle-button <?= $camionero['activo'] ? 'activo' : 'no-activo' ?>">
                            <?= $camionero['activo'] ? 'Activo' : 'No Activo' ?>
                        </span>
                    </td>
                    <td>
                        <a href="editar_camionero.php?id=<?= $camionero['camionero_id'] ?>">Detalles</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php include 'footer.php'; ?>
</body>
</html>
