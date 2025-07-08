<?php
session_start();
include 'conexion.php'; // Ajusta a tu archivo de conexión

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar admin_id y usuario_id
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['usuario_id'])) {
    die("Error: Faltan admin_id o usuario_id en la sesión.");
}
$admin_id   = $_SESSION['admin_id'];
$usuario_id = $_SESSION['usuario_id'];

/***************************************************************************************
 * (1) OBTENER EL NOMBRE DEL ADMINISTRADOR (opcional)
 ***************************************************************************************/
$query_admin = "SELECT nombre_usuario FROM usuarios WHERE id = ?";
$stmt_admin = $conn->prepare($query_admin);
if (!$stmt_admin) {
    die('Error en query_admin: ' . $conn->error);
}
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$res_admin = $stmt_admin->get_result();
$admin_data = $res_admin->fetch_assoc();
$admin_nombre = $admin_data ? $admin_data['nombre_usuario'] : 'Administrador';
$stmt_admin->close();

/***************************************************************************************
 * (2) Comprobar si hay gestores => mostrar submenú
 ***************************************************************************************/
$query_gestores = "
    SELECT COUNT(*) AS total_gestores
    FROM usuarios
    WHERE rol='gestor'
      AND admin_id=?
";
$stmt_gestores = $conn->prepare($query_gestores);
if (!$stmt_gestores) {
    die('Error en query_gestores: ' . $conn->error);
}
$stmt_gestores->bind_param("i", $admin_id);
$stmt_gestores->execute();
$res_gestores = $stmt_gestores->get_result();
$gest = $res_gestores->fetch_assoc();
$total_gestores = $gest ? $gest['total_gestores'] : 0;
$stmt_gestores->close();
$mostrar_submenu = ($total_gestores > 0);

/***************************************************************************************
 * (2.1) FILTROS => Recibidas y Aceptadas
 ***************************************************************************************/

// Filtros “Recibidas”
$buscar_rec = isset($_GET['buscar_rec']) ? trim($_GET['buscar_rec']) : '';
$fd_rec = isset($_GET['fecha_desde_rec']) ? trim($_GET['fecha_desde_rec']) : '';
$fh_rec = isset($_GET['fecha_hasta_rec']) ? trim($_GET['fecha_hasta_rec']) : '';

$filtroSQL = " ov.estado_oferta='pendiente' AND ov.usuario_id=? ";
$param = [$usuario_id];
$type = "i";

if($buscar_rec !== ''){
    $filtroSQL .= " 
        AND (
            p.mercancia_descripcion     LIKE ?
            OR p.localizacion_recogida  LIKE ?
            OR p.localizacion_entrega   LIKE ?
            OR u_ofert.nombre_usuario   LIKE ?
        ) 
    ";
    $like_rec = "%$buscar_rec%";
    $param[] = $like_rec; 
    $param[] = $like_rec; 
    $param[] = $like_rec;
    $param[] = $like_rec;   
    $type .= "ssss";        
}
if($fd_rec !== ''){
    $filtroSQL .= " AND p.fecha_recogida >= ? ";
    $param[] = $fd_rec;
    $type .= "s";
}
if($fh_rec !== ''){
    $filtroSQL .= " AND p.fecha_recogida <= ? ";
    $param[] = $fh_rec;
    $type .= "s";
}

// Filtros “Aceptadas”
$buscar_acept = isset($_GET['buscar_acept']) ? trim($_GET['buscar_acept']) : '';
$fd_acept = isset($_GET['fecha_desde_acept']) ? trim($_GET['fecha_desde_acept']) : '';
$fh_acept = isset($_GET['fecha_hasta_acept']) ? trim($_GET['fecha_hasta_acept']) : '';

$filtroSQL_acept = "
    ov.estado_oferta='asignado' 
    AND ov.usuario_id=?
    AND NOT EXISTS (
      SELECT 1
      FROM ofertas_varios ov2
      WHERE ov2.porte_id   = ov.porte_id
        AND ov2.usuario_id = ov.usuario_id
        AND ov2.fecha_oferta > ov.fecha_oferta
    )
";
$paramAce = [$usuario_id];
$typeAce  = "i";

if($buscar_acept !== ''){
    $filtroSQL_acept .= "
        AND (
            p.mercancia_descripcion     LIKE ?
            OR p.localizacion_recogida  LIKE ?
            OR p.localizacion_entrega   LIKE ?
            OR u_ofert.nombre_usuario   LIKE ?
        )
    ";
    $like_acept = "%$buscar_acept%";
    $paramAce[] = $like_acept; 
    $paramAce[] = $like_acept; 
    $paramAce[] = $like_acept;
    $paramAce[] = $like_acept; 
    $typeAce .= "ssss";         
}
if($fd_acept !== ''){
    $filtroSQL_acept .= " AND p.fecha_recogida >= ? ";
    $paramAce[] = $fd_acept;
    $typeAce .= "s";
}
if($fh_acept !== ''){
    $filtroSQL_acept .= " AND p.fecha_recogida <= ? ";
    $paramAce[] = $fh_acept;
    $typeAce .= "s";
}

/***************************************************************************************
 * (2.2) FILTROS => Reofrecidas, Reasignados, En Tren
 ***************************************************************************************/

// ----------- A) Filtros Reofrecidas ------------
$buscar_reof = isset($_GET['buscar_reof']) ? trim($_GET['buscar_reof']) : '';
$fd_reof = isset($_GET['fecha_desde_reof']) ? trim($_GET['fecha_desde_reof']) : '';
$fh_reof = isset($_GET['fecha_hasta_reof']) ? trim($_GET['fecha_hasta_reof']) : '';

$filtroSQL_reof = "
    so.usuario_id=?
    AND EXISTS(
      SELECT 1 FROM ofertas_varios ov3
      WHERE ov3.porte_id=so.porte_id
        AND ov3.fecha_oferta>so.fecha_seleccion
    )
    AND NOT EXISTS(
      SELECT 1 FROM seleccionados_oferta so2
      WHERE so2.porte_id=so.porte_id
        AND so2.fecha_seleccion>so.fecha_seleccion
        AND so2.id!=so.id
    )
    AND NOT EXISTS(
      SELECT 1 FROM porte_tren pt
      WHERE pt.porte_id=so.porte_id
        AND pt.inicio_tren>so.fecha_seleccion
    )
";
$paramReof = [$usuario_id]; 
$typeReof  = "i";

$paramReof[] = $usuario_id; 
$paramReof[] = $usuario_id; 
$typeReof    .= "ii";

if($buscar_reof !== ''){
    $filtroSQL_reof .= "
      AND (
        p.mercancia_descripcion LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR p.localizacion_entrega LIKE ?
        OR u_ofert.nombre_usuario LIKE ?
      )
    ";
    $like_reof = "%$buscar_reof%";
    $paramReof[] = $like_reof; 
    $paramReof[] = $like_reof; 
    $paramReof[] = $like_reof;
    $paramReof[] = $like_reof;
    $typeReof    .= "ssss";
}
if($fd_reof !== ''){
    $filtroSQL_reof .= " AND p.fecha_recogida >= ? ";
    $paramReof[] = $fd_reof;
    $typeReof   .= "s";
}
if($fh_reof !== ''){
    $filtroSQL_reof .= " AND p.fecha_recogida <= ? ";
    $paramReof[] = $fh_reof;
    $typeReof   .= "s";
}

// ----------- B) Filtros Reasignados ------------
$buscar_reasig = isset($_GET['buscar_reasig']) ? trim($_GET['buscar_reasig']) : '';
$fd_reasig = isset($_GET['fecha_desde_reasig']) ? trim($_GET['fecha_desde_reasig']) : '';
$fh_reasig = isset($_GET['fecha_hasta_reasig']) ? trim($_GET['fecha_hasta_reasig']) : '';

$filtroSQL_reasig = "
    so1.usuario_id=?
    AND NOT EXISTS(
      SELECT 1 FROM porte_tren pt
      WHERE pt.porte_id=so1.porte_id
        AND pt.inicio_tren>so1.fecha_seleccion
    )
";
$paramReasig = [$usuario_id];
$typeReasig  = "i";

if($buscar_reasig !== ''){
    $filtroSQL_reasig .= "
      AND (
        p.mercancia_descripcion LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR p.localizacion_entrega LIKE ?
        OR u_reasig.nombre_usuario LIKE ?
        OR u_resp.nombre_usuario LIKE ?
      )
    ";
    $like_reasig = "%$buscar_reasig%";
    $paramReasig[] = $like_reasig; 
    $paramReasig[] = $like_reasig; 
    $paramReasig[] = $like_reasig; 
    $paramReasig[] = $like_reasig; 
    $paramReasig[] = $like_reasig; 
    $typeReasig   .= "sssss";
}
if($fd_reasig !== ''){
    $filtroSQL_reasig .= " AND p.fecha_recogida >= ? ";
    $paramReasig[] = $fd_reasig;
    $typeReasig   .= "s";
}
if($fh_reasig !== ''){
    $filtroSQL_reasig .= " AND p.fecha_recogida <= ? ";
    $paramReasig[] = $fh_reasig;
    $typeReasig   .= "s";
}

// ----------- C) Filtros Tren ------------
$buscar_tren = isset($_GET['buscar_tren']) ? trim($_GET['buscar_tren']) : '';
$fd_tren = isset($_GET['fecha_desde_tren']) ? trim($_GET['fecha_desde_tren']) : '';
$fh_tren = isset($_GET['fecha_hasta_tren']) ? trim($_GET['fecha_hasta_tren']) : '';

$filtroSQL_tren = "
    pt.usuario_id=?
    AND so.usuario_id=?
";
$paramTren = [$usuario_id, $usuario_id];
$typeTren  = "ii";

if($buscar_tren !== '') {
    $filtroSQL_tren .= "
      AND (
        p.mercancia_descripcion LIKE ?
        OR p.localizacion_recogida LIKE ?
        OR p.localizacion_entrega LIKE ?
        OR u_tren.nombre_usuario LIKE ?
        OR t.tren_nombre LIKE ?
      )
    ";
    $like_tren = "%$buscar_tren%";
    $paramTren[] = $like_tren;
    $paramTren[] = $like_tren;
    $paramTren[] = $like_tren;
    $paramTren[] = $like_tren;
    $paramTren[] = $like_tren;
    $typeTren .= "sssss";
}
if($fd_tren !== ''){
    $filtroSQL_tren .= " AND p.fecha_recogida >= ? ";
    $paramTren[] = $fd_tren;
    $typeTren   .= "s";
}
if($fh_tren !== ''){
    $filtroSQL_tren .= " AND p.fecha_recogida <= ? ";
    $paramTren[] = $fh_tren;
    $typeTren   .= "s";
}

/***************************************************************************************
 * (3) CONSULTAS => Recibidas, Aceptadas, Reofrecidas, Reasignados, En Tren
 ***************************************************************************************/

/* (3.1) Ofertas Recibidas => Pendientes */
$sql_rec = "
SELECT ov.id AS oferta_id,
       ov.porte_id,
       ov.precio,
       p.mercancia_descripcion,
       p.localizacion_recogida,
       p.fecha_recogida,
       p.localizacion_entrega,
       p.fecha_entrega,
       u_ofert.nombre_usuario AS ofertante_nombre,
       u_ofert.nombre_empresa AS empresa_ofertante
FROM ofertas_varios ov
JOIN portes p
  ON ov.porte_id=p.id
LEFT JOIN usuarios u_ofert
  ON ov.ofertante_id=u_ofert.id
WHERE $filtroSQL
ORDER BY ov.fecha_oferta DESC
";
$stmt_rec = $conn->prepare($sql_rec);
if(!$stmt_rec){ die("Error en Recibidas: ".$conn->error); }
$stmt_rec->bind_param($type, ...$param);
$stmt_rec->execute();
$res_rec = $stmt_rec->get_result();
$ofertasRecibidas = [];
while($row = $res_rec->fetch_assoc()){
    $ofertasRecibidas[] = $row;
}
$res_rec->free();
$stmt_rec->close();
$num_ofertas_recibidas = count($ofertasRecibidas);

/* (3.2) Ofertas Aceptadas */
$sql_acept = "
SELECT 
  ov.id AS oferta_id,
  ov.porte_id,
  ov.precio,
  p.mercancia_descripcion,
  p.localizacion_recogida,
  p.fecha_recogida,
  p.localizacion_entrega,
  p.fecha_entrega,
  u_ofert.nombre_usuario AS ofertante_nombre,
  u_ofert.nombre_empresa AS empresa_ofertante,
  ov.fecha_oferta AS fecha_seleccion
FROM ofertas_varios ov
JOIN portes p
  ON ov.porte_id=p.id
LEFT JOIN usuarios u_ofert
  ON ov.ofertante_id=u_ofert.id
WHERE $filtroSQL_acept
ORDER BY ov.fecha_oferta DESC
";
$stmt_acept = $conn->prepare($sql_acept);
if(!$stmt_acept){ die("Error en Aceptadas: ".$conn->error); }
$stmt_acept->bind_param($typeAce, ...$paramAce);
$stmt_acept->execute();
$res_acept = $stmt_acept->get_result();
$ofertasAceptadas = [];
while($row=$res_acept->fetch_assoc()){
    $ofertasAceptadas[] = $row;
}
$res_acept->free();
$stmt_acept->close();
$num_ofertas_aceptadas = count($ofertasAceptadas);

/* (3.3) Ofertas Reofrecidas => con $filtroSQL_reof */
$query_reofrecidas = "
    SELECT
      so.porte_id,
      so.fecha_seleccion,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      ov.ofertante_id,
      u_ofert.nombre_usuario AS ofertante_nombre,
      u_ofert.nombre_empresa AS empresa_ofertante
    FROM seleccionados_oferta so
    JOIN portes p ON so.porte_id=p.id
    LEFT JOIN (
      SELECT porte_id, MAX(fecha_oferta) AS ultima
      FROM ofertas_varios
      WHERE usuario_id=?
      GROUP BY porte_id
    ) last_ov
      ON so.porte_id=last_ov.porte_id
    LEFT JOIN ofertas_varios ov
      ON ov.porte_id=so.porte_id
     AND ov.fecha_oferta=last_ov.ultima
     AND ov.usuario_id=?
    LEFT JOIN usuarios u_ofert
      ON ov.ofertante_id=u_ofert.id
    WHERE $filtroSQL_reof
    ORDER BY so.fecha_seleccion DESC
";
$stmt_reof = $conn->prepare($query_reofrecidas);
if(!$stmt_reof){ die("Error en Reofrecidas: ".$conn->error); }
$stmt_reof->bind_param($typeReof, ...$paramReof);
$stmt_reof->execute();

$res_reof = $stmt_reof->get_result();
$portesReofrecidos = [];
while($rw = $res_reof->fetch_assoc()){
    $portesReofrecidos[] = $rw;
}
$res_reof->free();
$stmt_reof->close();
$num_portes_reofrecidos = count($portesReofrecidos);

/* (3.4) Portes Reasignados => con $filtroSQL_reasig
   ==> AÑADIMOS p.estado_recogida_entrega al SELECT
*/
$query_reasignados = "
    SELECT
      so1.porte_id,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      p.estado_recogida_entrega,
      so2.usuario_id AS reasignado_a,
      so2.ofertante_id AS responsable,
      so2.fecha_seleccion,
      u_reasig.nombre_usuario AS reasignado_a_nombre,
      u_resp.nombre_usuario AS responsable_nombre
    FROM seleccionados_oferta so1
    JOIN seleccionados_oferta so2
      ON so1.porte_id=so2.porte_id
     AND so1.usuario_id=so2.ofertante_id
     AND so2.fecha_seleccion>so1.fecha_seleccion
     AND NOT EXISTS(
       SELECT 1
       FROM seleccionados_oferta so_inner
       WHERE so_inner.porte_id=so1.porte_id
         AND so_inner.fecha_seleccion>so1.fecha_seleccion
         AND so_inner.fecha_seleccion<so2.fecha_seleccion
     )
    JOIN portes p ON so1.porte_id=p.id
    LEFT JOIN usuarios u_reasig ON so2.usuario_id=u_reasig.id
    LEFT JOIN usuarios u_resp   ON so2.ofertante_id=u_resp.id
    WHERE $filtroSQL_reasig
";
$stmt_ras = $conn->prepare($query_reasignados);
if(!$stmt_ras){ die("Error en Reasignados: ".$conn->error); }
$stmt_ras->bind_param($typeReasig, ...$paramReasig);
$stmt_ras->execute();

$res_ras = $stmt_ras->get_result();
$portesReasignados = [];
while($rw=$res_ras->fetch_assoc()){
    $portesReasignados[] = $rw;
}
$res_ras->free();
$stmt_ras->close();
$num_portes_reasignados = count($portesReasignados);

/* (3.5) Portes en Tren => con $filtroSQL_tren
   ==> AÑADIMOS p.estado_recogida_entrega al SELECT
*/
$query_tren = "
    SELECT
      pt.id AS tren_id,
      p.id AS porte_id,
      p.mercancia_descripcion,
      p.localizacion_recogida,
      p.fecha_recogida,
      p.localizacion_entrega,
      p.fecha_entrega,
      p.estado_recogida_entrega,
      pt.inicio_tren,
      pt.usuario_id AS tren_usuario_id,
      u_tren.nombre_usuario AS tren_usuario_nombre,
      t.tren_nombre AS tren_nombre
    FROM porte_tren pt
    JOIN portes p ON pt.porte_id=p.id
    JOIN seleccionados_oferta so ON pt.porte_id=so.porte_id
    LEFT JOIN usuarios u_tren ON pt.usuario_id=u_tren.id
    JOIN tren t ON pt.tren_id = t.id
    WHERE $filtroSQL_tren
";
$stmt_trn = $conn->prepare($query_tren);
if(!$stmt_trn){ die("Error en Tren: ".$conn->error); }
$stmt_trn->bind_param($typeTren, ...$paramTren);
$stmt_trn->execute();

$res_trn = $stmt_trn->get_result();
$portesEnTren = [];
while($rw=$res_trn->fetch_assoc()){
    $portesEnTren[] = $rw;
}
$res_trn->free();
$stmt_trn->close();
$num_portes_en_tren = count($portesEnTren);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portes Nuevos Recibidos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles.css">
  <script>
    function showSection(sectionId){
      const allTabs=document.querySelectorAll('.tabs li');
      allTabs.forEach(t=>t.classList.remove('active'));

      const allContent=document.querySelectorAll('.tab-content');
      allContent.forEach(c=>c.classList.remove('active'));

      const selTab=document.querySelector(`.tabs li[data-section="${sectionId}"]`);
      if(selTab) selTab.classList.add('active');

      const selContent=document.getElementById(sectionId);
      if(selContent) selContent.classList.add('active');
    }

    function confirmarAceptar(){
      return confirm("¿Estás seguro de ACEPTAR la(s) oferta(s)?");
    }
    function confirmarRechazar(){
      return confirm("¿Estás seguro de RECHAZAR la(s) oferta(s)?");
    }

    function toggleTodos(chk){
      const checks = document.querySelectorAll('input[name="oferta_id[]"]');
      checks.forEach(c => c.checked = chk.checked);
    }

    function limpiarFiltros(tabId){
      window.location.href = 'portes_nuevos_recibidos.php?tab=' + tabId;
    }

    document.addEventListener('DOMContentLoaded',function(){
      const collapsibles=document.querySelectorAll('.collapsible');
      collapsibles.forEach(btn=>{
        btn.addEventListener('click',function(){
          this.classList.toggle('active');
          const c=this.nextElementSibling;
          c.style.display=(c.style.display==="block")?"none":"block";
        });
      });

      const urlParams = new URLSearchParams(window.location.search);
      const activeTab = urlParams.get('tab') || 'recibidasTab';

      showSection(activeTab);
    });
  </script>
</head>
<body>
<?php include 'header.php'; ?>

<main>
  <h1>PORTES NUEVOS</h1>
  <nav class="nav-margin">
    <ul class="nav-list">
      <li>
        <a href="portes_nuevos_recibidos.php" class="nav-link-btn btn-green active"
          >
          Recibidos
        </a>
      </li>
      <li>
        <a href="portes_nuevos_propios.php" class="nav-link-btn btn-blue"
          >
          Creados
        </a>
      </li>
    </ul>
  </nav>

  <?php if($mostrar_submenu): ?>
    <h2 class="nav-subtitle">Submenú:</h2>
    <nav class="nav-margin">
      <ul class="nav-list">
        <li>
          <a href="portes_nuevos_recibidos.php" class="nav-link-btn btn-green active"
            >
            Mios
          </a>
        </li>
        <li>
          <a href="portes_nuevos_recibidos_todos.php" class="nav-link-btn btn-blue"
            >
            Compañeros
          </a>
        </li>
      </ul>
    </nav>
    <br>
  <?php endif; ?>

  <!-- VERSIÓN MÓVIL (cards) -->
  <div class="mobile-only m-16">
    <button class="collapsible">
      Ofertas Recibidas (<?php echo $num_ofertas_recibidas; ?>)
    </button>
    <div class="content">
      <?php if($num_ofertas_recibidas>0): ?>
        <?php foreach($ofertasRecibidas as $row): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Origen:</strong> 
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Destino:</strong> 
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <p><strong>Precio:</strong> <?php echo htmlspecialchars($row['precio']); ?></p>
            <p><strong>Ofrece:</strong> <?php echo htmlspecialchars($row['empresa_ofertante']); ?></p>
            <div class="actions">
              <form action="aceptar_oferta.php" method="POST" onsubmit="return confirmarAceptar();">
                <input type="hidden" name="oferta_id" value="<?php echo $row['oferta_id']; ?>">
                <input type="hidden" name="porte_id"  value="<?php echo $row['porte_id']; ?>">
                <input type="hidden" name="accion"    value="aceptar">
                <button class="btn-blue btn-accion">Aceptar</button>
              </form>
              <form action="rechazar_oferta.php" method="POST" onsubmit="return confirmarRechazar();">
                <input type="hidden" name="oferta_id" value="<?php echo $row['oferta_id']; ?>">
                <button class="btn-red btn-accion">Rechazar</button>
              </form>
              <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>">
                <button class="btn-gray btn-accion">Detalles</button>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay ofertas recibidas (pendientes).</p>
      <?php endif; ?>
    </div>

    <button class="collapsible">
      Ofertas Aceptadas (<?php echo $num_ofertas_aceptadas; ?>)
    </button>
    <div class="content">
      <?php if($num_ofertas_aceptadas>0): ?>
        <?php foreach($ofertasAceptadas as $row): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Origen:</strong> 
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Destino:</strong> 
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <p><strong>Fecha Selección:</strong> <?php echo htmlspecialchars($row['fecha_seleccion']); ?></p>
            <p><strong>Ofrece:</strong> <?php echo htmlspecialchars($row['empresa_ofertante']); ?></p>
            <div class="actions">
              <a href="hacer_porte.php?porte_id=<?php echo $row['porte_id']; ?>">
                <button class="btn-green btn-accion">Hacer Porte</button>
              </a>
              <a href="hacer_oferta.php?porte_id=<?php echo $row['porte_id']; ?>">
                <button class="btn-blue btn-accion">Ofrecer</button>
              </a>
              <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>">
                <button class="btn-gray btn-accion">Detalles</button>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay ofertas aceptadas.</p>
      <?php endif; ?>
    </div>

    <button class="collapsible">
      Ofertas Reofrecidas (<?php echo $num_portes_reofrecidos; ?>)
    </button>
    <div class="content">
      <?php if($num_portes_reofrecidos > 0): ?>
        <?php foreach($portesReofrecidos as $row): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Origen:</strong>
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Destino:</strong>
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <p><strong>Fecha Selección:</strong> <?php echo htmlspecialchars($row['fecha_seleccion']); ?></p>
            <p><strong>Último ofertante:</strong> <?php echo htmlspecialchars($row['empresa_ofertante']); ?></p>
            <div class="actions">
              <a href="hacer_oferta.php?porte_id=<?php echo $row['porte_id']; ?>">
                <button class="btn-blue btn-accion">Reofrecer</button>
              </a>
              <a href="hacer_porte.php?porte_id=<?php echo $row['porte_id']; ?>">
                <button class="btn-green btn-accion">Hacer Porte</button>
              </a>
              <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>">
                <button class="btn-gray btn-accion">Detalles</button>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay ofertas reofrecidas.</p>
      <?php endif; ?>
    </div>

    <button class="collapsible">
      Portes Reasignados (<?php echo $num_portes_reasignados; ?>)
    </button>
    <div class="content">
      <?php if($num_portes_reasignados > 0): ?>
        <?php foreach($portesReasignados as $row): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Origen:</strong>
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Destino:</strong>
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></p>
            <p><strong>Reasignado a:</strong> <?php echo htmlspecialchars($row['reasignado_a_nombre']); ?></p>
            <p><strong>Responsable:</strong> <?php echo htmlspecialchars($row['responsable_nombre']); ?></p>
            <p><strong>Fecha:</strong> <?php echo htmlspecialchars($row['fecha_seleccion']); ?></p>
            <div class="actions">
              <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>">
                <button class="btn-gray btn-accion">Detalles</button>
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tipo_evento=recogida">
                <button class="btn-yellow btn-accion">Recogida</button>
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tipo_evento=entrega">
                <button class="btn-cyan btn-accion">Entrega</button>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay portes reasignados.</p>
      <?php endif; ?>
    </div>

    <button class="collapsible">
      Portes en Tren (<?php echo $num_portes_en_tren; ?>)
    </button>
    <div class="content">
      <?php if($num_portes_en_tren > 0): ?>
        <?php foreach($portesEnTren as $row): ?>
          <div class="card">
            <h3>Mercancía: <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></h3>
            <p><strong>Origen:</strong>
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> - 
               <?php echo htmlspecialchars($row['fecha_recogida']); ?></p>
            <p><strong>Destino:</strong>
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?> - 
               <?php echo htmlspecialchars($row['fecha_entrega']); ?></p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></p>
            <p><strong>Tren:</strong> <?php echo htmlspecialchars($row['tren_nombre']); ?></p>
            <p><strong>Asignado por:</strong> <?php echo htmlspecialchars($row['tren_usuario_nombre']); ?></p>
            <p><strong>Inicio Tren:</strong> <?php echo htmlspecialchars($row['inicio_tren']); ?></p>
            <div class="actions">
              <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>">
                <button class="btn-gray btn-accion">Detalles</button>
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tipo_evento=recogida">
                <button class="btn-yellow btn-accion">Recogida</button>
              </a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tipo_evento=entrega">
                <button class="btn-cyan btn-accion">Entrega</button>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay portes en tren.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- VERSIÓN ESCRITORIO (tablas) -->
  <div class="desktop-only">
    <ul class="tabs">
      <li data-section="recibidasTab" onclick="showSection('recibidasTab')">
        Ofertas Recibidas (<?php echo $num_ofertas_recibidas; ?>)
      </li>
      <li data-section="aceptadasTab" onclick="showSection('aceptadasTab')">
        Ofertas Aceptadas (<?php echo $num_ofertas_aceptadas; ?>)
      </li>
      <li data-section="reofTab" onclick="showSection('reofTab')">
        Ofertas Reofrecidas (<?php echo $num_portes_reofrecidos; ?>)
      </li>
      <li data-section="reasignadosTab" onclick="showSection('reasignadosTab')">
        Portes Reasignados (<?php echo $num_portes_reasignados; ?>)
      </li>
      <li data-section="trenTab" onclick="showSection('trenTab')">
        Portes en Tren (<?php echo $num_portes_en_tren; ?>)
      </li>
    </ul>

    <!-- Ofertas Recibidas -->
    <div id="recibidasTab" class="tab-content">
      <h2>Ofertas Recibidas (Pendientes)</h2>
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos.php">
          <input type="hidden" name="tab" value="recibidasTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_rec" value="<?php echo htmlspecialchars($buscar_rec); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_rec" value="<?php echo htmlspecialchars($fd_rec); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_rec" value="<?php echo htmlspecialchars($fh_rec); ?>">
          <button type="submit" class="btn-accion btn-green">
            Filtrar
          </button>
          <button type="button"
                  class="btn-accion btn-green"
                 
                  onclick="limpiarFiltros('recibidasTab');">
            Limpiar
          </button>
        </form>
      </div>

      <form method="POST" action=""
            onsubmit="return confirm('¿Seguro de aplicar la acción a las ofertas seleccionadas?');">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this)"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Origen</th>
              <th>Destino</th>
              <th>Precio</th>
              <th>Ofrece</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if($num_ofertas_recibidas>0): ?>
            <?php foreach($ofertasRecibidas as $row): ?>
              <tr>
                <td>
                  <input type="checkbox" name="oferta_id[]" value="<?php echo $row['oferta_id']; ?>">
                  <input type="hidden" name="porte_id_<?php echo $row['oferta_id']; ?>" value="<?php echo $row['porte_id']; ?>">
                </td>
                <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
                <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
                <td>
                  <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                  <?php echo htmlspecialchars($row['fecha_recogida']); ?>
                </td>
                <td>
                  <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                  <?php echo htmlspecialchars($row['fecha_entrega']); ?>
                </td>
                <td><?php echo htmlspecialchars($row['precio']); ?></td>
                <td><?php echo htmlspecialchars($row['empresa_ofertante']); ?></td>
                <td class="acciones">
                  <form action="aceptar_oferta.php" method="POST" onsubmit="return confirmarAceptar();">
                    <input type="hidden" name="oferta_id" value="<?php echo $row['oferta_id']; ?>">
                    <input type="hidden" name="porte_id"  value="<?php echo $row['porte_id']; ?>">
                    <input type="hidden" name="accion"    value="aceptar">
                    <button class="btn-accion btn-green">
                      Aceptar
                    </button>
                  </form>
                  <form action="rechazar_oferta.php" method="POST" onsubmit="return confirmarRechazar();">
                    <input type="hidden" name="oferta_id" value="<?php echo $row['oferta_id']; ?>">
                    <button class="btn-accion btn-green">
                      Rechazar
                    </button>
                  </form>
                  <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>"
                     target="_blank"
                     class="btn-accion btn-gray"
                    >
                    Detalles
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="8">No hay ofertas recibidas (pendientes).</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
        <?php if($num_ofertas_recibidas>0): ?>
          <br>
          <button type="submit" name="accion_global" value="aceptar_multiple"
                  class="btn-accion btn-green">
            Aceptar seleccionadas
          </button>
          <button type="submit" name="accion_global" value="rechazar_multiple"
                  class="btn-accion btn-green">
            Rechazar seleccionadas
          </button>
        <?php endif; ?>
      </form>
    </div>

    <!-- Ofertas Aceptadas -->
    <div id="aceptadasTab" class="tab-content">
      <h2>Ofertas Aceptadas</h2>
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos.php">
          <input type="hidden" name="tab" value="aceptadasTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_acept" value="<?php echo htmlspecialchars($buscar_acept); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_acept" value="<?php echo htmlspecialchars($fd_acept); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_acept" value="<?php echo htmlspecialchars($fh_acept); ?>">
          <button type="submit" class="btn-accion btn-green">
            Filtrar
          </button>
          <button type="button"
                  class="btn-accion btn-green"
                 
                  onclick="limpiarFiltros('aceptadasTab');">
            Limpiar
          </button>
        </form>
      </div>

      <form method="POST" action="hacer_porte_multiple.php"
            onsubmit="return confirm('¿Seguro de Hacer Porte con los seleccionados?');">
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="toggleTodos(this)"></th>
              <th>ID</th>
              <th>Mercancía</th>
              <th>Origen</th>
              <th>Destino</th>
              <th>Fecha Selección</th>
              <th>Ofrece</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if($num_ofertas_aceptadas>0): ?>
            <?php foreach($ofertasAceptadas as $row): ?>
              <tr>
                <td>
                  <input type="checkbox" name="oferta_id[]" value="<?php echo $row['oferta_id']; ?>">
                  <input type="hidden" name="porte_id_<?php echo $row['oferta_id']; ?>" value="<?php echo $row['porte_id']; ?>">
                </td>
                <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
                <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
                <td>
                  <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                  <?php echo htmlspecialchars($row['fecha_recogida']); ?>
                </td>
                <td>
                  <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                  <?php echo htmlspecialchars($row['fecha_entrega']); ?>
                </td>
                <td><?php echo htmlspecialchars($row['fecha_seleccion']); ?></td>
                <td><?php echo htmlspecialchars($row['empresa_ofertante']); ?></td>
                <td class="acciones">
                  <a href="hacer_porte.php?porte_id=<?php echo $row['porte_id']; ?>"
                     class="btn-accion btn-green"
                    >
                    Hacer Porte
                  </a>
                  <a href="hacer_oferta.php?porte_id=<?php echo $row['porte_id']; ?>"
                     class="btn-accion btn-blue"
                    >
                    Ofrecer
                  </a>
                  <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>"
                     target="_blank"
                     class="btn-accion btn-gray"
                    >
                    Detalles
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="8">No hay ofertas aceptadas.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
        <?php if($num_ofertas_aceptadas>0): ?>
          <br>
          <button type="submit" name="accion_global" value="hacer_porte_multiple"
                  class="btn-accion btn-green">
            Hacer Porte (seleccionadas)
          </button>
        <?php endif; ?>
      </form>
    </div>

    <!-- Ofertas Reofrecidas -->
    <div id="reofTab" class="tab-content">
      <h2>Ofertas Reofrecidas (<?php echo $num_portes_reofrecidos; ?>)</h2>
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos.php">
          <input type="hidden" name="tab" value="reofTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_reof" value="<?php echo htmlspecialchars($buscar_reof); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_reof" value="<?php echo htmlspecialchars($fd_reof); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_reof" value="<?php echo htmlspecialchars($fh_reof); ?>">
          <button type="submit" class="btn-accion btn-green">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-green"
                  onclick="limpiarFiltros('reofTab');">
            Limpiar
          </button>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Origen</th>
            <th>Destino</th>
            <th>Fecha Selección</th>
            <th>Recibido de</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if($num_portes_reofrecidos>0): ?>
          <?php foreach($portesReofrecidos as $row): ?>
            <tr>
              <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['fecha_seleccion']); ?></td>
              <td><?php echo htmlspecialchars($row['empresa_ofertante']); ?></td>
              <td class="acciones">
                <a href="hacer_oferta.php?porte_id=<?php echo $row['porte_id']; ?>"
                   class="btn-accion btn-blue">
                  Reofrecer
                </a>
                <a href="hacer_porte.php?porte_id=<?php echo $row['porte_id']; ?>"
                   class="btn-accion btn-green">
                  Hacer Porte
                </a>
                <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>"
                   target="_blank"
                   class="btn-accion btn-gray"
                  >
                  Detalles
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7">No hay ofertas reofrecidas.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Portes Reasignados -->
    <div id="reasignadosTab" class="tab-content">
      <h2>Portes Reasignados (<?php echo $num_portes_reasignados; ?>)</h2>
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos.php">
          <input type="hidden" name="tab" value="reasignadosTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_reasig" value="<?php echo htmlspecialchars($buscar_reasig); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_reasig" value="<?php echo htmlspecialchars($fd_reasig); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_reasig" value="<?php echo htmlspecialchars($fh_reasig); ?>">
          <button type="submit" class="btn-accion btn-green">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-green"
                  onclick="limpiarFiltros('reasignadosTab');">
            Limpiar
          </button>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Origen</th>
            <th>Destino</th>
            <th>Estado</th>
            <th>Reasignado a</th>
            <th>Responsable</th>
            <th>Fecha</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if($num_portes_reasignados>0): ?>
          <?php foreach($portesReasignados as $row): ?>
            <tr>
              <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></td>
              <td><?php echo htmlspecialchars($row['reasignado_a_nombre']); ?></td>
              <td><?php echo htmlspecialchars($row['responsable_nombre']); ?></td>
              <td><?php echo htmlspecialchars($row['fecha_seleccion']); ?></td>
              <td class="acciones">
                <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>" target="_blank"
                   class="btn-accion btn-gray">
                  Detalles
                </a>
                <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tipo_evento=recogida"
                   target="_blank"
                   class="btn-accion btn-green">
                  Recogida
                </a>
                <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tipo_evento=entrega"
                   target="_blank"
                   class="btn-accion btn-green">
                  Entrega
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9">No hay portes reasignados.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Portes en Tren -->
    <div id="trenTab" class="tab-content">
      <h2>Portes en Tren (<?php echo $num_portes_en_tren; ?>)</h2>
      <div class="filtro-container">
        <form method="GET" action="portes_nuevos_recibidos.php">
          <input type="hidden" name="tab" value="trenTab">
          <label>Buscar:</label>
          <input type="text" name="buscar_tren" value="<?php echo htmlspecialchars($buscar_tren); ?>">
          <label>Fecha desde:</label>
          <input type="date" name="fecha_desde_tren" value="<?php echo htmlspecialchars($fd_tren); ?>">
          <label>Fecha hasta:</label>
          <input type="date" name="fecha_hasta_tren" value="<?php echo htmlspecialchars($fh_tren); ?>">
          <button type="submit" class="btn-accion btn-green">
            Filtrar
          </button>
          <button type="button" class="btn-accion btn-green"
                  onclick="limpiarFiltros('trenTab');">
            Limpiar
          </button>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Tren</th>
            <th>Origen</th>
            <th>Destino</th>
            <th>Estado</th>
            <th>Asignado por</th>
            <th>Inicio Tren</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if($num_portes_en_tren>0): ?>
          <?php foreach($portesEnTren as $row): ?>
            <tr>
              <td>#<?php echo htmlspecialchars($row['porte_id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td><?php echo htmlspecialchars($row['tren_nombre']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_entrega']); ?><br>
                <?php echo htmlspecialchars($row['fecha_entrega']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['estado_recogida_entrega']); ?></td>
              <td><?php echo htmlspecialchars($row['tren_usuario_nombre']); ?></td>
              <td><?php echo htmlspecialchars($row['inicio_tren']); ?></td>
              <td class="acciones">
                <a href="detalle_porte.php?id=<?php echo $row['porte_id']; ?>" target="_blank"
                   class="btn-accion btn-gray">
                  Detalles
                </a>
                <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tipo_evento=recogida"
                   target="_blank"
                   class="btn-accion btn-green">
                  Recogida
                </a>
                <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['porte_id']; ?>&tipo_evento=entrega"
                   target="_blank"
                   class="btn-accion btn-green">
                  Entrega
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9">No hay portes en tren.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

</body>
</html>
