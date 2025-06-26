<?php
session_start();
include 'conexion.php';
include 'header.php';

/* ---------- 1) Seguridad ---------- */
if (!isset($_SESSION['usuario_id'])) {
    header('Location:/Perfil/inicio_sesion.php');
    exit();
}
$usuario_id = $_SESSION['usuario_id'];

/* ---------- 2) Averiguar admin_id (gestores) ---------- */
$stmt = $conn->prepare("SELECT admin_id FROM usuarios WHERE id=?");
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$admin_id = $stmt->get_result()->fetch_assoc()['admin_id'] ?? $usuario_id;
$stmt->close();

/* ---------- 3) Grupos ---------- */
$stmt = $conn->prepare("SELECT id,nombre FROM grupos WHERE usuario_id=?");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$resultado_grupos = $stmt->get_result();
$stmt->close();

/* ---------- 4) Buscar nuevos contactos (usuarios admin) ---------- */
if (!empty($_POST['busqueda_contacto'])) {
    $term = '%'.$_POST['busqueda_contacto'].'%';
    $sql = "SELECT u.id,
                   u.nombre_usuario,
                   u.email,
                   CASE WHEN c.contacto_usuario_id IS NULL THEN 'No' ELSE 'Sí' END AS es_contacto
            FROM usuarios u
            LEFT JOIN contactos c
              ON c.usuario_id=? AND c.contacto_usuario_id=u.id
            WHERE (u.nombre_usuario LIKE ? OR u.email LIKE ?)
              AND u.rol='administrador'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $admin_id, $term, $term);
    $stmt->execute();
    $resultado_busqueda = $stmt->get_result();
    $stmt->close();
}

/* ---------- 5) Añadir / eliminar contacto ---------- */
if (isset($_GET['añadir_contacto']) || isset($_GET['eliminar_contacto'])) {
    $target = intval($_GET['añadir_contacto'] ?? $_GET['eliminar_contacto']);

    if (isset($_GET['añadir_contacto'])) {
        $ins = $conn->prepare("INSERT IGNORE INTO contactos (usuario_id, contacto_usuario_id) VALUES (?,?)");
        $ins->bind_param('ii', $admin_id, $target);
        $ins->execute();
        $ins->close();
    } else {
        $del = $conn->prepare("DELETE FROM contactos WHERE usuario_id=? AND contacto_usuario_id=?");
        $del->bind_param('ii', $admin_id, $target);
        $del->execute();
        $del->close();
    }
    header("Location: my_network.php");
    exit();
}

/* ---------- 6) Crear entidad externa ---------- */
if (isset($_POST['crear_entidad'])) {
    $sql = "INSERT INTO entidades
              (usuario_id,nombre,telefono,email,cif,observaciones)
            VALUES (?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'isssss',
        $admin_id,
        $_POST['nombre'],
        $_POST['telefono'],
        $_POST['email'],
        $_POST['cif'],
        $_POST['observaciones']
    );
    $stmt->execute();
    $id_ent = $stmt->insert_id;
    $stmt->close();
    header("Location: gestionar_direcciones_entidad.php?entidad_id=$id_ent");
    exit();
}

/* ---------- 7) Contactos actuales ---------- */
$stmt = $conn->prepare("
    SELECT u.id,u.nombre_usuario,u.email
    FROM contactos c
    JOIN usuarios u ON u.id=c.contacto_usuario_id
    WHERE c.usuario_id=?");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$resultado_contactos = $stmt->get_result();
$stmt->close();

/* ---------- 8) Entidades externas (solo si NO existe usuario con mismo email) ---------- */
$stmt = $conn->prepare("
    SELECT e.id,e.nombre,e.email
    FROM entidades e
    LEFT JOIN usuarios u ON u.email = e.email
    WHERE e.usuario_id = ?
      AND u.id IS NULL");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$resultado_entidades = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi network</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* -------- Botón blue reutilizable -------- */
.btn{
    display:inline-block;padding:7px 16px;margin:4px 0;
    background:#007bff;color:#fff;border:none;border-radius:4px;
    font-size:1rem;font-weight:600;text-decoration:none;cursor:pointer
}
.btn:hover{background:#0056b3}
/* Para plegar / desplegar listas */
.hide{display:none;}
</style>
</head>
<body>

<h1>Mi red</h1>

<!-- BUSCAR CONTACTO -->
<h2>Buscar contacto (usuarios administradores):</h2>
<form method="post">
    <input type="text" name="busqueda_contacto" placeholder="Nombre o e-mail" required>
    <button class="btn">Buscar</button>
</form>

<?php if (isset($resultado_busqueda)): ?>
    <?php if ($resultado_busqueda->num_rows): ?>
        <ul>
        <?php while($c=$resultado_busqueda->fetch_assoc()): ?>
            <li style="font-size:1.1em;">
              <?=htmlspecialchars($c['nombre_usuario'])?>
              (<?=htmlspecialchars($c['email'])?>)
              – <?= $c['es_contacto']==='Sí'
                    ? 'Ya es contacto'
                    : '<a class="btn" href="?añadir_contacto='.$c['id'].'">Añadir</a>'?>
            </li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?><p>No se encontraron resultados.</p><?php endif; ?>
<?php endif; ?>

<!-- FORMULARIO ENTIDAD -->
<h2>Crear contacto externo (no usuario):</h2>
<button class="btn" onclick="document.getElementById('formExt').classList.toggle('hide')">
    Mostrar / Ocultar formulario
</button>

<div id="formExt" class="hide" style="margin-top:8px">
  <form method="post">
    Nombre:<br><input name="nombre" required><br>
    Teléfono:<br><input name="telefono" required><br>
    Email:<br><input type="email" name="email" required><br>
    CIF/NIF:<br><input name="cif" required><br>
    Observaciones:<br><textarea name="observaciones"></textarea><br>
    <button class="btn" name="crear_entidad">Crear externo</button>
  </form>
</div>

<!-- GRUPOS -->
<h2>Grupos:</h2>
<a href="crear_grupo.php" class="btn" style="margin-bottom:8px;">Crear grupo</a>
<?php if ($resultado_grupos->num_rows): ?>
  <ul>
    <?php while($g=$resultado_grupos->fetch_assoc()): ?>
      <li style="font-size:1.2em;">
        <?=htmlspecialchars($g['nombre'])?> –
        <a href="ver_grupo.php?grupo_id=<?=$g['id']?>" style="font-size:1.2em;">Ver</a>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?><p>No tienes grupos aún.</p><?php endif; ?>

<!-- LISTAS -->
<h2>Mis contactos</h2>
<button class="btn"
        onclick="document.getElementById('lista')
                 .classList.toggle('hide')">
    Mostrar / Ocultar lista
</button>

<div id="lista" class="hide" style="margin-top:10px">

  <!-- ---------- CONTACTOS-USUARIO ---------- -->
  <strong>Contactos (usuarios)</strong>
  <?php if ($resultado_contactos->num_rows): ?>
    <ul>
      <?php while ($u = $resultado_contactos->fetch_assoc()): ?>
        <li style="font-size:1.1em;">
          <?= htmlspecialchars($u['nombre_usuario']) ?>
          (<?= htmlspecialchars($u['email']) ?>)
          –
          <a href="ver_contacto.php?contacto_id=<?= $u['id'] ?>"
             target="_blank"
             class="btn"
             style="padding:3px 10px;">
             Ver
          </a>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?>
    <p>No hay contactos de usuarios.</p>
  <?php endif; ?>

  <!-- ---------- CONTACTOS-EXTERNOS ---------- -->
  <strong>Contactos externos</strong>
  <?php if ($resultado_entidades->num_rows): ?>
    <ul>
      <?php while ($e = $resultado_entidades->fetch_assoc()): ?>
        <li style="font-size:1.1em;">
          <?= htmlspecialchars($e['nombre']) ?>
          (<?= htmlspecialchars($e['email']) ?>)
        </li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?>
    <p>No hay contactos externos.</p>
  <?php endif; ?>

</div>


<?php include 'footer.php'; ?>
</body>
</html>
