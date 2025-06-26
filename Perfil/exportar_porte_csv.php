<?php
/*****************************************************************
 * exportar_porte_csv.php · v 1.4  (usa localizacion_recogida / entrega)
 *****************************************************************/
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['admin_id'])) die("Sesión no iniciada.");

$admin_id = $_SESSION['admin_id'];
$porte_id = isset($_GET['porte_id']) ? (int)$_GET['porte_id'] : 0;
if ($porte_id <= 0) die("ID de porte no válido");

/*--------------------------------------------------------------
  Consulta principal
--------------------------------------------------------------*/
$sql = "
 SELECT
     p.id                                   AS porte_id,
     p.fecha_recogida,
     p.fecha_entrega,
     p.mercancia_descripcion                AS mercancia,

     /* chófer (1º asociado al tren, si lo hay) */
     ( SELECT CONCAT(u2.nombre_usuario,' ',u2.apellidos)
       FROM porte_tren pt2
       JOIN tren_camionero tc2 ON tc2.tren_id = pt2.tren_id
       JOIN camioneros c2      ON c2.id       = tc2.camionero_id
       JOIN usuarios   u2      ON u2.id       = c2.usuario_id
       WHERE pt2.porte_id = p.id
       ORDER BY tc2.id LIMIT 1
     )                                       AS camionero,

     /* Direcciones almacenadas directamente en PORTES */
     p.localizacion_recogida,
     p.localizacion_entrega,

     /* Peso multimedia en MB */
     ROUND(
        COALESCE((
            SELECT SUM(tamano)
            FROM   multimedia_recogida_entrega m
            WHERE  m.porte_id = p.id
        ),0) / 1024 , 2
     )                                       AS memoria_mb
 FROM   portes   p
 JOIN   usuarios u ON p.usuario_creador_id = u.id
 WHERE  u.admin_id = ?  AND p.id = ?
 GROUP  BY p.id
";
$stmt = $conn->prepare($sql) or die("Error SQL: ".$conn->error);
$stmt->bind_param("ii",$admin_id,$porte_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) die("Porte no encontrado o fuera de tu empresa");

/*--------------------------------------------------------------
  CSV
--------------------------------------------------------------*/
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"porte_{$porte_id}.csv\"");

$out = fopen('php://output', 'w');
fputcsv($out, array_keys($data));   // cabeceras
fputcsv($out, $data);               // fila
fclose($out);
exit;
