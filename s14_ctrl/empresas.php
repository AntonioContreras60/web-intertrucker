<?php
/* -------------------------------------------------
 *  Panel Super-Admin → Empresas
 *  Versión con filtros País / Región
 *  y columna “Ubicación”                 2025-05-10
 * ------------------------------------------------- */
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol']!=='superadmin') {
    header('Location: login.php'); exit();
}

include '../conexion.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/header.php';

/* ───────────────────────── 1 · Filtros recibidos ───────────────────────── */
$paisSel   = isset($_GET['pais'])   ? $_GET['pais']   : '';
$regionSel = isset($_GET['region']) ? $_GET['region'] : '';

/* ───────────────────────── 2 · Listas País / Región ────────────────────── */
$paises = $conn->query("
    SELECT DISTINCT pais
    FROM   v_empresas_con_ubicacion
    WHERE  pais IS NOT NULL AND pais <> ''
    ORDER  BY pais
")->fetch_all(MYSQLI_ASSOC);

$regiones = [];
if ($paisSel) {
    $regiones = $conn->query("
        SELECT DISTINCT region
        FROM   v_empresas_con_ubicacion
        WHERE  pais = '".$conn->real_escape_string($paisSel)."'
          AND  region IS NOT NULL AND region <> ''
        ORDER  BY region
    ")->fetch_all(MYSQLI_ASSOC);
}

/* ──────────────────────── 3 · Administradores + ubicación ─────────────── */
$sqlAdmins = "
    SELECT  u.id,
            u.nombre_empresa,
            u.nombre_usuario AS administrador,
            u.email,
            v.pais,
            v.region,
            v.codigo_pais
    FROM    usuarios u
    JOIN    v_empresas_con_ubicacion v ON v.empresa_id = u.id
    WHERE   u.rol = 'administrador'
";
if ($paisSel)   $sqlAdmins .= " AND v.pais   = '".$conn->real_escape_string($paisSel)."'";
if ($regionSel) $sqlAdmins .= " AND v.region = '".$conn->real_escape_string($regionSel)."'";
$sqlAdmins .= " ORDER BY u.nombre_empresa";

$admins = $conn->query($sqlAdmins);

/* ───────────────────────── 4 · Estructura empresas ─────────────────────── */
$empresas = [];
while ($a = $admins->fetch_assoc()) {

    /* Gestores */
    $gestores = $conn->query("
        SELECT id, nombre_usuario, email
        FROM   usuarios
        WHERE  admin_id = {$a['id']} AND rol = 'gestor' AND estado = 'activo'
        ORDER  BY nombre_usuario
    ")->fetch_all(MYSQLI_ASSOC);

    /* Camioneros */
    $camioneros = $conn->query("
        SELECT id, nombre_usuario, email
        FROM   usuarios
        WHERE  admin_id = {$a['id']} AND rol = 'camionero' AND estado = 'activo'
        ORDER  BY nombre_usuario
    ")->fetch_all(MYSQLI_ASSOC);

    /* Memoria total (KB) */
    $memKB = 0;
    $memKB += (int) $conn->query("
        SELECT COALESCE(SUM(me.tamano),0) AS kb
        FROM   multimedia_recogida_entrega me
        JOIN   portes p  ON p.id = me.porte_id
        JOIN   usuarios u ON u.id = p.usuario_creador_id
        WHERE  (u.admin_id = {$a['id']} OR u.id = {$a['id']})
    ")->fetch_assoc()['kb'];
    $memKB += (int) $conn->query("
        SELECT COALESCE(SUM(dv.tamano_kb),0) AS kb
        FROM   documentos_vehiculos dv
        JOIN   vehiculos v ON v.id = dv.vehiculo_id
        JOIN   usuarios u ON u.id = v.usuario_id
        WHERE  (u.admin_id = {$a['id']} OR u.id = {$a['id']})
    ")->fetch_assoc()['kb'];

    /* Datos agregados */
    $a['gestores']        = $gestores;
    $a['camioneros']      = $camioneros;
    $a['num_gestores']    = count($gestores);
    $a['num_camioneros']  = count($camioneros);
    $a['memoria_gb']      = number_format($memKB / 1048576, 3);     // KB → GB
    $a['ubicacion']       = $a['pais']
                            ? $a['pais'].($a['region'] ? ' / '.$a['region'] : '')
                            : '—';

    $empresas[] = $a;
}

/* ────────────────────────── 5 · Totales tarjetas ──────────────────────── */
$totalEmp = count($empresas);
$totalGest = $totalCam = $totalMem = 0;
foreach ($empresas as $e) {
    $totalGest += $e['num_gestores'];
    $totalCam  += $e['num_camioneros'];
    $totalMem  += (float) $e['memoria_gb'];
}
?>

<h2>Empresas registradas</h2>

<!-- ─────────────── 6 · Filtros País / Región ─────────────── -->
<form id="filtros" class="filtros" method="get">
  <label>País:
    <select name="pais" id="paisSel">
      <option value="">Todos</option>
      <?php foreach ($paises as $p): ?>
        <option value="<?= htmlspecialchars($p['pais']) ?>"
                <?= $paisSel == $p['pais'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['pais']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Región:
    <select name="region" id="regionSel" <?= $paisSel ? '' : 'disabled' ?>>
      <option value="">Todas</option>
      <?php foreach ($regiones as $r): ?>
        <option value="<?= htmlspecialchars($r['region']) ?>"
                <?= $regionSel == $r['region'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($r['region']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <noscript><button type="submit">Filtrar</button></noscript>
</form>

<!-- ─────────────── 7 · Tarjetas totales ─────────────── -->
<div class="cards">
  <article><h4>Empresas</h4><p><?= $totalEmp ?></p></article>
  <article><h4>Gestores</h4><p><?= $totalGest ?></p></article>
  <article><h4>Camioneros</h4><p><?= $totalCam ?></p></article>
  <article><h4>Memoria GB</h4><p><?= number_format($totalMem,3) ?></p></article>
</div>

<!-- ─────────────── 8 · Tabla principal ─────────────── -->
<table class="listado" id="empresasTab">
  <thead>
    <tr>
      <th></th><th>Empresa</th><th>Administrador</th>
      <th>Ubicación</th>
      <th>#Gestores</th><th>#Camioneros</th>
      <th>Memoria (GB)</th><th>Acción</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($empresas as $e): ?>
    <!-- Fila empresa -->
    <tr class="empresa" data-id="<?= $e['id'] ?>">
      <td class="toggle">▶</td>
      <td><?= htmlspecialchars($e['nombre_empresa']) ?></td>
      <td><?= htmlspecialchars($e['administrador']) ?></td>
      <td><?= htmlspecialchars($e['ubicacion']) ?></td>
      <td><?= $e['num_gestores'] ?></td>
      <td><?= $e['num_camioneros'] ?></td>
      <td><?= $e['memoria_gb'] ?></td>
      <td class="acciones">
        <a href="impersonar.php?uid=<?= $e['id'] ?>"
           target="_blank" rel="noopener noreferrer" title="Ver como">👁</a>
        <a href="facturas_empresa.php?id=<?= $e['id'] ?>"
           title="Ver facturas">📄</a>
      </td>
    </tr>

    <!-- Detalle Gestores -->
    <tr class="detalle hidden" data-parent="<?= $e['id'] ?>">
      <td></td>
      <td colspan="7">
        <strong>Gestores</strong>
        <ul>
          <?php foreach ($e['gestores'] as $g): ?>
            <li><?= htmlspecialchars($g['nombre_usuario'].' – '.$g['email']) ?></li>
          <?php endforeach; ?>
          <?php if (!$e['gestores']) echo '<li><em>Ninguno</em></li>'; ?>
        </ul>
      </td>
    </tr>

    <!-- Detalle Camioneros -->
    <tr class="detalle hidden" data-parent="<?= $e['id'] ?>">
      <td></td>
      <td colspan="7">
        <strong>Camioneros</strong>
        <ul>
          <?php foreach ($e['camioneros'] as $c): ?>
            <li><?= htmlspecialchars($c['nombre_usuario'].' – '.$c['email']) ?></li>
          <?php endforeach; ?>
          <?php if (!$e['camioneros']) echo '<li><em>Ninguno</em></li>'; ?>
        </ul>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- ─────────────── 9 · JS: plegar filas + autosubmit filtros ───────────── -->
<script>
/* Plegar / desplegar gestores & camioneros */
document.querySelectorAll('#empresasTab .toggle').forEach(function(btn){
  btn.addEventListener('click', function(){
      const id = this.parentElement.getAttribute('data-id');
      document.querySelectorAll('.detalle[data-parent="'+id+'"]')
              .forEach(tr => tr.classList.toggle('hidden'));
      this.textContent = (this.textContent === '▶') ? '▼' : '▶';
  });
});

/* Autosubmit en los filtros */
document.getElementById('paisSel').addEventListener('change', function(){
    document.getElementById('regionSel').value = '';
    document.getElementById('filtros').submit();
});
document.getElementById('regionSel').addEventListener('change', function(){
    document.getElementById('filtros').submit();
});
</script>

<?php include 'footer.php'; ?>
