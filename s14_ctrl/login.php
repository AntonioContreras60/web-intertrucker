<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* -----------------------------------------------------------
 *  Login Super-Admin  –  /s14_ctrl/login.php
 *  Requiere: tabla usuarios con rol = 'superadmin'
 * ----------------------------------------------------------- */
session_start();
if (isset($_SESSION['rol']) && $_SESSION['rol']==='superadmin') {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login Super-Admin • InterTrucker</title>
  <link rel="stylesheet" href="s14_ctrl.css">
</head>
<body class="login-page">
  <h1>Panel Super-Admin</h1>

  <?php if (isset($_GET['error'])): ?>
      <p class="error">Credenciales incorrectas.</p>
  <?php endif; ?>

  <form action="procesar_login.php" method="POST" class="login-form">
      <label>Email
          <input type="email" name="email" required>
      </label>
      <label>Contraseña
          <input type="password" name="contrasena" required minlength="8">
      </label>
      <button type="submit">Entrar</button>
  </form>
</body>
</html>
