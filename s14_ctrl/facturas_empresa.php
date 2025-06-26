<?php
/* -----------------------------------------------------------
 *  /s14_ctrl/facturas_empresa.php?id=1015
 *  Lista de facturas SaaS por empresa (solo super-admin)
 * ----------------------------------------------------------- */
session_start();
if (empty($_SESSION['rol']) || $_SESSION['rol']!=='superadmin') { die('Acceso denegado'); }

$empresaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($empresaId<=0) die('Empresa no válida');

require_once __DIR__.'/../conexion.php';

/* nombre de la empresa */
$nom = $conn->query("SELECT nombre_empresa FROM usuarios
                     WHERE id=$empresaId LIMIT 1")->fetch_row()[0] ?? 'Empresa';

/* facturas */
$stmt = $conn->prepare("
    SELECT id, serie, num_factura,
           DATE_FORMAT(periodo_ini,'%Y-%m') AS mes,
           total, estado, pdf_url
      FROM facturas_saas
     WHERE empresa_id = ?
     ORDER BY periodo_ini DESC
");
$stmt->bind_param('i',$empresaId);
$stmt->execute();
$fac = $stmt->get_result();
?>
<!doctype html><html lang="es"><meta charset="utf-8">
<title>Facturas • <?= htmlspecialchars($nom) ?></title>
<link rel="stylesheet" href="s14_ctrl.css">
<h1>Facturas de <?= htmlspecialchars($nom) ?></h1>
<table>
 <thead><tr>
   <th>Mes</th><th>Nº factura</th><th>Total €</th><th>Estado</th><th>PDF</th>
 </tr></thead><tbody>
 <?php while ($f=$fac->fetch_assoc()): ?>
   <tr>
     <td><?= $f['mes'] ?></td>
     <td><?= $f['serie'].'-'.$f['num_factura'] ?></td>
     <td style="text-align:right"><?= number_format($f['total'],2) ?></td>
     <td><?= $f['estado'] ?></td>
     <td>
       <?php if ($f['pdf_url']): ?>
         <a href="<?= $f['pdf_url'] ?>" target="_blank">📄</a>
       <?php else: ?>–<?php endif; ?>
     </td>
   </tr>
 <?php endwhile; ?>
 </tbody>
</table>
<a href="empresas.php">← Volver a empresas</a>
