<?php
/*****************************************************************
 * Consumo por mes  (v 2.4)
 * – Sin portes duplicados
 * – Botón CSV del mes
 * – En cada porte:  Recogida | Entrega | Detalles | CSV | ZIP
 * – Recupera estilos visuales (tipografía, fondos, hover, zebra)
 *****************************************************************/
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario_id'], $_SESSION['admin_id'])) die("Sesión no iniciada.");

$admin_id = $_SESSION['admin_id'];

/*─────────────── 1) Portes sin duplicados ───────────────*/
$sql = "
 SELECT
     p.id                              AS porte_id,
     p.fecha_recogida,
     p.fecha_entrega,
     p.mercancia_descripcion           AS mercancia,
     ( SELECT CONCAT(u2.nombre_usuario,' ',u2.apellidos)
       FROM porte_tren pt2
       JOIN tren_camionero tc2 ON tc2.tren_id = pt2.tren_id
       JOIN camioneros c2      ON c2.id     = tc2.camionero_id
       JOIN usuarios u2        ON u2.id     = c2.usuario_id
       WHERE pt2.porte_id = p.id
       ORDER BY tc2.id
       LIMIT 1
     ) AS camionero
 FROM   portes p
 JOIN   usuarios u ON p.usuario_creador_id = u.id
 WHERE  u.admin_id = ?
   AND  p.fecha_entrega IS NOT NULL
 GROUP  BY p.id
 ORDER  BY p.fecha_entrega DESC, p.id DESC";
$stmt = $conn->prepare($sql) or die("SQL: ".$conn->error);
$stmt->bind_param("i",$admin_id);
$stmt->execute(); $res = $stmt->get_result();

/* Agrupamos por mes y calculamos memoria */
$portes_mes = [];
while ($row = $res->fetch_assoc()) {
  $mes = (new DateTime($row['fecha_entrega']))->format('Y-m');
  $q = $conn->prepare("SELECT COALESCE(SUM(tamano),0) kb FROM multimedia_recogida_entrega WHERE porte_id=?");
  $q->bind_param("i",$row['porte_id']); $q->execute();
  $row['memoria_kb'] = $q->get_result()->fetch_assoc()['kb'] ?? 0;
  $q->close();
  $portes_mes[$mes][] = $row;
}

/*─────────────── 2) Facturas del mes ───────────────────*/
$fact = $conn->prepare("
  SELECT DATE_FORMAT(periodo_ini,'%M %Y') mes,total,estado,pdf_url
  FROM   facturas_saas WHERE empresa_id=? ORDER BY periodo_ini DESC");
$fact->bind_param("i",$admin_id); $fact->execute();
$map=[]; foreach($fact->get_result() as $f){
  $map[DateTime::createFromFormat('F Y',$f['mes'])->format('Y-m')] = $f;
}
$fact->close(); $conn->close();
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><title>Consumo mensual</title>
<style>
/* —— Layout general —— */
body{font-family:Arial,Helvetica,sans-serif;margin:20px;background:#f5f6fa;color:#222}
h1{margin-bottom:24px;font-size:1.8rem}
.btn-back{display:inline-block;margin-bottom:20px;padding:8px 14px;background:#6c757d;color:#fff;
          border-radius:4px;text-decoration:none;font-weight:bold}
.btn-back:hover{background:#5a6268}

/* —— Bloques mes —— */
.mes-header{cursor:pointer;background:#007bff;color:#fff;padding:12px 16px;margin:14px 0 0;
            border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,.08);font-weight:600;
            display:flex;align-items:center;gap:10px;font-size:1.05rem}
.mes-header a{color:#ffd700;font-size:1.25rem;text-decoration:none}
.mes-header a:hover{color:#ffe97a}
.mes-body{display:none;padding:10px 16px 2px;background:#fff;border:1px solid #007bff;
          border-top:none;border-radius:0 0 6px 6px}

/* —— Líneas de porte —— */
.porte-linea{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
             padding:8px 0;border-bottom:1px solid #e0e0e0;font-size:.92rem}
.porte-linea:nth-child(odd){background:#fafbff}
.porte-info{flex:1;min-width:340px}
.porte-info strong{color:#333}
.porte-info em{color:#555}
.btn{display:inline-block;margin-left:4px;padding:6px 10px;border-radius:4px;font-size:.82rem;
     text-decoration:none;color:#fff}
.btn-det{background:#28a745}.btn-det:hover{background:#218838}
.btn-csv{background:#ffc107;color:#000 !important}.btn-csv:hover{background:#e0a800}
.btn-zip{background:#17a2b8}.btn-zip:hover{background:#138496}
</style>
<script>
function toggleMes(id){
  const box = document.getElementById(id);
  box.style.display = (box.style.display==='none'||box.style.display==='') ? 'block':'none';
}
function stopBub(e){ e.stopPropagation(); }
</script></head><body>

<?php include '../header.php'; ?>

<a class="btn-back" href="perfil_usuario.php">← Volver a Gestión</a>
<h1>Consumo mensual basado en la fecha de entrega</h1>

<?php foreach($portes_mes as $mes=>$lista):
      $mesTxt = DateTime::createFromFormat('Y-m',$mes)->format('F Y');
      $totalMB = array_sum(array_column($lista,'memoria_kb'))/1024;
      $fact = $map[$mes] ?? null; ?>
<div class="mes-header" onclick="toggleMes('mes_<?= $mes ?>')">
  <?= $mesTxt ?> — <?= count($lista) ?> portes, <?= round($totalMB,2) ?> MB
  <?php if($fact): ?>
    — <?= number_format($fact['total'],2) ?> € <span style="text-transform:capitalize"><?= $fact['estado'] ?></span>
    <a href="<?= $fact['pdf_url'] ?>" target="_blank">📄</a>
  <?php endif; ?>
  <a href="exportar_mes_csv.php?mes=<?= $mes ?>" onclick="stopBub(event)" class="btn btn-csv" title="CSV del mes">⬇️</a>
</div>

<div id="mes_<?= $mes ?>" class="mes-body">
<?php foreach($lista as $p):
      $rec = (new DateTime($p['fecha_recogida']))->format('d/m/Y');
      $ent = (new DateTime($p['fecha_entrega']))->format('d/m/Y');
      $mb  = round($p['memoria_kb']/1024,2); ?>
  <div class="porte-linea">
    <div class="porte-info">
      <strong>#<?= $p['porte_id'] ?></strong> |
      Rec: <?= $rec ?> |
      Ent: <?= $ent ?> |
      <em><?= htmlspecialchars($p['mercancia']) ?></em> |
      <span style="color:#007bff"><?= htmlspecialchars($p['camionero'] ?: '—') ?></span> |
      <?= $mb ?> MB
    </div>
    <div>
      <a href="/recogida_entrega_vista.php?porte_id=<?= $p['porte_id'] ?>&tipo_evento=recogida"  target="_blank" class="btn btn-det">Recogida</a>
      <a href="/recogida_entrega_vista.php?porte_id=<?= $p['porte_id'] ?>&tipo_evento=entrega"   target="_blank" class="btn btn-det">Entrega</a>
      <a href="/detalle_porte.php?id=<?= $p['porte_id'] ?>"                                     target="_blank" class="btn btn-det">Detalles</a>
      <a href="exportar_porte_csv.php?porte_id=<?= $p['porte_id'] ?>" target="_blank" class="btn btn-csv" title="CSV del porte">⬇️ porte</a>
      <a href="exportar_multimedia_zip.php?porte_id=<?= $p['porte_id'] ?>" target="_blank" class="btn btn-zip" title="Fotos/vídeos">📦</a>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
</body></html>
