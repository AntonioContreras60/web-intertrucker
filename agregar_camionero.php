<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /Perfil/inicio_sesion.php');
    exit();
}

// Ajusta estos si quieres ver errores en local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $nombre          = trim($_POST['nombre']);
    $apellidos       = trim($_POST['apellidos']);
    $dni             = trim($_POST['dni']);
    $tipo_carnet     = trim($_POST['tipo_carnet']);
    $num_licencia    = trim($_POST['num_licencia']);
    $fecha_caducidad = $_POST['fecha_caducidad'] ?? null;
    $email           = trim($_POST['email']);
    $activo          = isset($_POST['activo']) ? 1 : 0;
    $es_asociado     = isset($_POST['es_asociado']) ? 1 : 0;

    // El rol en `usuarios`: "camionero" o "asociado"
    $rol = $es_asociado ? 'asociado' : 'camionero';

    // ... (otros campos, fechas, etc. que quieras)

    // Obtenemos el admin_id del usuario actual
    $usuario_sesion_id = $_SESSION['usuario_id'];
    $sqlAdmin = "SELECT admin_id FROM usuarios WHERE id = ? LIMIT 1";
    $stmtA = $conn->prepare($sqlAdmin);
    $stmtA->bind_param("i", $usuario_sesion_id);
    $stmtA->execute();
    $stmtA->bind_result($admin_id);
    $stmtA->fetch();
    $stmtA->close();

    if (!$admin_id) {
        die("No se pudo determinar el admin_id para el usuario en sesión.");
    }

    // Crear el usuario en la tabla `usuarios`
    $contrasena_temporal = password_hash('cambiar123', PASSWORD_DEFAULT);
    $token_verificacion = bin2hex(random_bytes(32));
    $expiracion_token = date('Y-m-d H:i:s', strtotime('+1 day'));

    $sqlInsertUser = "
        INSERT INTO usuarios (email, contrasena, rol, admin_id, estado, token_verificacion, expiracion_token)
        VALUES (?, ?, ?, ?, 'activo', ?, ?)
    ";
    $stmtU = $conn->prepare($sqlInsertUser);
    $stmtU->bind_param("sssiss", 
        $email,
        $contrasena_temporal,
        $rol,
        $admin_id,
        $token_verificacion,
        $expiracion_token
    );
    if (!$stmtU->execute()) {
        die("Error al crear usuario: " . $stmtU->error);
    }
    $nuevo_usuario_id = $stmtU->insert_id;

    // Insertar en `camioneros`
    $sqlInsertCam = "
        INSERT INTO camioneros (
            usuario_id, 
            tipo_carnet,
            num_licencia,
            fecha_caducidad,
            activo
        ) VALUES (?, ?, ?, ?, ?)
    ";
    $stmtC = $conn->prepare($sqlInsertCam);
    $stmtC->bind_param("isssi",
        $nuevo_usuario_id,
        $tipo_carnet,
        $num_licencia,
        $fecha_caducidad,
        $activo
    );
    if (!$stmtC->execute()) {
        die("Error al guardar datos en camioneros: " . $stmtC->error);
    }
    $nuevo_camionero_id = $stmtC->insert_id;

    // CARPETA donde guardar los archivos
    $uploadDir = __DIR__ . '/uploads/camioneros/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Función para procesar y guardar un solo archivo
    function procesarArchivo($fileArray, $tipoDocumento, $camioneroId, $conn) {
        global $uploadDir;

        if ($fileArray['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $maxSize    = 20 * 1024 * 1024; // 20MB
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension  = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));

        if ($fileArray['size'] > $maxSize) {
            echo "<p style='color:red;'>El archivo excede el tamaño máximo de 20MB.</p>";
            return;
        }

        if (!in_array($extension, $allowedExt)) {
            echo "<p style='color:red;'>Formato de archivo no permitido.</p>";
            return;
        }

        $originalName = basename($fileArray['name']);
        $uniqueName   = uniqid('', true) . '.' . $extension;
        $destPath     = $uploadDir . $uniqueName;

        if (move_uploaded_file($fileArray['tmp_name'], $destPath)) {
            $rutaBD = 'uploads/camioneros/' . $uniqueName;
            $sqlDoc = "
                INSERT INTO documentos_camioneros (
                    camionero_id,
                    tipo_documento,
                    nombre_archivo,
                    ruta_archivo
                ) VALUES (?, ?, ?, ?)
            ";
            $stmtDoc = $conn->prepare($sqlDoc);
            if ($stmtDoc) {
                $stmtDoc->bind_param("isss",
                    $camioneroId,
                    $tipoDocumento,
                    $originalName,
                    $rutaBD
                );
                $stmtDoc->execute();
            }
        } else {
            echo "<p style='color:red;'>Error subiendo archivo ($tipoDocumento): " . htmlspecialchars($originalName) . "</p>";
        }
    }

    // 1) Subir archivo del DNI
    if (isset($_FILES['documentoDNI']) && $_FILES['documentoDNI']['error'] !== UPLOAD_ERR_NO_FILE) {
        procesarArchivo($_FILES['documentoDNI'], 'dni', $nuevo_camionero_id, $conn);
    }

    // 2) Subir archivo del Carnet de Conducir
    if (isset($_FILES['documentoCarnet']) && $_FILES['documentoCarnet']['error'] !== UPLOAD_ERR_NO_FILE) {
        procesarArchivo($_FILES['documentoCarnet'], 'carnet_conducir', $nuevo_camionero_id, $conn);
    }

    // 3) Subir archivo de Competencia Profesional
    if (isset($_FILES['documentoCompetencia']) && $_FILES['documentoCompetencia']['error'] !== UPLOAD_ERR_NO_FILE) {
        procesarArchivo($_FILES['documentoCompetencia'], 'competencia_profesional', $nuevo_camionero_id, $conn);
    }

    // 4) Subir archivos adicionales (múltiples)
    if (!empty($_FILES['documentosExtras']['name'][0])) {
        // Significa que al menos un archivo fue seleccionado
        $extras = $_FILES['documentosExtras'];

        for ($i = 0; $i < count($extras['name']); $i++) {
            if ($extras['error'][$i] === UPLOAD_ERR_OK) {
                $fileArray = [
                    'name' => $extras['name'][$i],
                    'type' => $extras['type'][$i],
                    'tmp_name' => $extras['tmp_name'][$i],
                    'error' => $extras['error'][$i],
                    'size' => $extras['size'][$i],
                ];
                // En documentos_camioneros, lo ponemos con tipo_documento = 'otros'
                procesarArchivo($fileArray, 'otros', $nuevo_camionero_id, $conn);
            }
        }
    }

    // (Opcional) Enviar email invitación, etc.

    header("Location: my_truckers.php?message=Camionero+guardado+correctamente");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Alta de Camionero</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    form { background: #f7f7f7; padding: 20px; border: 1px solid #ccc; border-radius: 8px; max-width: 700px; }
    label { font-weight: bold; }
    input[type="text"], input[type="date"], input[type="email"] {
      display: block; margin-bottom: 10px; padding: 6px; width: 300px;
    }
    .checkbox-inline {
      display: inline-block;
      margin-right: 15px;
      margin-bottom: 10px;
    }
    .file-section {
      margin-bottom: 20px;
    }
    button {
      background-color: #007bff; color: #fff; border: none; padding: 10px 16px;
      border-radius: 4px; cursor: pointer;
    }
    button:hover { background-color: #0056b3; }
    h1 { margin-bottom: 10px; }
    h2 { margin-top: 30px; }
  </style>
</head>
<body>
<h1>Alta de Camionero</h1>

<form method="POST" enctype="multipart/form-data">
  <h2>Datos Personales</h2>
  <label>Nombre:</label>
  <input type="text" name="nombre" required>

  <label>Apellidos:</label>
  <input type="text" name="apellidos" required>

  <label>DNI:</label>
  <input type="text" name="dni" required>

  <label>Email:</label>
  <input type="email" name="email" required>

  <div class="checkbox-inline">
    <input type="checkbox" name="activo" value="1" checked> Activo
  </div>
  <div class="checkbox-inline">
    <input type="checkbox" name="es_asociado" value="1"> Es Asociado (Autónomo)
  </div>

  <h2>Datos de Conducción</h2>
  <label>Tipo Carnet:</label>
  <input type="text" name="tipo_carnet" placeholder="Ej: C+E" required>

  <label>Número de Licencia:</label>
  <input type="text" name="num_licencia" placeholder="Ej: 12345-XYZ">

  <label>Fecha de Caducidad Carnet:</label>
  <input type="date" name="fecha_caducidad">

  <!-- Más campos si lo deseas (fecha_nacimiento, fecha_contratacion, etc.) -->

  <h2>Documentos obligatorios</h2>
  <div class="file-section">
    <label>Archivo DNI:</label>
    <input type="file" name="documentoDNI" accept="image/*,application/pdf">
  </div>

  <div class="file-section">
    <label>Archivo Carnet de Conducir:</label>
    <input type="file" name="documentoCarnet" accept="image/*,application/pdf">
  </div>

  <div class="file-section">
    <label>Archivo Competencia Profesional:</label>
    <input type="file" name="documentoCompetencia" accept="image/*,application/pdf">
  </div>

  <h2>Otros Documentos (opcionales)</h2>
  <label>Puedes subir múltiples archivos (PDF, imágenes, etc.):</label><br>
  <input type="file" name="documentosExtras[]" multiple accept="image/*,application/pdf">

  <br><br>
  <button type="submit">Guardar Camionero</button>
</form>

</body>
</html>
