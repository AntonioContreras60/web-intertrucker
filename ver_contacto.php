<?php
session_start();
include 'conexion.php';

/* ---------- 1) Seguridad de sesión ---------- */
if (!isset($_SESSION['usuario_id'])) {
    exit('Error: No has iniciado sesión.');
}

/* ---------- 2) ID del registro en la tabla contactos ---------- */
$contacto_id = intval($_GET['contacto_id'] ?? 0);
if ($contacto_id <= 0) {
    exit('ID de contacto no válido.');
}

/* ---------- 3) Recuperar contacto + usuario ---------- */
$sql = "
  SELECT  u.nombre_usuario          AS nombre,
          u.email,
          u.telefono,
          u.cif,
          c.visibilidad,
          c.observaciones,
          CONCAT_WS(', ',
             d.nombre_via, d.numero, d.complemento,
             d.codigo_postal, d.ciudad, d.pais)  AS direccion
  FROM contactos c
  JOIN usuarios    u ON u.id = c.contacto_usuario_id
  LEFT JOIN direcciones d
         ON d.usuario_id = u.id
        AND d.tipo_direccion = 'fiscal'
  WHERE c.id = ?                              -- ⚠️ id de la fila
  LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $contacto_id);
$stmt->execute();
$contacto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contacto) {
    exit('Contacto no encontrado.');
}

/* ---------- 4) Aplicar visibilidad ---------- */
if ($contacto['visibilidad'] !== 'completo') {
    $contacto['telefono']  = null;
    $contacto['cif']       = null;
    $contacto['direccion'] = null;
}

/* ---------- 5) Vista ---------- */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalles contacto</title>
<link rel="stylesheet" href="styles.css">
<style>
h1{color:#004d94;margin-top:0}
.btn{background:#007bff;color:#fff;border:none;padding:7px 16px;border-radius:4px;cursor:pointer;font-weight:600}
.btn:hover{background:#0056b3}
label{font-weight:600}
</style>
</head>
<body>

<h1>Detalles del contacto</h1>

<p><strong>Nombre:</strong> <?= htmlspecialchars($contacto['nombre']) ?></p>
<p><strong>Email :</strong> <?= htmlspecialchars($contacto['email'])  ?></p>

<?php if ($contacto['visibilidad'] === 'completo'): ?>
  <p><strong>Teléfono :</strong> <?= htmlspecialchars($contacto['telefono'] ?: 'No disponible') ?></p>
  <p><strong>CIF      :</strong> <?= htmlspecialchars($contacto['cif']      ?: 'No disponible') ?></p>
  <p><strong>Dirección:</strong> <?= htmlspecialchars($contacto['direccion'] ?: 'No disponible') ?></p>
<?php endif; ?>

<h2>Observaciones</h2>
<p><?= nl2br(htmlspecialchars($contacto['observaciones'] ?? '—')) ?></p>

<br>
<button class="btn" onclick="copiar()">Copiar información</button>
<br><br>
<a href="my_network.php">← Volver a contactos</a>

<script>
function copiar(){
 let txt  = `Nombre: <?= addslashes($contacto['nombre']) ?>\n`;
 txt     += `Email : <?= addslashes($contacto['email']) ?>`;
 <?php if ($contacto['visibilidad'] === 'completo'): ?>
 txt += `\nTeléfono : <?= addslashes($contacto['telefono']) ?>`;
 txt += `\nCIF      : <?= addslashes($contacto['cif']) ?>`;
 txt += `\nDirección: <?= addslashes($contacto['direccion']) ?>`;
 <?php endif; ?>
 navigator.clipboard.writeText(txt).then(()=>alert('Información copiada'));
}
</script>

</body>
</html>
