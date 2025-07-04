<?php
/*****************************************************************
 * detalles_colaborador.php   (v2.0 – con gestión de documentos)
 *****************************************************************/
session_start();
require_once __DIR__.'/conexion.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol']) || $_SESSION['rol']!=='administrador'){
   die("<p style='font-size:3rem;font-weight:bold'>No tiene permiso para acceder a esta sección</p>");
}

/*────────────────────── 1. Validar ID ──────────────────────*/
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("ID de colaborador inválido.");
$colab_id = intval($_GET['id']);

/*────────────────────── 2. Obtener datos ───────────────────*/
$q = $conn->prepare("SELECT * FROM usuarios WHERE id=? LIMIT 1");
$q->bind_param("i",$colab_id); $q->execute();
$colaborador = $q->get_result()->fetch_assoc();
if(!$colaborador) die("Colaborador no encontrado.");

$qCam = $conn->prepare("SELECT id FROM camioneros WHERE usuario_id=? LIMIT 1");
$qCam->bind_param("i",$colab_id); $qCam->execute();
$es_camionero = $qCam->get_result()->num_rows ? true : false;

/*──────────────── 3. Procesar subida/borra de documentos ───────────────*/
$uploadDir = __DIR__.'/uploads/usuarios/';
if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

/*– Subir documento –*/
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['subir_doc'])){
   if(isset($_FILES['archivo']) && $_FILES['archivo']['error']===UPLOAD_ERR_OK){
       $tipoDoc = $_POST['tipo_documento'];
       $tmp     = $_FILES['archivo']['tmp_name'];
       $orig    = basename($_FILES['archivo']['name']);
       $dest    = $uploadDir.time().'_'.$orig;
       if(move_uploaded_file($tmp,$dest)){
          $rutaBD = 'uploads/usuarios/'.basename($dest);
          $in  = $conn->prepare("INSERT INTO documentos_usuarios(usuario_id,tipo_documento,nombre_archivo,ruta_archivo) VALUES (?,?,?,?)");
          $in->bind_param("isss",$colab_id,$tipoDoc,$orig,$rutaBD);
          $in->execute();
       }
   }
   header("Location: detalles_colaborador.php?id=".$colab_id); exit();
}

/*– Eliminar documento –*/
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['eliminar_doc'])){
   $docId = intval($_POST['doc_id']);
   $sel   = $conn->prepare("SELECT ruta_archivo FROM documentos_usuarios WHERE id=? AND usuario_id=?");
   $sel->bind_param("ii",$docId,$colab_id); $sel->execute();
   $row = $sel->get_result()->fetch_assoc();
   if($row){
      @unlink(__DIR__.'/'.$row['ruta_archivo']);
      $conn->query("DELETE FROM documentos_usuarios WHERE id=".$docId);
   }
   header("Location: detalles_colaborador.php?id=".$colab_id); exit();
}

/*──────────────── 4. Documentos existentes ───────────────*/
$docs = [];
$dq = $conn->prepare("SELECT id,tipo_documento,nombre_archivo,fecha_subida,ruta_archivo FROM documentos_usuarios WHERE usuario_id=?");
$dq->bind_param("i",$colab_id); $dq->execute();
$docs = $dq->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><title>Detalles del Gestor</title>
<style>
body{font-family:Arial,sans-serif;margin:20px}
h1,h2{color:#333}
label{display:block;margin-bottom:4px;font-weight:bold}
input,select{margin-bottom:10px;padding:6px}
button{background:#007bff;color:#fff;border:none;padding:8px 14px;border-radius:4px;cursor:pointer}
button:hover{background:#0056b3}
table{border-collapse:collapse;margin-top:14px;width:100%}
th,td{border:1px solid #ccc;padding:6px;text-align:left}
th{background:#f0f0f0}
.docbtn{background:#dc3545} .docbtn:hover{background:#b02a37}
#camionero_fields{display:none}
</style>
<script>
function toggleCamioneroFields(){
   const cb=document.getElementById('convertir_camionero');
   document.getElementById('camionero_fields').style.display=cb.checked?'block':'none';
}
</script>
</head><body>
<?php require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; ?>
<h1>Detalles del Gestor</h1>

<!-- ------------- Formulario de DATOS ---------------- -->
<form action="actualizar_usuario.php" method="POST">
<input type="hidden" name="usuario_id" value="<?=$colab_id?>">
<h2>Información de Usuario</h2>
<label>Nombre:<input name="nombre_usuario" value="<?=htmlspecialchars($colaborador['nombre_usuario'])?>" required></label>
<label>Apellidos:<input name="apellidos" value="<?=htmlspecialchars($colaborador['apellidos'])?>" required></label>
<label>Teléfono:<input name="telefono" value="<?=htmlspecialchars($colaborador['telefono'])?>"></label>
<label>CIF:<input name="cif" value="<?=htmlspecialchars($colaborador['cif'])?>" required></label>
<label>Email:<input type="email" value="<?=htmlspecialchars($colaborador['email'])?>" disabled></label>
<label>Estado: <?=$colaborador['estado']=='activo'?'Activo':'Inactivo'?></label>

<?php if(!$es_camionero): ?>
<h2>Convertir en Camionero</h2>
<label><input type="checkbox" id="convertir_camionero" name="convertir_camionero" onchange="toggleCamioneroFields()"> Convertir en Camionero</label>
<div id="camionero_fields">
  <label>Fecha de Contratación: <input type="date" name="fecha_contratacion"></label>
  <label>Tipo Carnet: <input name="tipo_carnet" placeholder="C+E"></label>
  <label>Caducidad Carnet: <input type="date" name="fecha_caducidad"></label>
  <label>Número de Licencia: <input name="num_licencia"></label>
  <label>Caducidad Profesional: <input type="date" name="caducidad_profesional"></label>
</div>
<?php endif; ?>

<button type="submit">Guardar Cambios</button>
</form>

<!-- ------------- Cambiar estado ---------------- -->
<form action="cambiar_estado_colaborador.php" method="POST" style="margin-top:10px">
<input type="hidden" name="usuario_id" value="<?=$colab_id?>">
<input type="hidden" name="nuevo_estado" value="<?=$colaborador['estado']=='activo'?'inactivo':'activo'?>">
<button type="submit"><?=$colaborador['estado']=='activo'?'Desactivar Usuario':'Activar Usuario'?></button>
</form>

<hr>

<!-- ------------- Subida de DOCUMENTOS ---------------- -->
<h2>Subir documento</h2>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="subir_doc" value="1">
<label>Tipo de documento:
<select name="tipo_documento">
  <option value="dni">DNI</option>
  <option value="contrato">Contrato</option>
  <option value="otros">Otros</option>
</select></label>
<input type="file" name="archivo" required>
<button type="submit">Subir</button>
</form>

<!-- ------------- Listado de documentos ---------------- -->
<h2>Documentos existentes</h2>
<?php if(!$docs): ?>
<p>No hay documentos subidos.</p>
<?php else: ?>
<table>
<tr><th>ID</th><th>Tipo</th><th>Archivo</th><th>Fecha subida</th><th>-</th></tr>
<?php foreach($docs as $d): ?>
<tr>
  <td><?=$d['id']?></td>
  <td><?=$d['tipo_documento']?></td>
  <td><a href="/<?=$d['ruta_archivo']?>" target="_blank"><?=htmlspecialchars($d['nombre_archivo'])?></a></td>
  <td><?=$d['fecha_subida']?></td>
  <td>
     <form method="POST" style="display:inline">
       <input type="hidden" name="doc_id" value="<?=$d['id']?>">
       <button type="submit" name="eliminar_doc" class="docbtn" onclick="return confirm('¿Eliminar documento?')">Eliminar</button>
     </form>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<a href="gestinar_colaboradores.php" style="display:block;margin-top:20px">Volver a Gestión de Gestores</a>
</body></html>
