<?php
/* -----------------------------------------------------------------
 *  /s14_ctrl/generar_pdf_factura.php?id=123
 *  Genera (o regenera) el PDF para la factura indicada
 *  – Necesita Dompdf en /vendor/dompdf/autoload.inc.php
 * ----------------------------------------------------------------- */

if (!isset($_GET['id'])) { die('Falta id'); }
$facturaId = (int)$_GET['id'];

require_once __DIR__.'/../conexion.php';
require_once __DIR__.'/../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

/* ── 1 ▸ datos de la factura + empresa ────────────────────────── */
$sql = "
   SELECT f.*, u.nombre_empresa
   FROM   facturas_saas f
   JOIN   usuarios u ON u.id = f.empresa_id
   WHERE  f.id = ?
   LIMIT  1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $facturaId);
$stmt->execute();
$fx = $stmt->get_result()->fetch_assoc();
if (!$fx) die('Factura no encontrada');

/* ── 2 ▸ HTML de la factura (muy simple) ──────────────────────── */
$logo = 'https://intertrucker.net/imagenes/logos/intertrucker_chato.jpg';
$html = "
<style>
 body{font-family:DejaVu Sans;font-size:12px;margin:0 36px;}
 h1{font-size:20px;margin:0 0 12px;}
 table{width:100%;border-collapse:collapse;margin-top:12px;}
 th,td{border:1px solid #ccc;padding:6px;text-align:right;}
 th{text-align:left;background:#f2f2f2;}
</style>

<img src='$logo' style='height:60px'><br><br>
<h1>Factura {$fx['serie']}-{$fx['num_factura']}</h1>
<strong>Periodo:</strong> {$fx['periodo_ini']} – {$fx['periodo_fin']}<br>
<strong>Cliente:</strong> {$fx['nombre_empresa']} (ID {$fx['empresa_id']})<br><br>

<table>
<tr><th>Concepto</th><th>Base €</th></tr>
<tr><td>Usuarios base</td><td>".number_format($fx['base_usuarios'],2)."</td></tr>
<tr><td>Almacenamiento extra ({$fx['gb_exceso']} GB)</td><td>".number_format($fx['base_memoria'],2)."</td></tr>
<tr><th>Subtotal</th><th>".number_format($fx['subtotal'],2)."</th></tr>
<tr><td>IVA {$fx['iva_pct']} %</td><td>".number_format($fx['iva'],2)."</td></tr>
<tr><th>Total</th><th>".number_format($fx['total'],2)."</th></tr>
</table>

<p style='margin-top:24px;font-size:10px;'>Factura generada automáticamente – InterTrucker SaaS</p>
";

/* ── 3 ▸ Generar PDF ──────────────────────────────────────────── */
$dompdf = new Dompdf(['enable_remote' => true]);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdf = $dompdf->output();

/* ── 4 ▸ Guardar archivo ─────────────────────────────────────── */
$anio = date('Y', strtotime($fx['periodo_ini']));
$dir  = __DIR__."/../facturas_saas/$anio/EMP{$fx['empresa_id']}";
if (!is_dir($dir)) { mkdir($dir, 0775, true); }
$nombrePdf = "{$fx['serie']}-{$fx['num_factura']}.pdf";
file_put_contents("$dir/$nombrePdf", $pdf);

/* ── 5 ▸ Actualizar url en BD si aún está vacía ──────────────── */
if (empty($fx['pdf_url'])) {
    $url = "/facturas_saas/$anio/EMP{$fx['empresa_id']}/$nombrePdf";
    $up  = $conn->prepare("UPDATE facturas_saas SET pdf_url=? WHERE id=? LIMIT 1");
    $up->bind_param('si', $url, $facturaId);
    $up->execute();
}

/* ── 6 ▸ Descargar al navegador ─────────────────────────────── */
header('Content-Type: application/pdf');
header("Content-Disposition: inline; filename=\"$nombrePdf\"");
echo $pdf;
