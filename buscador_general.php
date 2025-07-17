<?php
/*  buscador_portes.php
 *  Visibilidad basada en 3 tablas: portes · seleccionados_oferta · cambios_titularidad
 *  07-may-2025
 *  Requiere: PHP ≥7.4 y las tablas del esquema InterTrucker.
 */

require_once __DIR__.'/auth.php';
require_login();
require_role(["administrador","gestor","camionero","asociado"]);
require_once 'conexion.php';
$usuario_id = (int)$_SESSION['usuario_id'];

/*--------------------------------------------------
  Filtros introducidos en el formulario
--------------------------------------------------*/
$mercancia           = $_GET['mercancia']           ?? '';
$origen              = $_GET['origen']              ?? '';
$destino             = $_GET['destino']             ?? '';
$fecha_desde         = $_GET['fecha_desde']         ?? '';
$fecha_hasta         = $_GET['fecha_hasta']         ?? '';
$estado              = $_GET['estado']              ?? '';
$expedidor_nombre    = $_GET['expedidor_nombre']    ?? '';
$destinatario_nombre = $_GET['destinatario_nombre'] ?? '';
$usuario_nombre      = $_GET['usuario_nombre']      ?? '';

/*--------------------------------------------------
  Paginación
--------------------------------------------------*/
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

/*--------------------------------------------------
  WHERE dinámico
--------------------------------------------------*/
$where  = [];
$params = [];
$types  = '';

/* 1 · Regla de visibilidad: el usuario participa */
$where[] = "
(
  p.usuario_creador_id = ?
  OR EXISTS (SELECT 1 FROM seleccionados_oferta so
             WHERE so.porte_id = p.id
               AND (so.usuario_id = ? OR so.ofertante_id = ?))
  OR EXISTS (SELECT 1 FROM cambios_titularidad ct
             WHERE ct.porte_id = p.id
               AND (ct.usuario_id_1 = ? OR ct.usuario_id_2 = ?))
)";
$types  .= 'iiiii';
$params  = array_fill(0, 5, $usuario_id);

/* 2 · Filtros rellenados */
function add(&$w,&$t,&$p,$val,$sql,$tt='s'){ if($val!==''){ $w[]=$sql; $t.=$tt; $p[]=$val; } }
add($where,$types,$params,$mercancia ? '%'.$mercancia.'%' : '','p.mercancia_descripcion LIKE ?');
add($where,$types,$params,$origen   ? '%'.$origen.'%'    : '','p.localizacion_recogida LIKE ?');
add($where,$types,$params,$destino  ? '%'.$destino.'%'   : '','p.localizacion_entrega  LIKE ?');
add($where,$types,$params,$fecha_desde,'p.fecha_recogida >= ?');
add($where,$types,$params,$fecha_hasta,'p.fecha_entrega  <= ?');
add($where,$types,$params,$estado,'p.estado_recogida_entrega = ?');
add($where,$types,$params,$expedidor_nombre   ? '%'.$expedidor_nombre.'%'    : '','ue.nombre_usuario LIKE ?');
add($where,$types,$params,$destinatario_nombre? '%'.$destinatario_nombre.'%' : '','ud.nombre_usuario LIKE ?');
add($where,$types,$params,$usuario_nombre     ? '%'.$usuario_nombre.'%'      : '','usr.todos_usuarios LIKE ?');

$whereClause = implode(' AND ',$where);

/*--------------------------------------------------
  Consulta principal
--------------------------------------------------*/
$sql = "
  SELECT DISTINCT
    p.id,
    p.mercancia_descripcion,
    p.localizacion_recogida,
    p.fecha_recogida,
    p.localizacion_entrega,
    p.fecha_entrega,
    p.estado_recogida_entrega,
    ue.nombre_usuario AS expedidor_nombre,
    ud.nombre_usuario AS destinatario_nombre,
    usr.todos_usuarios
  FROM portes p
  LEFT JOIN usuarios ue ON p.expedidor_usuario_id    = ue.id
  LEFT JOIN usuarios ud ON p.destinatario_usuario_id = ud.id

  /* usuarios que intervienen por cualquiera de las tres relaciones */
  LEFT JOIN (
      SELECT porte_id,
             GROUP_CONCAT(DISTINCT u.nombre_usuario SEPARATOR ', ') AS todos_usuarios
      FROM (
        SELECT id porte_id, usuario_creador_id uid FROM portes
        UNION ALL SELECT porte_id, usuario_id   FROM seleccionados_oferta
        UNION ALL SELECT porte_id, ofertante_id FROM seleccionados_oferta
        UNION ALL SELECT porte_id, usuario_id_1 FROM cambios_titularidad
        UNION ALL SELECT porte_id, usuario_id_2 FROM cambios_titularidad
      ) q
      JOIN usuarios u ON u.id = q.uid
      GROUP BY porte_id
  ) usr ON usr.porte_id = p.id

  WHERE $whereClause
  ORDER BY p.id DESC
  LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($sql) or die($conn->error);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8">
<title>Buscador de Portes</title>
<style>
body{margin:20px;font-family:Arial,Helvetica,sans-serif}
label{display:inline-block;width:165px;margin-top:4px}
input{margin-top:4px}
table{border-collapse:collapse;width:100%;margin-top:12px}
th,td{border:1px solid #ccc;padding:6px} th{background:#f2f2f2}
.paginacion{text-align:center;margin:15px 0}
.paginacion a{margin:0 5px;text-decoration:none}
</style></head><body>
<?php include 'header.php'; ?>

<h1>Buscador de Portes</h1>

<form method="get">
  <div><label>Mercancía:</label><input type="text" name="mercancia" value="<?php echo htmlspecialchars($mercancia); ?>"></div>
  <div><label>Origen:</label><input type="text" name="origen" value="<?php echo htmlspecialchars($origen); ?>">
       <label>Destino:</label><input type="text" name="destino" value="<?php echo htmlspecialchars($destino); ?>"></div>
  <div><label>Fecha desde:</label><input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
       <label>Fecha hasta:</label><input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>"></div>
  <div><label>Estado:</label><input type="text" name="estado" value="<?php echo htmlspecialchars($estado); ?>"></div>
  <div><label>Expedidor (nombre):</label><input type="text" name="expedidor_nombre" value="<?php echo htmlspecialchars($expedidor_nombre); ?>">
       <label>Destinatario (nombre):</label><input type="text" name="destinatario_nombre" value="<?php echo htmlspecialchars($destinatario_nombre); ?>"></div>
  <div><label>Nombre de usuario:</label><input type="text" name="usuario_nombre" value="<?php echo htmlspecialchars($usuario_nombre); ?>"></div>
  <div style="margin-top:10px;"><button type="submit">BUSCAR</button></div>
</form>

<?php if ($result && $result->num_rows > 0): ?>
<table>
<thead><tr>
<th>ID</th><th>Mercancía</th><th>Origen</th><th>F. Recogida</th>
<th>Destino</th><th>F. Entrega</th><th>Estado</th>
<th>Expedidor</th><th>Destinatario</th><th>Usuarios implicados</th><th>Acciones</th>
</tr></thead><tbody>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
  <td><?php echo $row['id']; ?></td>
  <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
  <td><?php echo htmlspecialchars($row['localizacion_recogida']); ?></td>
  <td><?php echo htmlspecialchars($row['fecha_recogida']); ?></td>
  <td><?php echo htmlspecialchars($row['localizacion_entrega']); ?></td>
  <td><?php echo htmlspecialchars($row['fecha_entrega']); ?></td>
  <td><?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></td>
  <td><?php echo htmlspecialchars($row['expedidor_nombre']    ?: '—'); ?></td>
  <td><?php echo htmlspecialchars($row['destinatario_nombre'] ?: '—'); ?></td>
  <td><?php echo htmlspecialchars($row['todos_usuarios']      ?: '—'); ?></td>
  <td>
    <form action="detalle_porte.php" method="get" style="display:inline;">
      <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
      <button type="submit">Ver</button>
    </form>
  </td>
</tr>
<?php endwhile; ?>
</tbody></table>

<div class="paginacion">
<?php if ($page > 1): ?>
  <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$page-1])); ?>">&laquo; Anterior</a>
<?php endif; ?>
<strong>Página <?php echo $page; ?></strong>
<?php if ($result->num_rows === $perPage): ?>
  <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$page+1])); ?>">Siguiente &raquo;</a>
<?php endif; ?>
</div>

<?php else: ?>
  <p>No hay portes para mostrar.</p>
<?php endif;

$stmt->close();
$conn->close();
?>
</body></html>
