<?php
session_start();
include 'conexion.php';

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

/* ---------- comprobación de sesión ---------- */
if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['admin_id'])) {
    header('Location: /Perfil/inicio_sesion.php'); exit();
}
$usuario_id = $_SESSION['usuario_id'];
$rol        = $_SESSION['rol'];
$admin_id   = $_SESSION['admin_id'];

/* ---------- listado de miembros (para filtro “Hecho por”) ---------- */
$miembros = [];
$sql = "SELECT id, CONCAT(nombre_usuario,' ',apellidos) AS nombre, rol
        FROM usuarios WHERE admin_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i',$admin_id);
$stmt->execute();
$r = $stmt->get_result();
while($row = $r->fetch_assoc()) $miembros[] = $row;
$stmt->close();

/* ---------- filtros dinámicos ---------- */
$cond=[]; $params=[]; $types='';

if (!empty($_GET['fecha_inicio'])){ $cond[]="f.fecha>=?"; $params[]=$_GET['fecha_inicio']; $types.='s'; }
if (!empty($_GET['fecha_fin']))   { $cond[]="f.fecha<=?"; $params[]=$_GET['fecha_fin'];   $types.='s'; }
if (!empty($_GET['tipo']))        { $cond[]="f.tipo =?";  $params[]=$_GET['tipo'];        $types.='s'; }
if (!empty($_GET['miembro']))     { $cond[]="f.usuario_id=?"; $params[]=$_GET['miembro']; $types.='i'; }

/* ---------- SELECT base ---------- */
$selectComun = "
  f.id, f.fecha, f.tipo, f.cantidad, f.foto, f.observaciones,
  IFNULL(t.tren_nombre,'Sin tren')      AS tren_nombre";

if ($rol==='administrador' || $rol==='gestor'){
    $selectComun .= ",
      IFNULL(CONCAT(u.nombre_usuario,' ',u.apellidos),'Desconocido') AS hecho_por";
}

/* ---------- consulta según rol ---------- */
$sqlFact = "
  SELECT $selectComun
  FROM facturas f
  /* usuario que hizo la factura */
  LEFT JOIN usuarios u ON f.usuario_id = u.id
  /* camionero correspondiente a ese usuario */
  LEFT JOIN camioneros cam ON cam.usuario_id = f.usuario_id
  /* último tren vigente para ese camionero antes de la fecha de la factura */
  LEFT JOIN tren_camionero tc ON tc.id = (
      SELECT id FROM tren_camionero
      WHERE camionero_id = cam.id
        AND inicio_tren_camionero <= f.fecha
      ORDER BY inicio_tren_camionero DESC, id DESC
      LIMIT 1
  )
  /* nombre del tren */
  LEFT JOIN tren t ON t.id = COALESCE(f.tren_id, tc.tren_id)
";

if ($rol==='administrador' || $rol==='gestor'){
    $sqlFact .= " WHERE u.admin_id = ? ";
    array_unshift($params,$admin_id);
    $types = 'i'.$types;
} else {          /* rol camionero */
    $sqlFact .= " WHERE f.usuario_id = ? ";
    array_unshift($params,$usuario_id);
    $types = 'i'.$types;
}

/* condiciones extra */
if ($cond) $sqlFact .= " AND ".implode(' AND ',$cond);
$sqlFact .= " ORDER BY f.fecha DESC";

/* ---------- ejecutar ---------- */
$stmt = $conn->prepare($sqlFact) or die("Error en prepare: ".$conn->error);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result_facturas = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Facturas - InterTrucker</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; ?>
<main>
  <h1>Facturas</h1>
  <a href="registro_nueva_factura.php" class="button">Registrar Nueva Factura</a>

  <!-- filtros -->
  <form method="get" action="facturas.php" class="filtros">
    <label>Fecha desde:
      <input type="date" name="fecha_inicio" value="<?=htmlspecialchars($_GET['fecha_inicio']??'')?>">
    </label>
    <label>Fecha hasta:
      <input type="date" name="fecha_fin" value="<?=htmlspecialchars($_GET['fecha_fin']??'')?>">
    </label>
    <label>Tipo:
      <select name="tipo">
        <option value="">--Todos--</option>
        <option value="ingreso" <?=($_GET['tipo']??'')==='ingreso'?'selected':''?>>Ingreso</option>
        <option value="gasto"   <?=($_GET['tipo']??'')==='gasto'  ?'selected':''?>>Gasto</option>
      </select>
    </label>
    <label>Hecho por:
      <select name="miembro">
        <option value="">--Todos--</option>
        <?php foreach($miembros as $m): ?>
          <option value="<?=$m['id']?>" <?=($_GET['miembro']??'')==$m['id']?'selected':''?>>
            <?=htmlspecialchars($m['nombre'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" class="button">Filtrar</button>
  </form>

  <!-- tabla -->
  <table>
    <thead>
      <tr>
        <th>Fecha</th><th>Tipo</th><th>Cantidad</th>
        <?php if($rol!=='camionero'): ?><th>Hecho por</th><?php endif;?>
        <th>Tren</th><th>Foto</th><th>Observaciones</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php if($result_facturas->num_rows): ?>
      <?php while($row=$result_facturas->fetch_assoc()): ?>
        <tr>
          <td><?=htmlspecialchars($row['fecha'])?></td>
          <td><?=htmlspecialchars($row['tipo'])?></td>
          <td><?=number_format($row['cantidad'],2)?> €</td>
          <?php if($rol!=='camionero'): ?>
            <td><?=htmlspecialchars($row['hecho_por'])?></td>
          <?php endif;?>
          <td><?=htmlspecialchars($row['tren_nombre'])?></td>
          <td><?=$row['foto']?"<a href='{$row['foto']}' target='_blank'>Ver Foto</a>":'Sin foto'?></td>
          <td><?=htmlspecialchars($row['observaciones']??'')?></td>
          <td><a href="detalle_factura.php?id=<?=$row['id']?>">Detalles</a></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="8">No se encontraron facturas.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
