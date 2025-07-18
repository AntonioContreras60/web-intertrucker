<?php
require_once __DIR__.'/auth.php';
require_login();
include 'conexion.php'; // Ajusta la ruta a tu archivo de conexión

// ======================================================
// CSRF: Generar token si no existe
// ======================================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$usuario_sesion_id = $_SESSION['usuario_id'];

// 1) Verificar que llega param "id" => ID en `camioneros`
if (!isset($_GET['id'])) {
    die("Falta el ID del camionero (param 'id').");
}
$camionero_id = (int)$_GET['id'];

// 2) Cargar datos del camionero y de su usuario
$sql = "
    SELECT 
        c.id AS camionero_id,
        c.tipo_carnet,
        c.num_licencia,
        c.fecha_caducidad,
        c.fecha_nacimiento,
        c.fecha_contratacion,
        c.activo,
        u.id AS usuario_id,
        u.email,
        u.telefono,
        u.nombre_usuario,
        u.apellidos,
        u.rol,
        u.estado
    FROM camioneros c
    JOIN usuarios u ON u.id = c.usuario_id
    WHERE c.id = ?
      AND u.admin_id = (
          SELECT admin_id FROM usuarios WHERE id = ?
      )
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $camionero_id, $usuario_sesion_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows < 1) {
    die("No se encontró el camionero con ID=$camionero_id o no tienes permisos.");
}
$camionero = $result->fetch_assoc();

// 3) Cargar documentos existentes (si aplica)
$sqlDocs = "
    SELECT id, tipo_documento, nombre_archivo, ruta_archivo, fecha_subida
    FROM documentos_camioneros
    WHERE camionero_id = ?
    ORDER BY fecha_subida DESC
";
$stmtDocs = $conn->prepare($sqlDocs);
$stmtDocs->bind_param("i", $camionero_id);
$stmtDocs->execute();
$resDocs = $stmtDocs->get_result();
$documentos = [];
while ($row = $resDocs->fetch_assoc()) {
    $documentos[] = $row;
}

// ======================================================
// PROCESAR FORMULARIO (si llega por POST)
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF: Verificar token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: Token CSRF inválido o ausente.");
    }

    // a) Recoger campos de `usuarios`
    $nombre_usuario    = trim($_POST['nombre_usuario'] ?? '');
    $apellidos         = trim($_POST['apellidos'] ?? '');
    $telefono          = trim($_POST['telefono'] ?? '');

    // b) Recoger campos de `camioneros`
    $tipo_carnet       = trim($_POST['tipo_carnet'] ?? '');
    $num_licencia      = trim($_POST['num_licencia'] ?? '');
    $fecha_caducidad   = $_POST['fecha_caducidad']   ?? null;
    $fecha_nacimiento  = $_POST['fecha_nacimiento']  ?? null;
    $fecha_contratacion= $_POST['fecha_contratacion']?? null;

    // c) Activo => si se marcó checkbox => 1, sino 0
    $activo = isset($_POST['activo']) ? 1 : 0;

    // d) Actualizar tabla `usuarios`
    $estadoUsuario = $activo ? 'activo' : 'inactivo';
    $sqlUpdateUser = "
        UPDATE usuarios
        SET nombre_usuario = ?,
            apellidos = ?,
            telefono = ?,
            estado = ?
        WHERE id = ?
        LIMIT 1
    ";
    $stmtU = $conn->prepare($sqlUpdateUser);
    $stmtU->bind_param(
        "ssssi",
        $nombre_usuario,
        $apellidos,
        $telefono,
        $estadoUsuario,
        $camionero['usuario_id']
    );
    $okUser = $stmtU->execute();
    $stmtU->close();

    // e) Actualizar tabla `camioneros`
    $sqlUpdateCam = "
        UPDATE camioneros
        SET tipo_carnet = ?,
            num_licencia = ?,
            fecha_caducidad = ?,
            fecha_nacimiento = ?,
            fecha_contratacion = ?,
            activo = ?
        WHERE id = ?
        LIMIT 1
    ";
    $stmtC = $conn->prepare($sqlUpdateCam);
    $stmtC->bind_param(
        "ssssiii",
        $tipo_carnet,
        $num_licencia,
        $fecha_caducidad,
        $fecha_nacimiento,
        $fecha_contratacion,
        $activo,
        $camionero_id
    );
    $okCam = $stmtC->execute();
    $stmtC->close();

    // f) Procesar subida de nuevos documentos
    $uploadDir = __DIR__ . '/uploads/camioneros/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    function subirDocumento($fileData, $tipoDocumento, $camioneroId, $conn) {
        global $uploadDir;

        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $maxSize    = 20 * 1024 * 1024; // 20MB
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
        $mimeAllowed = ['application/pdf','image/jpeg','image/png'];
        $extension  = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        $finfo      = new finfo(FILEINFO_MIME_TYPE);
        $mime       = $finfo->file($fileData['tmp_name']);

        if ($fileData['size'] > $maxSize) {
            echo "<p style='color:red;'>El archivo excede el tamaño máximo de 20MB.</p>";
            return;
        }

        if (!in_array($extension, $allowedExt) || !in_array($mime, $mimeAllowed)) {
            echo "<p style='color:red;'>Formato de archivo no permitido.</p>";
            return;
        }

        $originalName = basename($fileData['name']);
        $uniqueName   = uniqid('', true) . '.' . $extension;
        $destPath     = $uploadDir . $uniqueName;

        if (move_uploaded_file($fileData['tmp_name'], $destPath)) {
            $rutaBD = 'uploads/camioneros/' . $uniqueName;
            $insDoc = "
                INSERT INTO documentos_camioneros (
                    camionero_id,
                    tipo_documento,
                    nombre_archivo,
                    ruta_archivo
                ) VALUES (?, ?, ?, ?)
            ";
            $stmtDoc = $conn->prepare($insDoc);
            $stmtDoc->bind_param(
                "isss",
                $camioneroId,
                $tipoDocumento,
                $originalName,
                $rutaBD
            );
            $stmtDoc->execute();
            $stmtDoc->close();
        } else {
            echo "<p style='color:red;'>Error subiendo archivo ($tipoDocumento).</p>";
        }
    }

    // g) Subida de DNI
    if (isset($_FILES['documentoDNI']) && $_FILES['documentoDNI']['error'] !== UPLOAD_ERR_NO_FILE) {
        subirDocumento($_FILES['documentoDNI'], 'dni', $camionero_id, $conn);
    }
    // h) Carnet
    if (isset($_FILES['documentoCarnet']) && $_FILES['documentoCarnet']['error'] !== UPLOAD_ERR_NO_FILE) {
        subirDocumento($_FILES['documentoCarnet'], 'carnet_conducir', $camionero_id, $conn);
    }
    // i) Competencia
    if (isset($_FILES['documentoCompetencia']) && $_FILES['documentoCompetencia']['error'] !== UPLOAD_ERR_NO_FILE) {
        subirDocumento($_FILES['documentoCompetencia'], 'competencia_profesional', $camionero_id, $conn);
    }
    // j) Extras
    if (!empty($_FILES['documentosExtras']['name'][0])) {
        $extras = $_FILES['documentosExtras'];
        for ($i = 0; $i < count($extras['name']); $i++) {
            if ($extras['error'][$i] === UPLOAD_ERR_OK) {
                $fData = [
                    'name'     => $extras['name'][$i],
                    'type'     => $extras['type'][$i],
                    'tmp_name' => $extras['tmp_name'][$i],
                    'error'    => $extras['error'][$i],
                    'size'     => $extras['size'][$i],
                ];
                subirDocumento($fData, 'otros', $camionero_id, $conn);
            }
        }
    }

    // k) Verificar actualizaciones
    if ($okUser && $okCam) {
        echo "<p style='color:green;'>Datos actualizados correctamente.</p>";
    } else {
        echo "<p style='color:red;'>Error al actualizar los datos.</p>";
    }

    // l) Recargar la info de documentos (por si se subieron nuevos)
    $sqlDocs2 = "
        SELECT id, tipo_documento, nombre_archivo, ruta_archivo, fecha_subida
        FROM documentos_camioneros
        WHERE camionero_id = ?
        ORDER BY fecha_subida DESC
    ";
    $stmtD2 = $conn->prepare($sqlDocs2);
    $stmtD2->bind_param("i", $camionero_id);
    $stmtD2->execute();
    $resDocs2 = $stmtD2->get_result();
    $documentos = [];
    while ($rw = $resDocs2->fetch_assoc()) {
        $documentos[] = $rw;
    }
    $stmtD2->close();
}

// Cerramos aquí, o lo dejas para final
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Camionero</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    form {
      background: #f7f7f7;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      max-width: 700px;
    }
    label { font-weight: bold; }
    input[type="text"], input[type="date"], input[type="email"] {
      display: block;
      width: 300px;
      margin-bottom: 10px;
      padding: 6px;
    }
    .checkbox-inline { display: inline-block; margin-right: 15px; }
    table { border-collapse: collapse; margin-top: 20px; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 6px; }
    .file-section { margin-bottom: 15px; }
    button {
        background-color: #007bff; color: #fff;
        border: none; padding: 10px 16px; border-radius: 4px; cursor: pointer;
    }
    button:hover { background-color: #0056b3; }
  </style>
</head>
<body>

<h1>Editar Camionero / Asociado</h1>

<!-- Muestra el ID del camionero y su email -->
<p><strong>ID Camionero:</strong> <?= htmlspecialchars($camionero['camionero_id']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($camionero['email']) ?></p>

<!-- Formulario para editar datos -->
<form method="POST" enctype="multipart/form-data">
  <!-- CSRF: token hidden -->
  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

  <!-- Campos de 'usuarios' -->
  <label>Nombre:</label>
  <input type="text" name="nombre_usuario" 
         value="<?= htmlspecialchars($camionero['nombre_usuario']) ?>" required>

  <label>Apellidos:</label>
  <input type="text" name="apellidos" 
         value="<?= htmlspecialchars($camionero['apellidos']) ?>" required>

  <label>Teléfono:</label>
  <input type="text" name="telefono" 
         value="<?= htmlspecialchars($camionero['telefono'] ?? '') ?>">

  <!-- Campos de 'camioneros' -->
  <label>Tipo Carnet:</label>
  <input type="text" name="tipo_carnet" 
         value="<?= htmlspecialchars($camionero['tipo_carnet']) ?>">

  <label>Número de Licencia:</label>
  <input type="text" name="num_licencia" 
         value="<?= htmlspecialchars($camionero['num_licencia']) ?>">

  <label>Fecha de Caducidad Carnet:</label>
  <input type="date" name="fecha_caducidad"
         value="<?= htmlspecialchars($camionero['fecha_caducidad'] ?? '') ?>">

  <label>Fecha de Nacimiento:</label>
  <input type="date" name="fecha_nacimiento"
         value="<?= htmlspecialchars($camionero['fecha_nacimiento'] ?? '') ?>">

  <label>Fecha de Contratación:</label>
  <input type="date" name="fecha_contratacion"
         value="<?= htmlspecialchars($camionero['fecha_contratacion'] ?? '') ?>">

  <div class="checkbox-inline">
    <input type="checkbox" name="activo" <?= $camionero['activo'] ? 'checked' : '' ?>> Activo
  </div>

  <br><br>
  <h3>Actualizar Documentos (opcional)</h3>
  <label>Nuevo DNI:</label>
  <input type="file" name="documentoDNI" accept=".pdf,.jpg,.jpeg,.png"><br>

  <label>Nuevo Carnet de Conducir:</label>
  <input type="file" name="documentoCarnet" accept=".pdf,.jpg,.jpeg,.png"><br>

  <label>Nuevo Competencia Profesional:</label>
  <input type="file" name="documentoCompetencia" accept=".pdf,.jpg,.jpeg,.png"><br>

  <label>Archivos Extra (múltiple):</label>
  <input type="file" name="documentosExtras[]" multiple accept=".pdf,.jpg,.jpeg,.png"><br><br>

  <button type="submit">Guardar Cambios</button>
</form>

<hr>
<h2>Documentos Existentes</h2>
<?php if (!empty($documentos)): ?>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Tipo</th>
        <th>Archivo</th>
        <th>Fecha Subida</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($documentos as $doc): ?>
        <tr>
          <td><?= $doc['id'] ?></td>
          <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
          <td><?= htmlspecialchars($doc['nombre_archivo']) ?></td>
          <td><?= htmlspecialchars($doc['fecha_subida']) ?></td>
          <td>
            <a href="descargar_documento.php?id=<?= $doc['id'] ?>&tipo=camionero" target="_blank">Ver</a>
            <!-- Aquí podrías añadir un botón para eliminar el documento si quieres -->
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No hay documentos subidos para este camionero.</p>
<?php endif; ?>

<p>
  <button type="button" onclick="window.location.href='my_truckers.php'">Volver atrás</button>
</p>

</body>
</html>
