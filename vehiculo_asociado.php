<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php'; // Ajusta la ruta si tu archivo de conexión está en otro directorio

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Verificamos si llega ?usuario_id= por GET (ID del asociado)
if (!isset($_GET['usuario_id'])) {
    die("Falta el usuario_id del asociado en la URL (?usuario_id=XXX).");
}
$asociado_id = intval($_GET['usuario_id']);

/* =======================================================
   OBTENER TODOS LOS VEHÍCULOS DEL ASOCIADO (SIN FILTRO)
   ======================================================= */
$sql_vehiculos = "SELECT * FROM vehiculos WHERE usuario_id = ?";
$stmt_vehiculos = $conn->prepare($sql_vehiculos);
if (!$stmt_vehiculos) {
    die("Error al preparar la consulta de vehículos: " . $conn->error);
}
$stmt_vehiculos->bind_param("i", $asociado_id);
if (!$stmt_vehiculos->execute()) {
    die("Error al ejecutar la consulta de vehículos: " . $stmt_vehiculos->error);
}
$resultado_vehiculos = $stmt_vehiculos->get_result();

/* =======================================================
   CREAR TREN (marcando varios vehículos)
   ======================================================= */
$mensaje_tren = ""; // Para notificar éxito/error al crear tren

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tren'])) {
    if (!isset($_POST['vehiculo_seleccion']) || count($_POST['vehiculo_seleccion']) === 0) {
        $mensaje_tren = "No se seleccionó ningún vehículo para formar el tren.";
    } else {
        $vehiculos_seleccion = $_POST['vehiculo_seleccion'];

        // 1) Generar nombre de tren a partir de "Marca Matricula" de cada vehículo
        $sqlVeh = "SELECT marca, matricula FROM vehiculos WHERE id = ?";
        $stmtVeh = $conn->prepare($sqlVeh);
        if (!$stmtVeh) {
            die("Error preparando consulta de marca/matricula: " . $conn->error);
        }

        $nombresVehiculos = [];
        foreach ($vehiculos_seleccion as $vid) {
            $vid_int = intval($vid);
            $stmtVeh->bind_param("i", $vid_int);
            $stmtVeh->execute();
            $resV = $stmtVeh->get_result();
            if ($resV->num_rows > 0) {
                $rowV = $resV->fetch_assoc();
                $marMat = $rowV['marca'] . " " . $rowV['matricula'];
                $nombresVehiculos[] = $marMat;
            }
        }

        if (count($nombresVehiculos) === 0) {
            $mensaje_tren = "No se pudo obtener marca/matrícula de los vehículos seleccionados.";
        } else {
            $nombreTren = implode(" - ", $nombresVehiculos);

            // 2) Insertar en 'tren'
            $sqlTren = "INSERT INTO tren (tren_nombre) VALUES (?)";
            $stmt_tren = $conn->prepare($sqlTren);
            if (!$stmt_tren) {
                die("Error al preparar inserción de tren: " . $conn->error);
            }
            $stmt_tren->bind_param("s", $nombreTren);
            if (!$stmt_tren->execute()) {
                die("Error al crear tren: " . $stmt_tren->error);
            }
            $tren_id = $stmt_tren->insert_id;

            // 3) Insertar en tren_vehiculos
            $sqlTV = "INSERT INTO tren_vehiculos (tren_id, vehiculo_id, inicio_vehiculo_tren)
                      VALUES (?, ?, NOW())";
            $stmt_tv = $conn->prepare($sqlTV);
            if (!$stmt_tv) {
                die("Error al preparar inserción en tren_vehiculos: " . $conn->error);
            }

            foreach ($vehiculos_seleccion as $veh_id) {
                $veh_id_int = intval($veh_id);
                $stmt_tv->bind_param("ii", $tren_id, $veh_id_int);
                $stmt_tv->execute();
            }

            // 4) Asociar el tren al camionero (tren_camionero)
            //    => Buscar el camionero que tenga usuario_id = $asociado_id
            $sqlCam = "SELECT id FROM camioneros WHERE usuario_id = ?";
            $stmtCam = $conn->prepare($sqlCam);
            $stmtCam->bind_param("i", $asociado_id);
            $stmtCam->execute();
            $resCam = $stmtCam->get_result();
            if ($resCam->num_rows === 0) {
                // No se encontró el camionero para ese user_id
                $mensaje_tren .= "\n⚠ No se pudo asociar el tren al camionero (no encontrado en 'camioneros').";
            } else {
                // Insertar en tren_camionero
                $rowCam = $resCam->fetch_assoc();
                $camionero_id = $rowCam['id'];

                $sqlTC = "INSERT INTO tren_camionero (tren_id, camionero_id, inicio_tren_camionero)
                          VALUES (?, ?, NOW())";
                $stmtTC = $conn->prepare($sqlTC);
                if (!$stmtTC) {
                    $mensaje_tren .= "\n⚠ Error al preparar inserción en tren_camionero: " . $conn->error;
                } else {
                    $stmtTC->bind_param("ii", $tren_id, $camionero_id);
                    $stmtTC->execute();
                }
            }

            // Mensaje de éxito final
            $mensaje_tren = "¡Tren creado con ID=$tren_id! Nombre: $nombreTren" . ($mensaje_tren ? $mensaje_tren : "");
        }
    }
}

/* =======================================================
   ELIMINAR TREN
   ======================================================= */
$mensaje_borrar_tren = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_tren'])) {
    $tren_id_eliminar = intval($_POST['tren_id'] ?? 0);
    if ($tren_id_eliminar <= 0) {
        $mensaje_borrar_tren = "ID de tren inválido.";
    } else {
        // 1) Borrar primero de tren_vehiculos
        $sqlDelTV = "DELETE FROM tren_vehiculos WHERE tren_id = ?";
        $stmt_delTV = $conn->prepare($sqlDelTV);
        $stmt_delTV->bind_param("i", $tren_id_eliminar);
        $stmt_delTV->execute();

        // 2) Borrar de tren_camionero
        $sqlDelTC = "DELETE FROM tren_camionero WHERE tren_id = ?";
        $stmt_delTC = $conn->prepare($sqlDelTC);
        $stmt_delTC->bind_param("i", $tren_id_eliminar);
        $stmt_delTC->execute();

        // 3) Borrar de tren
        $sqlDelT = "DELETE FROM tren WHERE id = ?";
        $stmt_delT = $conn->prepare($sqlDelT);
        $stmt_delT->bind_param("i", $tren_id_eliminar);
        if ($stmt_delT->execute()) {
            $mensaje_borrar_tren = "Tren con ID=$tren_id_eliminar eliminado correctamente.";
        } else {
            $mensaje_borrar_tren = "Error al eliminar el tren con ID=$tren_id_eliminar: " . $stmt_delT->error;
        }
    }
}

/* =======================================================
   ELIMINAR VEHÍCULO
   ======================================================= */
$mensaje_borrar_vehiculo = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_vehiculo'])) {
    $veh_id_eliminar = intval($_POST['vehiculo_id'] ?? 0);
    if ($veh_id_eliminar <= 0) {
        $mensaje_borrar_vehiculo = "ID de vehículo inválido.";
    } else {
        // 1) Borrar referencias en tren_vehiculos
        $sqlDelTV = "DELETE FROM tren_vehiculos WHERE vehiculo_id = ?";
        $stmt_delTV = $conn->prepare($sqlDelTV);
        $stmt_delTV->bind_param("i", $veh_id_eliminar);
        $stmt_delTV->execute();

        // 2) Borrar de vehiculos
        $sqlDelV = "DELETE FROM vehiculos WHERE id = ?";
        $stmt_delV = $conn->prepare($sqlDelV);
        $stmt_delV->bind_param("i", $veh_id_eliminar);
        if ($stmt_delV->execute()) {
            $mensaje_borrar_vehiculo = "Vehículo con ID=$veh_id_eliminar eliminado correctamente.";
        } else {
            $mensaje_borrar_vehiculo = "Error al eliminar vehículo con ID=$veh_id_eliminar: " . $stmt_delV->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vehículos del Asociado</title>
    <link rel="stylesheet" href="styles.css?v=1.1">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        .activo {
            background-color: #8bc34a; 
        }
        .no_activo {
            background-color: #f44336;
        }
        .mensaje {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            color: #fff;
        }
        .exito {
            background-color: #4caf50;
        }
        .error {
            background-color: #f44336;
        }
    </style>
    <script>
    // Confirmación al eliminar vehículo
    function confirmarEliminarVehiculo() {
        return confirm("¿Estás seguro de que deseas eliminar este vehículo?");
    }
    // Confirmación al eliminar tren
    function confirmarEliminarTren() {
        return confirm("¿Estás seguro de que deseas eliminar este tren?");
    }
    </script>
</head>
<body>
<?php require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; ?>

<h1>Vehículos del Asociado (ID: <?= htmlspecialchars($asociado_id) ?>)</h1>

<!-- Mensajes de éxito/error -->
<?php if (!empty($mensaje_tren)): ?>
    <div class="mensaje exito">
        <?= nl2br(htmlspecialchars($mensaje_tren)) ?>
    </div>
<?php endif; ?>

<?php if (!empty($mensaje_borrar_tren)): ?>
    <div class="mensaje exito">
        <?= nl2br(htmlspecialchars($mensaje_borrar_tren)) ?>
    </div>
<?php endif; ?>

<?php if (!empty($mensaje_borrar_vehiculo)): ?>
    <div class="mensaje exito">
        <?= nl2br(htmlspecialchars($mensaje_borrar_vehiculo)) ?>
    </div>
<?php endif; ?>

<!-- Enlace para ir al formulario de añadir vehículo (página aparte) -->
<p>
    <a href="agregar_vehiculo_asociado.php?usuario_id=<?= $asociado_id ?>">
        Añadir Vehículo
    </a>
</p>

<h2>Listado de vehículos de este asociado</h2>

<!--
    TABLA DE VEHÍCULOS: Fuera de cualquier formulario global,
    para evitar anidamientos.
-->
<table>
    <tr>
        <th>Seleccionar</th>
        <th>Matrícula</th>
        <th>Marca</th>
        <th>Modelo</th>
        <th>Año</th>
        <th>Tipo Principal</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php if ($resultado_vehiculos->num_rows > 0): ?>
        <?php while ($fila = $resultado_vehiculos->fetch_assoc()): ?>
            <?php
                $vehId = (int)$fila['id'];
                $estado = ($fila['activo'] == 1) ? "Activo" : "No Activo";
                $clase_estado = ($fila['activo'] == 1) ? "activo" : "no_activo";
            ?>
            <tr>
                <!--
                    Checkbox para el tren
                    NOTA: Usamos form="formCrearTren" para indicar
                    que este checkbox pertenece al formulario
                    cuyo id es "formCrearTren" (definido abajo).
                -->
                <td style="text-align:center;">
                    <input type="checkbox"
                           name="vehiculo_seleccion[]"
                           value="<?= $vehId ?>"
                           form="formCrearTren">
                </td>
                <td><?= htmlspecialchars($fila['matricula']) ?></td>
                <td><?= htmlspecialchars($fila['marca']) ?></td>
                <td><?= htmlspecialchars($fila['modelo']) ?></td>
                <td><?= htmlspecialchars($fila['ano_fabricacion']) ?></td>
                <td><?= htmlspecialchars($fila['nivel_1']) ?></td>
                <td class="<?= $clase_estado ?>"><?= $estado ?></td>
                <td>
                    <!-- Ver detalles -->
                    <a href="ver_detalles_vehiculo.php?vehiculo_id=<?= $vehId ?>">Ver Detalles</a>
                    &nbsp;|&nbsp;
                    
                    <!-- Formulario para eliminar este vehículo (separado) -->
                    <form method="POST"
                          action="vehiculo_asociado.php?usuario_id=<?= $asociado_id ?>"
                          style="display:inline;"
                          onsubmit="return confirmarEliminarVehiculo();">
                        <input type="hidden" name="eliminar_vehiculo" value="1">
                        <input type="hidden" name="vehiculo_id" value="<?= $vehId ?>">
                        <button type="submit" class="btn-small">
                            Eliminar
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8">No se encontraron vehículos para este asociado.</td>
        </tr>
    <?php endif; ?>
</table>

<?php
// SOLAMENTE si hay vehículos, mostramos el botón para crear tren.
if ($resultado_vehiculos->num_rows > 0):
?>
    <!-- 
        Formulario PARA CREAR TREN:
        - Incluimos un <input hidden> para que se dispare la lógica "crear_tren"
        - El checkbox de cada vehículo se asocia con este form usando form="formCrearTren"
    -->
    <form method="POST"
          action="vehiculo_asociado.php?usuario_id=<?= $asociado_id ?>"
          id="formCrearTren"
          style="margin-top: 1rem;">
        <input type="hidden" name="crear_tren" value="1">
        <button type="submit">Crear Tren</button>
    </form>
<?php endif; ?>

<hr>

<!-- LISTADO DE TRENES (que incluyan al menos un vehículo de este asociado) -->
<?php
$sqlTrenes = "
    SELECT
       t.id AS tren_id,
       t.tren_nombre,
       COUNT(tv.vehiculo_id) AS total_vehiculos
    FROM tren t
    JOIN tren_vehiculos tv ON t.id = tv.tren_id
    JOIN vehiculos v ON tv.vehiculo_id = v.id
    WHERE v.usuario_id = ?
    GROUP BY t.id
    ORDER BY t.id DESC
";
$stmtTrenes = $conn->prepare($sqlTrenes);
$stmtTrenes->bind_param("i", $asociado_id);
$stmtTrenes->execute();
$resTrenes = $stmtTrenes->get_result();
?>

<h2>Trenes de este asociado</h2>
<?php if ($resTrenes->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID Tren</th>
            <th>Nombre del Tren</th>
            <th># Vehículos</th>
            <th>Acciones</th>
        </tr>
        <?php while ($rowT = $resTrenes->fetch_assoc()): ?>
            <?php
                $tid = (int)$rowT['tren_id'];
                $tname = $rowT['tren_nombre'];
                $tcount = (int)$rowT['total_vehiculos'];
            ?>
            <tr>
                <td><?= htmlspecialchars($tid) ?></td>
                <td><?= htmlspecialchars($tname) ?></td>
                <td><?= $tcount ?></td>
                <td>
                    <!-- Eliminar tren -->
                    <form method="POST"
                          action="vehiculo_asociado.php?usuario_id=<?= $asociado_id ?>"
                          style="display:inline;"
                          onsubmit="return confirmarEliminarTren();">
                        <input type="hidden" name="tren_id" value="<?= $tid ?>">
                        <button type="submit" name="eliminar_tren">
                            Eliminar
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>No se han creado trenes con los vehículos de este asociado.</p>
<?php endif; ?>

<br>
<!-- Botón para volver a la lista de asociados -->
<div style="text-align: center; margin-top: 20px;">
  <button onclick="window.location.href='https://intertrucker.net/mis_asociados.php'">
    Volver a la lista de asociados
  </button>
</div>


</body>
</html>
