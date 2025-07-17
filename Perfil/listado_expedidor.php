$baseDir = dirname(__DIR__);
require_once $baseDir.'/auth.php';
require_login();
include $baseDir.'/conexion.php';
$usuario_id = (int)$_SESSION['usuario_id'];

// Recoger filtros de la URL
$f_buscar   = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$f_estado   = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$f_desde    = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$f_hasta    = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';
$order_by   = isset($_GET['order_by']) ? $_GET['order_by'] : 'fecha_recogida';
$order_dir  = (isset($_GET['order_dir']) && strtolower($_GET['order_dir']) === 'asc') ? 'ASC' : 'DESC';

// Columnas permitidas para ordenación segura
$ordenables = [
    'fecha_recogida' => 'p.fecha_recogida',
    'estado'         => 'p.estado_recogida_entrega',
    'descripcion'    => 'p.mercancia_descripcion',
    'camionero'      => 'u.nombre_usuario'
];
$order_sql = $ordenables[$order_by] ?? 'p.fecha_recogida';

// Construir SQL dinámico con filtros seguros
$sql = "
    SELECT
        p.id AS id_porte,
        p.mercancia_descripcion,
        p.fecha_recogida,
        p.localizacion_recogida,
        p.estado_recogida_entrega,
        t.tren_nombre,
        u.cif            AS cif_camionero,
        u.nombre_usuario AS nombre_camionero
    FROM portes p
    LEFT JOIN porte_tren     pt ON p.id       = pt.porte_id
    LEFT JOIN tren           t  ON pt.tren_id = t.id
    LEFT JOIN tren_camionero tc ON pt.tren_id = tc.tren_id
    LEFT JOIN camioneros     c  ON tc.camionero_id = c.id
    LEFT JOIN usuarios       u  ON c.usuario_id    = u.id
    WHERE p.expedidor_usuario_id = ?
";

// Filtros dinámicos
$params = [$usuario_id];
$types  = "i";

if ($f_buscar !== '') {
    $sql .= " AND (
        p.mercancia_descripcion LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR t.tren_nombre LIKE ?
        OR u.cif LIKE ?
        OR u.nombre_usuario LIKE ?
    )";
    $search = '%'.$f_buscar.'%';
    $params = array_merge($params, [$search, $search, $search, $search, $search]);
    $types .= "sssss";
}
if ($f_estado !== '') {
    $sql .= " AND p.estado_recogida_entrega = ?";
    $params[] = $f_estado;
    $types .= "s";
}
if ($f_desde !== '') {
    $sql .= " AND p.fecha_recogida >= ?";
    $params[] = $f_desde;
    $types .= "s";
}
if ($f_hasta !== '') {
    $sql .= " AND p.fecha_recogida <= ?";
    $params[] = $f_hasta;
    $types .= "s";
}

$sql .= " ORDER BY $order_sql $order_dir";

$stmt = $conn->prepare($sql) or die($conn->error);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Obtener lista de estados únicos para el filtro de estado
$estados = [];
$resEstados = $conn->query("SELECT DISTINCT estado_recogida_entrega FROM portes WHERE expedidor_usuario_id = $usuario_id ORDER BY estado_recogida_entrega");
while ($r = $resEstados->fetch_assoc()) {
    if ($r['estado_recogida_entrega']) $estados[] = $r['estado_recogida_entrega'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Salida-Entrada Almacén – Expedidor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/styles.css">
<style>
main{max-width:100%;padding:var(--spacing-large);}
.tabla-scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;margin-top:var(--spacing-base)}
th,td{border:1px solid #ccc;padding:.6rem .5rem;text-align:left;font-size:15px}
th{background:#f5f7fa;cursor:pointer;}
.btn{display:inline-block;padding:.55rem 1.1rem;border-radius:8px;font-weight:600;text-decoration:none;color:#fff}
.btn-green{background:#28a745} .btn-green:hover{filter:brightness(92%)}
.btn-blue {background:#007bff} .btn-blue:hover {filter:brightness(92%)}
.btn-gray {background:#6c757d} .btn-gray:hover{filter:brightness(92%)}
.filtros label{margin-right:7px;}
.filtros input[type="text"], .filtros input[type="date"], .filtros select {margin-right:10px;}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
  <h1 style="text-align:center;margin:0;">Salida-Entrada Almacén</h1>

  <div style="text-align:center;margin:var(--spacing-large) 0;">
      <a href="listado_expedidor.php"    class="btn btn-green" style="margin-right:20px">Expedidor</a>
      <a href="listado_destinatario.php" class="btn btn-blue">Destinatario</a>
  </div>

  <h2 style="text-align:center;margin-bottom:var(--spacing-base);">
      Listado de Portes que Salen (Soy Expedidor)
  </h2>

  <!-- FILTROS -->
  <form class="filtros" method="get" style="margin-bottom:18px;text-align:center;">
    <label>Buscar:</label>
    <input type="text" name="buscar" value="<?= htmlspecialchars($f_buscar) ?>" placeholder="Descripción, tren, camionero...">
    <label>Estado:</label>
    <select name="estado">
      <option value="">-- Todos --</option>
      <?php foreach($estados as $est): ?>
        <option value="<?= htmlspecialchars($est) ?>" <?= $f_estado===$est?'selected':'' ?>><?= htmlspecialchars($est) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Fecha desde:</label>
    <input type="date" name="fecha_desde" value="<?= htmlspecialchars($f_desde) ?>">
    <label>hasta:</label>
    <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($f_hasta) ?>">
    <button type="submit" class="btn btn-blue">Filtrar</button>
    <a href="listado_expedidor.php" class="btn btn-gray" style="margin-left:7px;">Limpiar</a>
  </form>

  <?php if ($result->num_rows > 0): ?>
    <div class="tabla-scroll">
      <table>
        <thead>
          <tr>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET,['order_by'=>'descripcion','order_dir'=>(($order_by=='descripcion'&&$order_dir=='ASC')?'DESC':'ASC')])) ?>">
                Descripción <?= $order_by=='descripcion' ? ($order_dir=='ASC'?'▲':'▼') : '' ?>
              </a>
            </th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET,['order_by'=>'fecha_recogida','order_dir'=>(($order_by=='fecha_recogida'&&$order_dir=='ASC')?'DESC':'ASC')])) ?>">
                Fecha Recogida <?= $order_by=='fecha_recogida' ? ($order_dir=='ASC'?'▲':'▼') : '' ?>
              </a>
            </th>
            <th>Local. Recogida</th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET,['order_by'=>'estado','order_dir'=>(($order_by=='estado'&&$order_dir=='ASC')?'DESC':'ASC')])) ?>">
                Estado Recogida <?= $order_by=='estado' ? ($order_dir=='ASC'?'▲':'▼') : '' ?>
              </a>
            </th>
            <th>Tren</th>
            <th>CIF Camionero</th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET,['order_by'=>'camionero','order_dir'=>(($order_by=='camionero'&&$order_dir=='ASC')?'DESC':'ASC')])) ?>">
                Nombre Camionero <?= $order_by=='camionero' ? ($order_dir=='ASC'?'▲':'▼') : '' ?>
              </a>
            </th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['mercancia_descripcion']) ?></td>
            <td><?= htmlspecialchars($row['fecha_recogida']) ?></td>
            <td><?= htmlspecialchars($row['localizacion_recogida']) ?></td>
            <td><?= htmlspecialchars($row['estado_recogida_entrega'] ?? 'Desconocido') ?></td>
            <td><?= $row['tren_nombre'] ? htmlspecialchars($row['tren_nombre']) : 'Sin Tren' ?></td>
            <td><?= $row['cif_camionero'] ? htmlspecialchars($row['cif_camionero']) : 'Pendiente' ?></td>
            <td><?= $row['nombre_camionero'] ? htmlspecialchars($row['nombre_camionero']) : 'Pendiente' ?></td>
            <td class="acciones">
              <a href="recogida_entrega_vista.php?porte_id=<?= $row['id_porte'] ?>&tipo_evento=recogida"
                 class="btn btn-gray">Ver Recogida</a>
              <a href="detalle_porte.php?id=<?= $row['id_porte'] ?>"
                 class="btn btn-gray">Detalles</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p style="text-align:center;">No tienes portes como expedidor.</p>
  <?php endif; ?>
</main>

<?php $conn->close(); ?>
</body>
</html>
