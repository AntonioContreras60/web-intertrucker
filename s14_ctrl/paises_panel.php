<?php
/* -------------------------------------------------
 *  Panel de País / Región con totales plegables
 *  InterTrucker · 2025-05-12
 * ------------------------------------------------- */
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header('Location: login.php'); exit();
}
include '../conexion.php';
include 'header.php';

/* ── Totales País & Región ───────────────────────── */
$paises   = $conn->query("
    SELECT pais, codigo_pais, empresas, usuarios_totales
    FROM   v_totales_pais
    ORDER  BY pais
")->fetch_all(MYSQLI_ASSOC);

$regiones = $conn->query("
    SELECT pais, region, empresas, usuarios_totales
    FROM   v_totales_region
    ORDER  BY pais, region
")->fetch_all(MYSQLI_ASSOC);

/* Índice regiones → $map[pais][] = fila --------------- */
$map = [];
foreach ($regiones as $r)  $map[$r['pais']][] = $r;
?>
<h2>Empresas · País / Región</h2>

<p class="hint">Haz clic en un país para ver sus regiones. Selecciona una
región y saltarás a la tabla de empresas ya filtrada.</p>

<div class="accordion">
<?php foreach ($paises as $p): ?>
  <div class="pais">
    <button class="btn-pais">
      <?= htmlspecialchars($p['pais']) ?>
      (<?= $p['empresas'] ?> emp · <?= $p['usuarios_totales'] ?> us)
    </button>

    <div class="panel">
      <?php if (!empty($map[$p['pais']])): ?>
        <ul>
        <?php foreach ($map[$p['pais']] as $r): ?>
          <li>
            <a href="empresas.php?pais=<?= urlencode($r['pais']) ?>
                                   &region=<?= urlencode($r['region']) ?>">
              <?= htmlspecialchars($r['region']) ?>
              (<?= $r['empresas'] ?> · <?= $r['usuarios_totales'] ?>)
            </a>
          </li>
        <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <em>— Sin regiones definidas —</em>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script>
/* ── Plegar / desplegar ─────────────────────────── */
document.querySelectorAll('.btn-pais').forEach(btn => {
  btn.addEventListener('click', () => {
    btn.classList.toggle('active');
    const panel = btn.nextElementSibling;
    panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
  });
});
</script>

<?php include 'footer.php'; ?>
