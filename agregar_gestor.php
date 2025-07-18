<?php
/*****************************************************************
 * agregar_colaborador.php   (gestores empleados) – v2.1
 * - Crea gestor (y opcionalmente camionero)
 * - Envía e-mail de invitación con token de verificación
 * - Permite subir documentos (DNI, Contrato, Otros)
 *****************************************************************/

session_start();
require_once __DIR__.'/conexion.php';   // conexión BD

/*────────────────────── 1. Control de acceso ─────────────────────*/
if (!isset($_SESSION['rol']) || $_SESSION['rol']!=='administrador' || !empty($_SESSION['es_gestor'])) {
    die("<p style='font-size:3rem;font-weight:bold'>No tiene permiso para acceder a esta sección</p>");
}

/*────────────────────── 2. Procesar formulario ───────────────────*/
if ($_SERVER['REQUEST_METHOD']==='POST') {

  // 2.1 Datos del POST
  $nombre   = trim($_POST['nombre']);
  $apellidos= trim($_POST['apellidos']);
  $dni      = trim($_POST['dni']);
  $email    = trim($_POST['email']);
  $titula   = trim($_POST['titulacion_gestor']);
  $esCam    = isset($_POST['es_camionero']) ? 1 : 0;

  // 2.2 Verificar que el e-mail no exista ya
  $dup = $conn->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
  $dup->bind_param("s",$email); $dup->execute(); $dup->store_result();
  if($dup->num_rows){
      die("Error: este e-mail ya está registrado en InterTrucker.");
  }

  // 2.3 Crear usuario
  $admin_id  = $_SESSION['usuario_id'];            // id del admin creador
  $token     = bin2hex(random_bytes(32));
  $expira    = date('Y-m-d H:i:s',strtotime('+48 hours'));
  $pwdHash   = password_hash('cambiar123',PASSWORD_DEFAULT);
  $rol       = $esCam ? 'gestor_camionero' : 'gestor';

  $ins = $conn->prepare("INSERT INTO usuarios
         (nombre_usuario,apellidos,email,dni,rol,admin_id,estado,titulacion_gestor,
          token_verificacion,expiracion_token,contrasena)
         VALUES (?,?,?,? ,? ,? ,'activo',? ,? ,?,?)");
  $ins->bind_param("sssssisiss",
        $nombre,$apellidos,$email,$dni,
        $rol,$admin_id,$titula,
        $token,$expira,$pwdHash);
  if(!$ins->execute()) die("Error BD: ".$ins->error);
  $usuario_id = $ins->insert_id;

  // 2.4 Si también es camionero ► ficha mínima
  if($esCam){
      $conn->query("INSERT INTO camioneros (usuario_id,activo) VALUES ($usuario_id,1)");
  }

  /*──────────────── 2.5 Subir documentos opcionales ───────────────*/
  $uploadDir = __DIR__.'/uploads/usuarios/';
  if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

  function saveDoc($fileArr,$tipo,$uid,$dir,$conn){
       if($fileArr['error']!==UPLOAD_ERR_OK) return;

       $maxSize  = 20 * 1024 * 1024; // 20MB
       $allowed  = ['pdf','jpg','jpeg','png'];
       $mimeAllowed = ['application/pdf','image/jpeg','image/png'];
       $ext      = strtolower(pathinfo($fileArr['name'],PATHINFO_EXTENSION));
       $finfo    = new finfo(FILEINFO_MIME_TYPE);
       $mime     = $finfo->file($fileArr['tmp_name']);

       if($fileArr['size'] > $maxSize){
          echo "<p style='color:red;'>El archivo excede el tamaño máximo de 20MB.</p>";
          return;
       }
       if(!in_array($ext,$allowed) || !in_array($mime,$mimeAllowed)){
          echo "<p style='color:red;'>Formato de archivo no permitido.</p>";
          return;
       }

       $orig = basename($fileArr['name']);
       $dest = $dir . uniqid('', true) . '.' . $ext;
       if(move_uploaded_file($fileArr['tmp_name'],$dest)){
          $ruta = 'uploads/usuarios/'.basename($dest);
          $q=$conn->prepare("INSERT INTO documentos_usuarios (usuario_id,tipo_documento,nombre_archivo,ruta_archivo) VALUES (?,?,?,?)");
          $q->bind_param("isss",$uid,$tipo,$orig,$ruta);
          $q->execute();
       }else{
          echo "<p style='color:red;'>Error subiendo archivo ($tipo).</p>";
       }
  }

  // DNI
  if(!empty($_FILES['doc_dni']['name']))      saveDoc($_FILES['doc_dni'],'dni',$usuario_id,$uploadDir,$conn);
  // Contrato
  if(!empty($_FILES['doc_contrato']['name'])) saveDoc($_FILES['doc_contrato'],'contrato',$usuario_id,$uploadDir,$conn);
  // Otros múltiples
  if(!empty($_FILES['doc_otros']['name'][0])){
        foreach($_FILES['doc_otros']['name'] as $k=>$n){
           $tmp=[
             'name'=>$_FILES['doc_otros']['name'][$k],
             'type'=>$_FILES['doc_otros']['type'][$k],
             'tmp_name'=>$_FILES['doc_otros']['tmp_name'][$k],
             'error'=>$_FILES['doc_otros']['error'][$k],
             'size'=>$_FILES['doc_otros']['size'][$k],
           ];
           saveDoc($tmp,'otros',$usuario_id,$uploadDir,$conn);
        }
  }

  /*──────────────── 2.6 Enviar e-mail de invitación ───────────────*/
  $enlace = "https://intertrucker.net/registro_gestor.php?token=".$token;
  $asunto = "Completa tu registro como Gestor en InterTrucker";
  $mensaje = "Hola $nombre,\n\nHas sido dado de alta como gestor en InterTrucker.";
  if($esCam){ $mensaje.=" Además se ha activado tu perfil de camionero."; }
  $mensaje.="\n\nTitulado como: $titula\n\nPara activar tu cuenta y elegir contraseña, entra aquí:\n$enlace\n\nEl enlace caduca en 48 horas.";
  $headers="From: no-reply@intertrucker.net";

  // Usa la función mail (o tu librería SMTP)
  mail($email,$asunto,$mensaje,$headers);

  header("Location: gestinar_colaboradores.php?msg=Gestor+creado+correctamente");
  exit();
}
?>

<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8"><title>Añadir Gestor</title>
<style>
body{font-family:Arial;margin:20px}
label{display:block;margin-top:8px;font-weight:bold}
input,select{padding:6px;margin-top:2px;width:280px}
button{background:#007bff;color:#fff;border:none;padding:10px 18px;border-radius:4px;cursor:pointer;margin-top:14px}
button:hover{background:#0056b3}
section{background:#f7f7f7;padding:14px;border:1px solid #ccc;border-radius:8px;max-width:500px}
</style>
</head>
<body>
<h1>Agregar Gestor</h1>

<form method="POST" enctype="multipart/form-data">
<section>
  <label>Nombre:<br><input name="nombre" required></label>
  <label>Apellidos:<br><input name="apellidos" required></label>
  <label>DNI:<br><input name="dni" required></label>
  <label>Email:<br><input type="email" name="email" required></label>
  <label>Titulación como Gestor:<br><input name="titulacion_gestor" required></label>
  <label><input type="checkbox" name="es_camionero"> ¿También será camionero?</label>
</section>

<section>
  <h3>Subir documentos (opcional)</h3>
  DNI: <input type="file" name="doc_dni" accept=".pdf,.jpg,.jpeg,.png"><br>
  Contrato laboral: <input type="file" name="doc_contrato" accept=".pdf,.jpg,.jpeg,.png"><br>
  Otros archivos (múltiples): <input type="file" name="doc_otros[]" multiple accept=".pdf,.jpg,.jpeg,.png">
</section>

<button type="submit">Añadir Gestor y Enviar Enlace</button>
</form>

</body></html>
