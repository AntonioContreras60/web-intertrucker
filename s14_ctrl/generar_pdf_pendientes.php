<?php
session_start();
if ($_SESSION['rol']!=='superadmin') die('Acceso denegado');

require_once __DIR__.'/../conexion.php';

/* todas las facturas sin PDF */
$res = $conn->query("SELECT id FROM facturas_saas WHERE pdf_url IS NULL");

while ($f = $res->fetch_assoc()) {
    $_GET['id'] = $f['id'];                 // pasa el id al script existente
    include 'generar_pdf_factura.php';      // crea el PDF y actualiza pdf_url
}
echo "Todos los PDF pendientes generados";
