<?php
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador']);
phpinfo();
?>
