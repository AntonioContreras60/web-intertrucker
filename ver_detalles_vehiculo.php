<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php'; // Ajusta la ruta si tu archivo de conexión está en otro directorio

// Verificar si el usuario está autenticado (opcional, depende de tu app)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar si llega vehiculo_id por GET
if (!isset($_GET['vehiculo_id'])) {
    die("Falta el vehiculo_id en la URL (ej: ver_detalles_vehiculo.php?vehiculo_id=XX).");
}
$vehiculo_id = intval($_GET['vehiculo_id']);

// Ruta para documentos
$uploadDirVeh = __DIR__ . '/uploads/vehiculos/';
if (!is_dir($uploadDirVeh)) {
    mkdir($uploadDirVeh, 0777, true);
}

function procesarArchivoVehiculo($fileData, $vehiculoId, $conn) {
    global $uploadDirVeh;
    if ($fileData['error'] === UPLOAD_ERR_OK) {
        $tmpName      = $fileData['tmp_name'];
        $originalName = basename($fileData['name']);
        $uniqueName   = time() . '_' . $originalName;
        $destPath     = $uploadDirVeh . $uniqueName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $rutaBD   = 'uploads/vehiculos/' . $uniqueName;
            $tipoMime = $fileData['type'];
            $tamKB    = round(filesize($destPath) / 1024);

            $sqlD = "INSERT INTO documentos_vehiculos (vehiculo_id, nombre_archivo, ruta_archivo, tipo_mime, tamano_kb) VALUES (?, ?, ?, ?, ?)";
            $stmtD = $conn->prepare($sqlD);
            if ($stmtD) {
                $stmtD->bind_param('isssi', $vehiculoId, $originalName, $rutaBD, $tipoMime, $tamKB);
                $stmtD->execute();
                $stmtD->close();
            }
        }
    }
}

// Procesar subida de nuevos documentos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['nuevos_docs']['name'][0])) {
    $docs = $_FILES['nuevos_docs'];
    for ($i = 0; $i < count($docs['name']); $i++) {
        if ($docs['error'][$i] === UPLOAD_ERR_OK) {
            $f = [
                'name'     => $docs['name'][$i],
                'type'     => $docs['type'][$i],
                'tmp_name' => $docs['tmp_name'][$i],
                'error'    => $docs['error'][$i],
                'size'     => $docs['size'][$i]
            ];
            procesarArchivoVehiculo($f, $vehiculo_id, $conn);
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
            <td><a href="<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank">Ver / Descargar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>No hay documentos asociados a este vehículo.</p>
<?php endif; ?>

<h3>Subir nuevos documentos</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="nuevos_docs[]" multiple accept="image/*,application/pdf">
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


