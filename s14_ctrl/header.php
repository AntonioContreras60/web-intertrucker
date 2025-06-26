<?php
/* -----------------------------------------------------------
 *  Cabecera común – Panel Super-Admin  •  InterTrucker
 *  Fecha: 2025-05-12
 * ----------------------------------------------------------- */
session_start();
if (($_SESSION['rol'] ?? '') !== 'superadmin') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Super-Admin • InterTrucker</title>
  <link rel="stylesheet" href="s14_ctrl.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<header class="sa-header">
  <h2>InterTrucker • Panel Super-Admin</h2>

  <nav>
    <a href="dashboard.php">Inicio</a>
    <a href="empresas.php">Empresas / Consumo</a>
    <a href="paises_panel.php">Países / Regiones</a>        <!-- ← NUEVO -->
    
    <!-- Sólo visible cuando estás impersonando a un cliente -->
    <?php if (isset($_SESSION['impersonador_id'])): ?>
      <a href="salir_impersonar.php" class="salir-observer">
        Salir modo observador
      </a>
    <?php endif; ?>

    <a href="../logout.php">Cerrar sesión</a>
  </nav>
</header>

<main>

<?php /* Banner amarillo si estamos en modo observador ----------*/ ?>
<?php if (isset($_SESSION['impersonador_id'])): ?>
  <div class="banner-observer">
    Modo observador activo – Estás viendo la cuenta de
    <strong><?= htmlspecialchars($_SESSION['nombre_usuario_original'] ?? 'usuario') ?></strong>.
    <a href="salir_impersonar.php">Salir</a>
  </div>
<?php endif; ?>
