<?php require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; ?>
<section class="resumen">
  <h3>Bienvenido, <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></h3>
  <p>Usa el menú para gestionar empresas, revisar consumo y facturar.</p>
</section>
<?php include 'footer.php'; ?>
