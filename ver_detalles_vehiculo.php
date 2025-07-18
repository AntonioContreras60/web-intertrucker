<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(["administrador","gestor"]);
include 'conexion.php'; // Ajusta la ruta si tu archivo de conexión está en otro directorio
include 'funciones_subida.php';

// Verificar si llega vehiculo_id por GET
if (!isset($_GET['vehiculo_id'])) {
    die("Falta el vehiculo_id en la URL (ej: ver_detalles_vehiculo.php?vehiculo_id=XX).");
}
$vehiculo_id = intval($_GET['vehiculo_id']);
$admin_id = $_SESSION["admin_id"] ?? 0;
$chk = $conn->prepare("SELECT v.id FROM vehiculos v JOIN usuarios u ON v.usuario_id=u.id WHERE v.id=? AND u.admin_id=? LIMIT 1");
$chk->bind_param("ii", $vehiculo_id, $admin_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    http_response_code(403);
    exit("Acceso denegado");
}
$chk->close();

// Ruta para documentos manejada por la función de subida

// Procesar subida de nuevos documentos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['nuevos_docs']['name'][0])) {
    $docs = $_FILES['nuevos_docs'];
    for ($i = 0; $i < count($docs['name']); $i++) {
        $f = [
            'name'     => $docs['name'][$i],
            'type'     => $docs['type'][$i],
            'tmp_name' => $docs['tmp_name'][$i],
            'error'    => $docs['error'][$i],
            'size'     => $docs['size'][$i]
        ];

        $resultado = subir_archivo($f, 'uploads/vehiculos', 'vehiculo');
        if (str_starts_with($resultado, 'Error')) {
            echo "<p>{$resultado}</p>";
            continue;
        }

        $rutaBD   = $resultado;
        $tipoMime = $f['type'];
        $tamKB    = round(filesize(__DIR__ . '/' . $rutaBD) / 1024);

        $sqlD = "INSERT INTO documentos_vehiculos (vehiculo_id, nombre_archivo, ruta_archivo, tipo_mime, tamano_kb) VALUES (?, ?, ?, ?, ?)";
        $stmtD = $conn->prepare($sqlD);
        if ($stmtD) {
            $stmtD->bind_param('isssi', $vehiculo_id, $f['name'], $rutaBD, $tipoMime, $tamKB);
            $stmtD->execute();
            $stmtD->close();
        }
    }
}

// Consulta para obtener los datos del vehículo
$sql = "SELECT * FROM vehiculos WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}
$stmt->bind_param("i", $vehiculo_id);

if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("No se encontró el vehículo con ID " . $vehiculo_id);
}
$vehiculo = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Vehículo</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<h1>Detalles del Vehículo (ID: <?= htmlspecialchars($vehiculo_id) ?>)</h1>

<table style="width: 100%; max-width: 700px; border-collapse: collapse; margin-bottom: 30px;">
    <tbody>
        <tr><td><strong>Matrícula:</strong></td><td><?= htmlspecialchars($vehiculo['matricula']) ?></td></tr>
        <tr><td><strong>Marca:</strong></td><td><?= htmlspecialchars($vehiculo['marca']) ?></td></tr>
        <tr><td><strong>Modelo:</strong></td><td><?= htmlspecialchars($vehiculo['modelo']) ?></td></tr>
        <tr><td><strong>Año de Fabricación:</strong></td><td><?= htmlspecialchars($vehiculo['ano_fabricacion']) ?></td></tr>
        <tr><td><strong>Tipo Principal:</strong></td><td><?= htmlspecialchars($vehiculo['nivel_1']) ?></td></tr>
        <tr><td><strong>Subcategoría:</strong></td><td><?= htmlspecialchars($vehiculo['nivel_2']) ?></td></tr>
        <tr><td><strong>Especificación:</strong></td><td><?= htmlspecialchars($vehiculo['nivel_3']) ?></td></tr>
        <tr><td><strong>Capacidad (Ton):</strong></td><td><?= htmlspecialchars($vehiculo['capacidad']) ?></td></tr>
        <tr><td><strong>Volumen (m³):</strong></td><td><?= htmlspecialchars($vehiculo['volumen']) ?></td></tr>
        <tr><td><strong>Cap. Arrastre (Ton):</strong></td><td><?= htmlspecialchars($vehiculo['capacidad_arrastre']) ?></td></tr>
        <tr><td><strong>Número de Ejes:</strong></td><td><?= htmlspecialchars($vehiculo['numero_ejes']) ?></td></tr>
        <tr><td><strong>Temperatura Controlada:</strong></td><td><?= $vehiculo['temperatura_controlada'] ? "Sí" : "No" ?></td></tr>
        <tr><td><strong>Formas de Carga:</strong></td><td>
            <?php
            $cargas = [];
            if ($vehiculo['forma_carga_lateral']) $cargas[] = "Lateral";
            if ($vehiculo['forma_carga_detras']) $cargas[] = "Detrás";
            if ($vehiculo['forma_carga_arriba'])  $cargas[] = "Arriba";
            echo count($cargas) > 0 ? implode(", ", $cargas) : "Ninguna";
            ?>
        </td></tr>
        <tr><td><strong>ADR:</strong></td><td><?= $vehiculo['adr'] ? "Sí" : "No" ?></td></tr>
        <tr><td><strong>Doble Conductor:</strong></td><td><?= $vehiculo['doble_conductor'] ? "Sí" : "No" ?></td></tr>
        <tr><td><strong>Plataforma Elevadora:</strong></td><td><?= $vehiculo['plataforma_elevadora'] ? "Sí" : "No" ?></td></tr>
        <tr><td><strong>Teléfono:</strong></td><td><?= htmlspecialchars($vehiculo['telefono']) ?></td></tr>
        <tr><td><strong>Observaciones:</strong></td><td><?= nl2br(htmlspecialchars($vehiculo['observaciones'])) ?></td></tr>
        <tr>
            <td><strong>Estado:</strong></td>
            <td><?= ($vehiculo['activo'] == 1) ? "<span style='color:green;'>Activo</span>" : "<span style='color:red;'>No Activo</span>"; ?></td>
        </tr>
    </tbody>
</table>

<button id="toggleEditBtn">Editar camión</button>
<div id="editVehicleForm" class="hidden" style="margin-top:20px;">
<h2>Editar Vehículo</h2>
<form method="POST" action="actualizar_vehiculo.php">
    <input type="hidden" name="vehiculo_id" value="<?= $vehiculo_id ?>">
    <label>Matrícula:</label>
    <input type="text" name="matricula" value="<?= htmlspecialchars($vehiculo['matricula']) ?>" required><br>

    <label>Marca:</label>
    <input type="text" name="marca" value="<?= htmlspecialchars($vehiculo['marca']) ?>" required><br>

    <label>Modelo:</label>
    <input type="text" name="modelo" value="<?= htmlspecialchars($vehiculo['modelo']) ?>" required><br>

    <label>Año de Fabricación:</label>
    <input type="number" name="ano_fabricacion" value="<?= htmlspecialchars($vehiculo['ano_fabricacion']) ?>"><br>

    <label>Tipo Principal:</label>
    <input type="text" name="nivel_1" value="<?= htmlspecialchars($vehiculo['nivel_1']) ?>" required><br>

    <label>Subcategoría:</label>
    <input type="text" name="nivel_2" value="<?= htmlspecialchars($vehiculo['nivel_2']) ?>" required><br>

    <label>Especificación:</label>
    <input type="text" name="nivel_3" value="<?= htmlspecialchars($vehiculo['nivel_3']) ?>"><br>

    <label>Capacidad (Ton):</label>
    <input type="number" step="0.1" name="capacidad" value="<?= htmlspecialchars($vehiculo['capacidad']) ?>"><br>

    <label>Volumen (m³):</label>
    <input type="number" step="0.1" name="volumen" value="<?= htmlspecialchars($vehiculo['volumen']) ?>"><br>

    <label>Cap. Arrastre (Ton):</label>
    <input type="number" step="0.1" name="capacidad_arrastre" value="<?= htmlspecialchars($vehiculo['capacidad_arrastre']) ?>"><br>

    <label>Número de Ejes:</label>
    <input type="number" name="numero_ejes" value="<?= htmlspecialchars($vehiculo['numero_ejes']) ?>"><br>

    <label>Temperatura Controlada:</label>
    <input type="checkbox" name="temperatura_controlada" value="1" <?= $vehiculo['temperatura_controlada'] ? 'checked' : '' ?>><br>

    <label>Forma de Carga - Lateral:</label>
    <input type="checkbox" name="forma_carga_lateral" value="1" <?= $vehiculo['forma_carga_lateral'] ? 'checked' : '' ?>><br>

    <label>Forma de Carga - Detrás:</label>
    <input type="checkbox" name="forma_carga_detras" value="1" <?= $vehiculo['forma_carga_detras'] ? 'checked' : '' ?>><br>

    <label>Forma de Carga - Arriba:</label>
    <input type="checkbox" name="forma_carga_arriba" value="1" <?= $vehiculo['forma_carga_arriba'] ? 'checked' : '' ?>><br>

    <label>ADR:</label>
    <input type="checkbox" name="adr" value="1" <?= $vehiculo['adr'] ? 'checked' : '' ?>><br>

    <label>Doble Conductor:</label>
    <input type="checkbox" name="doble_conductor" value="1" <?= $vehiculo['doble_conductor'] ? 'checked' : '' ?>><br>

    <label>Plataforma Elevadora:</label>
    <input type="checkbox" name="plataforma_elevadora" value="1" <?= $vehiculo['plataforma_elevadora'] ? 'checked' : '' ?>><br>

    <label>Teléfono:</label>
    <input type="text" name="telefono" value="<?= htmlspecialchars($vehiculo['telefono']) ?>"><br>

    <label>Observaciones:</label>
    <textarea name="observaciones" rows="4" cols="50"><?= htmlspecialchars($vehiculo['observaciones']) ?></textarea><br>

    <label>Activo:</label>
    <input type="checkbox" name="activo" value="1" <?= $vehiculo['activo'] ? 'checked' : '' ?>><br><br>

    <button type="submit">Guardar Cambios</button>
</form>
</div>


<?php
$sqlDocs = "SELECT id, nombre_archivo, ruta_archivo, fecha_subida FROM documentos_vehiculos WHERE vehiculo_id = ? ORDER BY fecha_subida DESC";
$stmt_docs = $conn->prepare($sqlDocs);
$stmt_docs->bind_param("i", $vehiculo_id);
$stmt_docs->execute();
$res_docs = $stmt_docs->get_result();
$documentos = $res_docs->fetch_all(MYSQLI_ASSOC);
?>

<h2>Documentos del Vehículo</h2>
<?php if (!empty($documentos)): ?>
<table style="width:100%; max-width:700px; border-collapse: collapse;">
    <thead>
        <tr>
            <th>Archivo</th>
            <th>Fecha</th>
            <th>Descargar</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($documentos as $doc): ?>
        <tr>
            <td><?= htmlspecialchars($doc['nombre_archivo']) ?></td>
            <td><?= htmlspecialchars($doc['fecha_subida']) ?></td>
            <td><a href="descargar_documento.php?id=<?= $doc['id'] ?>&tipo=vehiculo" target="_blank">Ver / Descargar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>No hay documentos asociados a este vehículo.</p>
<?php endif; ?>

<h3>Subir nuevos documentos</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="nuevos_docs[]" multiple accept=".pdf,.jpg,.jpeg,.png">
    <button type="submit">Subir</button>
</form>

<br>
<button onclick="history.back()">Volver</button>

<?php include 'footer.php'; ?>

<script>
    const toggleBtn = document.getElementById('toggleEditBtn');
    const editForm = document.getElementById('editVehicleForm');
    toggleBtn.addEventListener('click', () => {
        if (editForm.classList.contains('hidden')) {
            editForm.classList.remove('hidden');
        } else {
            editForm.classList.add('hidden');
        }
    });
</script>
</body>
</html>


