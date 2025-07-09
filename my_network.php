<?php
/* -------- Mi red -------- */
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location:/Perfil/inicio_sesion.php'); exit();
}
$usuario_id = (int)$_SESSION['usuario_id'];

/* --- si empleado, trabajar con el admin_id --- */
$stmt = $conn->prepare("SELECT admin_id FROM usuarios WHERE id=?");
$stmt->bind_param('i',$usuario_id); $stmt->execute();
$admin_id = $stmt->get_result()->fetch_assoc()['admin_id'] ?? $usuario_id;
$stmt->close();

/* --- grupos --- */
$stmt = $conn->prepare("SELECT id,nombre FROM grupos WHERE usuario_id=?");
$stmt->bind_param('i',$admin_id); $stmt->execute();
$grupos = $stmt->get_result(); $stmt->close();

/* --- búsqueda de contactos (admins) --- */
if (!empty($_POST['busqueda_contacto'])) {
    $term='%'.$_POST['busqueda_contacto'].'%';
    $sql="SELECT u.id,u.nombre_usuario,u.email,
                 c.id AS contacto_id,
                 CASE WHEN c.contacto_usuario_id IS NULL THEN 'No' ELSE 'Sí' END es_contacto
          FROM usuarios u
          LEFT JOIN contactos c ON c.usuario_id=? AND c.contacto_usuario_id=u.id
          WHERE (u.nombre_usuario LIKE ? OR u.email LIKE ?)
            AND u.rol='administrador'";
    $stmt=$conn->prepare($sql);
    $stmt->bind_param('iss',$admin_id,$term,$term);
    $stmt->execute(); $busqueda=$stmt->get_result(); $stmt->close();
}

/* --- alta / baja contacto --- */
if (isset($_GET['añadir_contacto']) || isset($_GET['eliminar_contacto'])) {
    $t=(int)($_GET['añadir_contacto']??$_GET['eliminar_contacto']);
    if(isset($_GET['añadir_contacto'])){
        $conn->prepare("INSERT IGNORE INTO contactos (usuario_id,contacto_usuario_id) VALUES (?,?)")
             ->bind_param('ii',$admin_id,$t)->execute();
    }else{
        $conn->prepare("DELETE FROM contactos WHERE usuario_id=? AND contacto_usuario_id=?")
             ->bind_param('ii',$admin_id,$t)->execute();
    }
    header('Location: my_network.php'); exit();
}

/* --- nueva entidad externa --- */
if(isset($_POST['crear_entidad'])){
    $sql="INSERT INTO entidades (usuario_id,nombre,telefono,email,cif,observaciones)
          VALUES (?,?,?,?,?,?)";
    $stmt=$conn->prepare($sql);
    $stmt->bind_param('isssss',$admin_id,
        $_POST['nombre'],$_POST['telefono'],$_POST['email'],
        $_POST['cif'],$_POST['observaciones']);
    $stmt->execute(); $id=$stmt->insert_id; $stmt->close();
    header("Location: gestionar_direcciones_entidad.php?entidad_id=$id"); exit();
}

/* --- contactos actuales --- */
$stmt=$conn->prepare("
    SELECT c.id contacto_id,u.nombre_usuario,u.email
    FROM contactos c JOIN usuarios u ON u.id=c.contacto_usuario_id
    WHERE c.usuario_id=?");
$stmt->bind_param('i',$admin_id); $stmt->execute();
$contactos=$stmt->get_result(); $stmt->close();

/* --- entidades externas --- */
$stmt=$conn->prepare("
    SELECT e.nombre,e.email
    FROM entidades e
    LEFT JOIN usuarios u ON u.email=e.email
    WHERE e.usuario_id=? AND u.id IS NULL");
$stmt->bind_param('i',$admin_id); $stmt->execute();
$entidades=$stmt->get_result(); $stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi red</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/styles.css">
<style>
/* Botón coherente con el primario global */
.btn{display:inline-block;width:auto;max-width:260px;padding:.55rem 1.1rem;border-radius:8px;
     background:var(--color-primary);color:#fff;font-weight:600;
     text-decoration:none;border:none;cursor:pointer}
.btn:hover{filter:brightness(92%);}
.hide{display:none;}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<main style="padding:var(--spacing-large);">
<h1>Mi red</h1>

<!-- ========== Añadir contacto ========== -->
<h2>Añadir contacto</h2>
<form method="post" style="margin-bottom:var(--spacing-base);">
    <input type="text" name="busqueda_contacto" placeholder="Nombre o e-mail" required>
    <button class="btn">Buscar</button>
</form>

<?php if(isset($busqueda)): ?>
  <?php if($busqueda->num_rows): ?>
    <ul>
      <?php while($c=$busqueda->fetch_assoc()): ?>
        <li>
          <?= htmlspecialchars($c['nombre_usuario']) ?> (<?= htmlspecialchars($c['email']) ?>) –
          <?php if($c['es_contacto']==='Sí'): ?>
             Ya es contacto
             <a class="btn" href="ver_contacto.php?contacto_id=<?= $c['contacto_id'] ?>" style="padding:3px 10px;">Ver</a>
          <?php else: ?>
             <a class="btn" href="?añadir_contacto=<?= $c['id'] ?>">Añadir</a>
          <?php endif; ?>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?><p>No se encontraron resultados.</p><?php endif; ?>
<?php endif; ?>

<!-- ========== Contacto externo ========== -->
<h2>Crear contacto externo</h2>
<button class="btn" onclick="document.getElementById('formExt').classList.toggle('hide')">
    Mostrar / Ocultar formulario
</button>

<div id="formExt" class="hide" style="margin-top:var(--spacing-base);">
  <form method="post">
    <p>Nombre:<br><input name="nombre" required></p>
    <p>Teléfono:<br><input name="telefono" required></p>
    <p>Email:<br><input type="email" name="email" required></p>
    <p>CIF/NIF:<br><input name="cif" required></p>
    <p>Observaciones:<br><textarea name="observaciones"></textarea></p>
    <button class="btn" name="crear_entidad">Crear externo</button>
  </form>
</div>

<!-- ========== Grupos ========== -->
<h2>Grupos</h2>
<a href="crear_grupo.php" class="btn" style="margin-bottom:var(--spacing-base);">Crear grupo</a>
<?php if($grupos->num_rows): ?>
  <ul>
    <?php while($g=$grupos->fetch_assoc()): ?>
      <li>
        <?= htmlspecialchars($g['nombre']) ?> –
        <a class="btn" style="padding:3px 10px;" href="ver_grupo.php?grupo_id=<?= $g['id'] ?>">Ver</a>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?><p>No tienes grupos aún.</p><?php endif; ?>

<!-- ========== Mis contactos ========== -->
<h2>Mis contactos</h2>
<button class="btn" onclick="document.getElementById('lista').classList.toggle('hide')">
    Mostrar / Ocultar lista
</button>

<div id="lista" class="hide" style="margin-top:var(--spacing-base);">

  <strong>Contactos (usuarios)</strong>
  <?php if($contactos->num_rows): ?>
    <ul>
      <?php while($u=$contactos->fetch_assoc()): ?>
        <li>
          <?= htmlspecialchars($u['nombre_usuario']) ?> (<?= htmlspecialchars($u['email']) ?>) –
          <a class="btn" style="padding:3px 10px;" target="_blank"
             href="ver_contacto.php?contacto_id=<?= $u['contacto_id'] ?>">Ver</a>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?><p>No hay contactos de usuarios.</p><?php endif; ?>

  <strong>Contactos externos</strong>
  <?php if($entidades->num_rows): ?>
    <ul>
      <?php while($e=$entidades->fetch_assoc()): ?>
        <li><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($e['email']) ?>)</li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?><p>No hay contactos externos.</p><?php endif; ?>
</div>
</main>

<?php include 'footer.php'; ?>
</body>
</html>
