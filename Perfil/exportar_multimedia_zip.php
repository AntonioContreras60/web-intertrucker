<?php
/*****************************************************************
 * exportar_multimedia_zip.php      ·  v 1.1
 * ▸ Descarga todas las fotos / vídeos de un porte en un ZIP
 * ▸ Acepta rutas:
 *     –  ruta_local (absoluta)
 *     –  url_archivo («/uploads/...», «uploads/...», URL http://…)
 *     –  sólo nombre_archivo  ⇒ se asume /uploads/
 *****************************************************************/
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../conexion.php';
if (!isset($_SESSION['admin_id'])) die("Sesión no iniciada");

$admin_id = $_SESSION['admin_id'];
$porte_id = isset($_GET['porte_id']) ? (int)$_GET['porte_id'] : 0;
if ($porte_id <= 0) die("ID de porte no válido");

/*───────────────────────── 1) Porte pertenece a la empresa ──*/
$chk = $conn->prepare("
        SELECT 1
        FROM   portes p
        JOIN   usuarios u ON p.usuario_creador_id = u.id
        WHERE  p.id = ? AND u.admin_id = ?");
$chk->bind_param("ii", $porte_id, $admin_id);
$chk->execute();
if (!$chk->get_result()->fetch_assoc()) die("No tienes permiso sobre este porte");
$chk->close();

/*───────────────────────── 2) Archivos multimedia ───────────*/
$sql = "
  SELECT nombre_archivo, url_archivo, ruta_local
  FROM   multimedia_recogida_entrega
  WHERE  porte_id = ?
 UNION ALL
  SELECT archivo_nombre AS nombre_archivo,
         CONCAT('/uploads/',archivo_nombre) AS url_archivo,
         NULL AS ruta_local
  FROM   archivos_entrega_recogida
  WHERE  porte_id = ?
";
$q = $conn->prepare($sql);
$q->bind_param("ii",$porte_id,$porte_id);
$q->execute();
$rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close(); $conn->close();

if (!$rows) die("Este porte no tiene archivos multimedia.");

/*───────────────────────── 3) Crear ZIP temp ────────────────*/
$tmp = sys_get_temp_dir()."/zip_porte_{$porte_id}_".bin2hex(random_bytes(3));
mkdir($tmp,0700);
$zipPath = "$tmp/porte_{$porte_id}_multimedia.zip";
$zip = new ZipArchive();
if ($zip->open($zipPath,ZipArchive::CREATE)!==TRUE) die("No se pudo crear el ZIP");

/*───────────────────────── 4) Añadir ficheros ───────────────*/
$DR = rtrim($_SERVER['DOCUMENT_ROOT'],'/');        // ej: /home/…/htdocs
$BASE = dirname(__DIR__).'/uploads';               // fallback absoluto

foreach ($rows as $f){
    /* 1) ruta_local absoluta */
    if (!empty($f['ruta_local']) && file_exists($f['ruta_local'])){
        $zip->addFile($f['ruta_local'], basename($f['ruta_local']));
        continue;
    }

    $u = $f['url_archivo'] ?: $f['nombre_archivo'];

    /* 2) Quitar esquema http://  y dominio → deja “/uploads/…/foto.jpg” */
    if (preg_match('#^https?://[^/]+(/.+)$#i', $u, $m)) $u = $m[1];

    /* 3) Si no empieza por “/”, añádelo */
    if ($u[0] !== '/') $u = "/$u";

    /* 4) Buscar en DOCUMENT_ROOT */
    if (file_exists($DR.$u)){
        $zip->addFile($DR.$u, basename($u));
        continue;
    }

    /* 5) Buscar en /uploads relativo al proyecto */
    if (file_exists($BASE.'/'.basename($u))){
        $zip->addFile($BASE.'/'.basename($u), basename($u));
    }
}
$zip->close();

/* ZIP vacío ⇒ mensaje y limpieza */
if (!file_exists($zipPath) || (new ZipArchive)->open($zipPath) === TRUE && !filesize($zipPath)){
    @unlink($zipPath);
    @rmdir($tmp);
    die("No se encontró ningún archivo físico en el servidor.");
}

/*───────────────────────── 5) Enviar y limpiar ──────────────*/
header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=\"porte_{$porte_id}_multimedia.zip\"");
header('Content-Length: '.filesize($zipPath));
readfile($zipPath);
@unlink($zipPath);
@rmdir($tmp);
exit;
