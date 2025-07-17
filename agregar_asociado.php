<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(["administrador","gestor"]);
include 'conexion.php'; // Conexión a la base de datos

// Para ver errores en modo desarrollo (opcional)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Recoger datos del formulario
    $nombre               = trim($_POST['nombre']);        // usuarios.nombre_usuario
    $apellidos            = trim($_POST['apellidos']);     // usuarios.apellidos
    $telefono             = trim($_POST['telefono']);      // usuarios.telefono
    $email                = trim($_POST['email']);         // usuarios.email
    $dni                  = trim($_POST['dni']);           // (si lo guardas en 'camioneros' o no, depende de tu DB)
    $tipo_carnet          = trim($_POST['tipo_carnet']);
    $num_licencia         = trim($_POST['num_licencia']);
    $fecha_caducidad      = $_POST['fecha_caducidad'] ?? null;
    $fecha_nacimiento     = $_POST['fecha_nacimiento'] ?? null;      // si lo guardas en 'camioneros'
    $fecha_contratacion   = $_POST['fecha_contratacion'] ?? null;    // si lo guardas en 'camioneros'
    $activo               = isset($_POST['activo']) ? 1 : 0;

    // El rol siempre 'asociado'
    $rol = 'asociado';

    // 2) Obtener el admin_id del usuario en sesión
    $usuario_sesion_id = $_SESSION['usuario_id'];
    $admin_id_query = "SELECT admin_id FROM usuarios WHERE id = ? LIMIT 1";
    $stmt_admin_id = $conn->prepare($admin_id_query);
    $stmt_admin_id->bind_param("i", $usuario_sesion_id);
    $stmt_admin_id->execute();
    $stmt_admin_id->bind_result($admin_id);
    $stmt_admin_id->fetch();
    $stmt_admin_id->close();

    if (!$admin_id) {
        die("Error: No se pudo determinar el admin_id para el usuario en sesión.");
    }

    // 3) Crear el usuario en `usuarios` con rol='asociado'
    $contrasena_temporal   = password_hash('cambiar123', PASSWORD_DEFAULT);
    $token_verificacion    = bin2hex(random_bytes(32));
    $fecha_expiracion_token= date('Y-m-d H:i:s', strtotime('+1 day'));

    $sqlInsertUser = "
        INSERT INTO usuarios (
            email,
            contrasena,
            rol,
            admin_id,
            estado,
            token_verificacion,
            expiracion_token,
            telefono,
            nombre_usuario,
            apellidos
        ) 
        VALUES (?, ?, ?, ?, 'activo', ?, ?, ?, ?, ?)
    ";
    $stmt_usuario = $conn->prepare($sqlInsertUser);
    if (!$stmt_usuario) {
        die("Error al preparar la inserción en usuarios: " . $conn->error);
    }

    $stmt_usuario->bind_param(
        "sssisssss",
        $email,
        $contrasena_temporal,
        $rol,
        $admin_id,
        $token_verificacion,
        $fecha_expiracion_token,
        $telefono,
        $nombre,
        $apellidos
    );

    if (!$stmt_usuario->execute()) {
        die("Error al insertar en usuarios: " . $stmt_usuario->error);
    }
    $nuevo_usuario_id = $stmt_usuario->insert_id;

    // 4) Insertar datos en la tabla `camioneros`
    //    Ajusta si tienes/quieres campos extras como fecha_nacimiento, fecha_contratacion, etc.
    $sqlInsertCam = "
        INSERT INTO camioneros (
            usuario_id,
            tipo_carnet,
            num_licencia,
            fecha_caducidad,
            fecha_nacimiento,
            fecha_contratacion,
            activo
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt_camionero = $conn->prepare($sqlInsertCam);
    if (!$stmt_camionero) {
        die("Error al preparar la inserción en camioneros: " . $conn->error);
    }
    $stmt_camionero->bind_param(
        "isssssi",
        $nuevo_usuario_id,
        $tipo_carnet,
        $num_licencia,
        $fecha_caducidad,
        $fecha_nacimiento,
        $fecha_contratacion,
        $activo
    );

    if (!$stmt_camionero->execute()) {
        die("Error al insertar en camioneros: " . $stmt_camionero->error);
    }
    $nuevo_camionero_id = $stmt_camionero->insert_id;

    // 5) Subir archivos / documentos (DNI, Carnet, Competencia + extras)
    $uploadDir = __DIR__ . '/uploads/camioneros/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Función para procesar el archivo y guardarlo en `documentos_camioneros`
    function procesarArchivo($fileArray, $tipoDocumento, $camioneroId, $conn) {
        global $uploadDir;

        if ($fileArray['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $maxSize      = 20 * 1024 * 1024; // 20MB
        $allowedExt   = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension    = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));

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
                )
                VALUES (?, ?, ?, ?)
            ";
            $stmtDoc = $conn->prepare($sqlDoc);
            if ($stmtDoc) {
                $stmtDoc->bind_param(
                    "isss",
                    $camioneroId,
                    $tipoDocumento,
                    $originalName,
                    $rutaBD
                );
                $stmtDoc->execute();
            }
        } else {
            echo "<p style='color:red;'>Error subiendo archivo ($tipoDocumento): "
                 . htmlspecialchars($originalName) . "</p>";
        }
    }

    // Subir DNI
    if (isset($_FILES['documentoDNI']) && $_FILES['documentoDNI']['error'] !== UPLOAD_ERR_NO_FILE) {
        procesarArchivo($_FILES['documentoDNI'], 'dni', $nuevo_camionero_id, $conn);
    }
    // Subir Carnet
    if (isset($_FILES['documentoCarnet']) && $_FILES['documentoCarnet']['error'] !== UPLOAD_ERR_NO_FILE) {
        procesarArchivo($_FILES['documentoCarnet'], 'carnet_conducir', $nuevo_camionero_id, $conn);
    }
    // Subir Competencia
    if (isset($_FILES['documentoCompetencia']) && $_FILES['documentoCompetencia']['error'] !== UPLOAD_ERR_NO_FILE) {
        procesarArchivo($_FILES['documentoCompetencia'], 'competencia_profesional', $nuevo_camionero_id, $conn);
    }
    // Subir extras (múltiples)
    if (!empty($_FILES['documentosExtras']['name'][0])) {
        $extras = $_FILES['documentosExtras'];
        for ($i = 0; $i < count($extras['name']); $i++) {
            if ($extras['error'][$i] === UPLOAD_ERR_OK) {
                $fileArray = [
                    'name'     => $extras['name'][$i],
                    'type'     => $extras['type'][$i],
                    'tmp_name' => $extras['tmp_name'][$i],
                    'error'    => $extras['error'][$i],
                    'size'     => $extras['size'][$i]
                ];
                procesarArchivo($fileArray, 'otros', $nuevo_camionero_id, $conn);
            }
        }
    }

    // 6) (Opcional) Enviar mail de verificación al email del asociado
    /*
    $enlace_verificacion = "https://tu-dominio.com/verificar.php?token=" . $token_verificacion;
    $asunto  = "Completa tu registro como Asociado";
    $mensaje = "Hola $nombre,\n\nHaz clic en el siguiente enlace para activar tu cuenta:\n$enlace_verificacion\n\nEste enlace expira en 24 horas.";
    $cabeceras = "From: no-reply@tu-dominio.com\r\nContent-Type: text/plain; charset=utf-8";
    mail($email, $asunto, $mensaje, $cabeceras);
    */

    // 7) Redirigir a la lista de asociados
    header("Location: mis_asociados.php?msg=Asociado+creado+correctamente");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Agregar Asociado</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    form {
      background: #f9f9f9;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      max-width: 700px;
    }
    label { font-weight: bold; }
    input[type="text"],
    input[type="date"],
    input[type="email"] {
      display: block;
      margin-bottom: 10px;
      padding: 6px;
      width: 300px;
    }
    .file-section { margin-bottom: 15px; }
    .checkbox-inline { display:inline-block; margin-right:15px; }
    button {
      background-color: #007bff; color: #fff; border:none; padding:10px 16px; border-radius:4px; cursor:pointer;
    }
    button:hover { background-color:#0056b3; }
  </style>
</head>
<body>
<h1>Alta de Asociado</h1>

<form method="POST" enctype="multipart/form-data">
  <label>Nombre:</label>
  <input type="text" name="nombre" required>

  <label>Apellidos:</label>
  <input type="text" name="apellidos" required>

  <label>Teléfono:</label>
  <input type="text" name="telefono">

  <label>Email:</label>
  <input type="email" name="email" required>

  <label>DNI:</label>
  <input type="text" name="dni">

  <label>Tipo Carnet:</label>
  <input type="text" name="tipo_carnet" required>

  <label>Número de Licencia:</label>
  <input type="text" name="num_licencia">

  <label>Fecha de Caducidad del Carnet:</label>
  <input type="date" name="fecha_caducidad">

  <label>Fecha de Caducidad de C. Profesional:</label>
  <input type="date" name="caducidad_profesional">

  <label>Fecha de Nacimiento:</label>
  <input type="date" name="fecha_nacimiento">

  <label>Fecha de Contratación:</label>
  <input type="date" name="fecha_contratacion">

  <div class="checkbox-inline">
    <input type="checkbox" name="activo" value="1" checked> Activo
  </div>

  <h2>Documentos obligatorios</h2>
  <label>Archivo DNI:</label>
  <input type="file" name="documentoDNI" accept="image/*,application/pdf"><br>

  <label>Archivo Carnet de Conducir:</label>
  <input type="file" name="documentoCarnet" accept="image/*,application/pdf"><br>

  <label>Archivo Competencia Profesional:</label>
  <input type="file" name="documentoCompetencia" accept="image/*,application/pdf"><br><br>

  <h2>Otros Documentos (opcionales)</h2>
  <input type="file" name="documentosExtras[]" multiple accept="image/*,application/pdf"><br><br>

  <button type="submit">Guardar Asociado</button>
</form>
</body>
</html>
