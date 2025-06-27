<?php
session_start();
include 'conexion.php';

/* ───────── SEGURIDAD ───────── */
if (!isset($_SESSION['usuario_id'])) {
    exit('Error: No has iniciado sesión.');
}
$usuario_id  = $_SESSION['usuario_id'];

/* ─────── ID DEL CONTACTO ─────── */
$contacto_id = intval($_GET['contacto_id'] ?? 0);
if ($contacto_id <= 0) { exit('ID de contacto no válido.'); }

/* ───────── CONSULTA PRINCIPAL (contactos) ───────── */
$sql = "
 SELECT  u.nombre_usuario AS nombre,
         u.email,
         u.telefono,
         u.cif,
         c.visibilidad,
         c.observaciones,
         CONCAT_WS(', ',
           d.nombre_via,d.numero,d.complemento,
           d.codigo_postal,d.ciudad,d.pais)     AS direccion
 FROM contactos c
 JOIN usuarios u         ON u.id = c.contacto_usuario_id
 LEFT JOIN direcciones d ON d.usuario_id = u.id
                        AND d.tipo_direccion = 'fiscal'
 WHERE c.usuario_id = ? AND u.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $usuario_id, $contacto_id);
$stmt->execute();
$contacto = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ───────── PLAN B: NO ESTÁ EN CONTACTOS ───────── */
if (!$contacto) {
    $stmt = $conn->prepare("
        SELECT nombre_usuario AS nombre,
               email,
               telefono,
               cif
        FROM usuarios
        WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $contacto_id);
    $stmt->execute();
    $contacto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$contacto) { exit('Contacto no encontrado.'); }
    // sólo básico
    $contacto['visibilidad']  = 'basico';
    $contacto['observaciones'] = null;
    $contacto['direccion']     = null;
}

/* ─────── GUARDAR OBSERVACIONES (sólo si existe fila en contactos) ─────── */
if ($_SERVER['REQUEST_METHOD']==='POST'
    && isset($_POST['observaciones'])
    && $contacto['visibilidad']!=='basico'   /* seguridad extra */ ) {

    $upd = $conn->prepare("
        UPDATE contactos
           SET observaciones = ?
         WHERE usuario_id = ? AND contacto_usuario_id = ?");
    $upd->bind_param('sii', $_POST['observaciones'], $usuario_id, $contacto_id);
    $upd->execute();
    $upd->close();
    header("Location: ver_contacto.php?contacto_id=$contacto_id&guardado=1");
    exit;
}

/* ─────── ELIMINAR (sólo si existe en contactos) ─────── */
if (isset($_GET['eliminar_contacto']) && $contacto['visibilidad']!=='basico') {
    $del = $conn->prepare("
        DELETE FROM contactos
         WHERE usuario_id=? AND contacto_usuario_id=?");
    $del->bind_param('ii', $usuario_id, $contacto_id);
    $del->execute();
    $del->close();
    header('Location: my_network.php'); exit;
}
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><title>Detalles contacto</title>
<link rel="stylesheet" href="styles.css">
<style>
h1{color:#004d94;margin-top:0} label{font-weight:600}
.btn{background:#007bff;color:#fff;border:none;padding:7px 16px;border-radius:4px;cursor:pointer;font-weight:600}
.btn:hover{background:#0056b3}
</style></head><body>

<h1>Detalles del contacto</h1>

<p><strong>Nombre: </strong><?= htmlspecialchars($contacto['nombre']) ?></p>
<p><strong>Email : </strong><?= htmlspecialchars($contacto['email'])  ?></p>

<?php if ($contacto['visibilidad']==='completo'): ?>
  <p><strong>Teléfono : </strong><?= htmlspecialchars($contacto['telefono'] ?: 'No disponible') ?></p>
  <p><strong>CIF      : </strong><?= htmlspecialchars($contacto['cif']      ?: 'No disponible') ?></p>
  <p><strong>Dirección: </strong><?= htmlspecialchars($contacto['direccion'] ?: 'No disponible') ?></p>
<?php endif; ?>

<h2>Observaciones</h2>
<p><?= nl2br(htmlspecialchars($contacto['observaciones'] ?? '—')) ?></p>

<?php if (isset($_GET['guardado'])): ?>
  <p style="color:green;">Observaciones guardadas.</p>
<?php endif; ?>

<?php if ($contacto['visibilidad']==='completo'): ?>
<form method="post">
  <label for="observaciones">Editar observaciones:</label><br>
  <textarea name="observaciones" id="observaciones" rows="5" cols="40"><?= htmlspecialchars($contacto['observaciones'] ?? '') ?></textarea><br>
  <button class="btn">Guardar</button>
</form>
<?php endif; ?>

<br>
<button class="btn" onclick="copiar()">Copiar información</button>
<?php if ($contacto['visibilidad']==='completo'): ?>
  &nbsp;
  <a class="btn" style="background:#d9534f"
     href="?contacto_id=<?= $contacto_id ?>&eliminar_contacto=1"
     onclick="return confirm('¿Eliminar este contacto?');">Eliminar contacto</a>
<?php endif; ?>
<br><br>
<a href="my_network.php">← Volver a contactos</a>

<script>
function copiar(){
 let txt  = `Nombre: <?= addslashes($contacto['nombre']) ?>\n`;
 txt     += `Email : <?= addslashes($contacto['email']) ?>`;
 <?php if ($contacto['visibilidad']==='completo'): ?>
 txt += `\nTeléfono : <?= addslashes($contacto['telefono']) ?>`;
 txt += `\nCIF      : <?= addslashes($contacto['cif']) ?>`;
 txt += `\nDirección: <?= addslashes($contacto['direccion']) ?>`;
 <?php endif; ?>
 navigator.clipboard.writeText(txt).then(()=>alert('Información copiada'));
}
</script>
</body></html>
