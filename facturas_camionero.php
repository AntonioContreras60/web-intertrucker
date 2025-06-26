<?php
session_start();
include 'conexion.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
// Para este archivo (facturas_camionero.php), forzamos el rol a "camionero"
// (Si el rol se guarda en la sesión, podrías usar: $_SESSION['rol'] ?? 'camionero')
$rol = 'camionero';

$nombre_camionero_header = "Desconocido";

// Obtener el nombre del camionero asociado
$stmt = $conn->prepare("SELECT nombre FROM camioneros WHERE usuario_id = ?");
if (!$stmt) {
    die("Error en prepare: " . $conn->error);
}
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $camionero = $result->fetch_assoc();
    $nombre_camionero_header = $camionero['nombre'];
}
$stmt->close();

// Incluir el header para camioneros
include 'header_camionero.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Meta viewport para responsividad -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Facturas - InterTrucker</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main>
        <h1>Facturas</h1>

        <!-- Solo mostramos la acción y filtros si el rol no es camionero -->
        <?php if ($rol != 'camionero'): ?>
            <a href="registro_nueva_factura.php" class="button">Registrar Nueva Factura</a>
        <?php endif; ?>

        <h2>Listado de Facturas</h2>

        <?php if ($rol != 'camionero'): ?>
        <form method="get" action="facturas.php" class="filtros">
            <label for="fecha_inicio">Desde:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $_GET['fecha_inicio'] ?? ''; ?>">

            <label for="fecha_fin">Hasta:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $_GET['fecha_fin'] ?? ''; ?>">

            <label for="tipo">Tipo:</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                <option value="Dietas" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'Dietas') ? 'selected' : ''; ?>>Dietas</option>
                <option value="Mantenimiento" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'Mantenimiento') ? 'selected' : ''; ?>>Mantenimiento</option>
                <option value="Combustible" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'Combustible') ? 'selected' : ''; ?>>Combustible</option>
                <option value="Peajes" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'Peajes') ? 'selected' : ''; ?>>Peajes</option>
                <option value="Alojamiento" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'Alojamiento') ? 'selected' : ''; ?>>Alojamiento</option>
            </select>

            <!-- Si se requieren filtros por miembro, se debería obtener la variable $miembros previamente -->
            <!--
            <label for="miembro">Miembro:</label>
            <select id="miembro" name="miembro">
                <option value="">Todos</option>
                <?php foreach ($miembros as $miembro): ?>
                    <option value="<?php echo $miembro['id']; ?>" <?php echo (isset($_GET['miembro']) && $_GET['miembro'] == $miembro['id']) ? 'selected' : ''; ?>>
                        <?php echo "{$miembro['nombre']} ({$miembro['rol']})"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            -->
            <button type="submit">Filtrar</button>
            <a href="facturas.php" class="button limpiar">Limpiar Filtros</a>
        </form>
        <?php endif; ?>

        <!-- Tabla de Facturas -->
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Foto</th>
                    <?php if ($rol != 'camionero') echo '<th>Hecho por</th>'; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $suma_total = 0;
                $condiciones = [];
                $parametros = [];
                $tipos_parametros = "";

                if (!empty($_GET['fecha_inicio'])) {
                    $condiciones[] = "f.fecha >= ?";
                    $parametros[] = $_GET['fecha_inicio'];
                    $tipos_parametros .= "s";
                }
                if (!empty($_GET['fecha_fin'])) {
                    $condiciones[] = "f.fecha <= ?";
                    $parametros[] = $_GET['fecha_fin'];
                    $tipos_parametros .= "s";
                }
                if (!empty($_GET['tipo'])) {
                    $condiciones[] = "f.tipo = ?";
                    $parametros[] = $_GET['tipo'];
                    $tipos_parametros .= "s";
                }

                if ($rol == 'administrador' || $rol == 'gestor') {
                    $sql = "SELECT f.id, f.fecha, f.tipo, f.cantidad, f.foto, 
                                   IFNULL(c.nombre, u.nombre_usuario) AS hecho_por
                            FROM facturas f
                            LEFT JOIN camioneros c ON f.camionero_id = c.id
                            LEFT JOIN usuarios u ON f.usuario_id = u.id
                            WHERE u.admin_id = ?";
                    $parametros = array_merge([$admin_id], $parametros);
                    $tipos_parametros = "i" . $tipos_parametros;
                } elseif ($rol == 'camionero') {
                    $sql = "SELECT id, fecha, tipo, cantidad, foto 
                            FROM facturas 
                            WHERE camionero_id = ?";
                    $parametros = [$usuario_id];
                    $tipos_parametros = "i";
                }

                if (!empty($condiciones)) {
                    $sql .= " AND " . implode(" AND ", $condiciones);
                }
                $sql .= " ORDER BY f.fecha DESC";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die("Error en la preparación de la consulta: " . $conn->error);
                }
                $stmt->bind_param($tipos_parametros, ...$parametros);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $suma_total += $row['cantidad'];
                        echo "<tr>
                                <td>{$row['fecha']}</td>
                                <td>{$row['tipo']}</td>
                                <td>{$row['cantidad']} €</td>";
                        if (!empty($row['foto'])) {
                            echo "<td>Sin acciones</td>";
                        } else {
                            echo "<td>Sin Foto</td>";
                        }
                        if ($rol != 'camionero') {
                            echo "<td>{$row['hecho_por']}</td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No se encontraron facturas.</td></tr>";
                }
                $stmt->close();
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><strong>Total:</strong></td>
                    <td><strong><?php echo number_format($suma_total, 2); ?> €</strong></td>
                </tr>
            </tfoot>
        </table>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
