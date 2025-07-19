<?php
require_once __DIR__ . '/conexion.php';

$now = date('Y-m-d H:i:s');

try {
    $stmt = $pdo->prepare("DELETE FROM autologin_tokens WHERE fecha_expira < ? OR usado = 1");
    $stmt->execute([$now]);
    $deleted = $stmt->rowCount();
    echo "Deleted $deleted expired or used tokens\n";
} catch (Exception $e) {
    echo "Error cleaning tokens: " . $e->getMessage() . "\n";
}
