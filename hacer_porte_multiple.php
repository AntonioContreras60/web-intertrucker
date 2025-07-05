<?php
session_start();
include 'conexion.php'; // Ajusta la ruta si tu archivo de conexión es diferente

// ======================================================
// CSRF: Generar token si no existe
// ======================================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Asegurar que el usuario esté en sesión
if (!isset($_SESSION['usuario_id'])) {
    echo "<p style='color:red;'>No hay usuario_id en la sesión. No se puede continuar.</p>";
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

// Verificar si llegan varios porte_id (por ejemplo, $_POST['porte_id'])
// Según tu lógica, quizá lleguen por GET o POST. Ajusta si hace falta.
if (!isset($_POST['porte_id']) || !is_array($_POST['porte_id'])) {
    echo "<p style='color:red;'>No se recibió un array de porte_id.</p>";
    exit();
}
$portes_recibidos = $_POST['porte_id'];

// Si no hay portes seleccionados
if (count($portes_recibidos) === 0) {
    echo "<p style='color:red;'>No hay portes en la selección.</p>";
    exit();
}

// 1. Obtener admin_id del usuario actual
$sql_admin = "SELECT admin_id FROM usuarios WHERE id = ?";
$stmt_admin = $conn->prepare($sql_admin);
if (!$stmt_admin) {
    die("Error al preparar consulta admin_id: " . $conn->error);
}
$stmt_admin->bind_param("i", $usuario_id);
$stmt_admin->execute();
$res_admin = $stmt_admin->get_result();
$admin_id  = null;
if ($res_admin->num_rows > 0) {
    $row_adm = $res_admin->fetch_assoc();
    $admin_id = $row_adm['admin_id'];
}
$stmt_admin->close();

// 2. Cargar datos básicos de cada porte
$listaPortes = [];
$placeholders = implode(',', array_fill(0, count($portes_recibidos), '?'));
$sql = "
    SELECT 
        id AS porte_id,
        mercancia_descripcion,
        localizacion_recogida,
        fecha_recogida,
        localizacion_entrega,
        fecha_entrega
    FROM portes
    WHERE id IN ($placeholders)
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en preparar la consulta de portes múltiples: " . $conn->error);
}
// Vincular parámetros dinámicamente
$tipos = str_repeat('i', count($portes_recibidos));
$stmt->bind_param($tipos, ...$portes_recibidos);
$stmt->execute();
$res = $stmt->get_result();
while($rowP = $res->fetch_assoc()) {
    $listaPortes[] = $rowP;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Hacer Porte Múltiple</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 16px;
    }
    .container {
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      border: 1px solid #ccc;
      padding: 20px;
      border-radius: 5px;
    }
    .porte-card {
      border: 1px solid #ccc;
      border-radius: 5px;
      padding: 12px;
      margin-bottom: 10px;
      background-color: #f9f9f9;
    }
    .porte-card h3 {
      margin: 0 0 8px;
      font-size: 1.1em;
    }
    label {
      font-weight: bold;
      display: block;
      margin-top: 10px;
    }
    select, input[type="text"], input[type="date"] {
      display: block;
      width: 100%;
      max-width: 500px;
      margin-bottom: 8px;
      padding: 6px;
    }
    fieldset {
      margin-top: 20px;
      border: 1px solid #007bff;
      border-radius: 5px;
      padding: 10px;
    }
    legend {
      font-weight: bold;
      color: #007bff;
    }
    .btn {
      display: inline-block;
      padding: 8px 14px;
      border: none;
      border-radius: 4px;
      margin-right: 6px;
      color: #fff;
      cursor: pointer;
    }
    .btn-success { background: #28a745; }
    .btn-info    { background: #17a2b8; }
    .btn-warning { background: #ffc107; }
    .btn-danger  { background: #dc3545; }
    .btn-primary { background: #007bff; }
    .hidden { display: none; }
  </style>
  <script>
  function onTandemChange(){
    const valor = document.getElementById("tandem_existente").value;
    const nuevoTrenSec = document.getElementById("nuevo_tren_section");
    if(valor !== "") {
      // Ocultamos la sección de crear tren
      nuevoTrenSec.style.display = "none";
    } else {
      // Mostrar la sección para crear tren
      nuevoTrenSec.style.display = "block";
    }
  }

  function rellenarCamposVehiculo(){
    const sel = document.getElementById("vehiculo_existente");
    const opt = sel.options[sel.selectedIndex];
    const vehInfo = document.getElementById("vehiculo_info");
    const semiSec = document.getElementById("semi_remolque_section");

    if(sel.value !== ""){
      document.getElementById("matricula_txt").textContent = opt.getAttribute("data-matricula");
      document.getElementById("marca_txt").textContent     = opt.getAttribute("data-marca");
      document.getElementById("modelo_txt").textContent    = opt.getAttribute("data-modelo");
      document.getElementById("capacidad_txt").textContent = opt.getAttribute("data-capacidad");
      document.getElementById("nivel2_txt").textContent    = opt.getAttribute("data-nivel2");
      document.getElementById("nivel3_txt").textContent    = opt.getAttribute("data-nivel3");
      vehInfo.style.display = "block";

      const n1 = opt.getAttribute("data-nivel");
      if(n1 === "cabeza_tractora"){
        semiSec.style.display = "block";
      } else {
        semiSec.style.display = "none";
      }
    } else {
      vehInfo.style.display = "none";
      semiSec.style.display = "none";
    }
  }
  </script>
<link rel='stylesheet' href='/header.css'>
<script src='/header.js'></script>
</head>
<body>
<?php require_once $_SERVER["DOCUMENT_ROOT"]."/header.php"; ?>

<div class="container">
  <h1>Hacer Porte - Varios Portes</h1>

  <?php if(count($listaPortes) === 0): ?>
    <p style="color:red;">No se encontraron datos para los portes seleccionados.</p>
    <?php exit; ?>
  <?php endif; ?>

  <p>Has seleccionado <strong><?php echo count($listaPortes); ?></strong> porte(s). A continuación se muestra un resumen:</p>

  <?php foreach($listaPortes as $p): ?>
    <div class="porte-card">
      <h3>Porte ID: <?php echo htmlspecialchars($p['porte_id']); ?></h3>
      <p><strong>Mercancía:</strong> <?php echo htmlspecialchars($p['mercancia_descripcion']); ?></p>
      <p><strong>Recogida:</strong> 
         <?php echo htmlspecialchars($p['localizacion_recogida']); ?> - 
         <?php echo htmlspecialchars($p['fecha_recogida']); ?>
      </p>
      <p><strong>Entrega:</strong> 
         <?php echo htmlspecialchars($p['localizacion_entrega']); ?> - 
         <?php echo htmlspecialchars($p['fecha_entrega']); ?>
      </p>
    </div>
  <?php endforeach; ?>

  <!-- ============================================= -->
  <!-- FORMULARIO para guardar la asignación múltiple -->
  <!-- ============================================= -->
  <form method="POST" action="guardar_hacer_porte_multiple.php">
    <!-- (A) Campo oculto para CSRF -->
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <!-- (B) Pasar array de porte_id -->
    <?php foreach($listaPortes as $p): ?>
      <input type="hidden" name="porte_id[]" value="<?php echo $p['porte_id']; ?>">
    <?php endforeach; ?>

    <!-- (C) Selector de Tándem Existente -->
    <fieldset>
      <legend>Usar Tándem (Tren+Camionero) Existente</legend>
      <label for="tandem_existente">Tándems Disponibles:</label>
      <select id="tandem_existente" name="tandem_existente" onchange="onTandemChange()">
        <option value="">(Ninguno / Crear Nuevo)</option>
        <?php
        // Consulta tren+camionero activos (ejemplo)
        $sql_tandems = "
          SELECT 
            t.id AS tren_id,
            c.id AS camionero_id,
            t.tren_nombre,
            u.nombre_usuario,
            u.apellidos
          FROM tren t
          JOIN tren_camionero tc ON t.id = tc.tren_id
                                AND tc.fin_tren_camionero IS NULL
          JOIN camioneros c ON tc.camionero_id = c.id
          JOIN usuarios u ON c.usuario_id = u.id
          WHERE u.admin_id = ?
        ";
        $stmt_tandem = $conn->prepare($sql_tandems);
        if($stmt_tandem){
          $stmt_tandem->bind_param("i", $admin_id);
          $stmt_tandem->execute();
          $r_tandem = $stmt_tandem->get_result();
          while($rowT = $r_tandem->fetch_assoc()){
            $tren_id = $rowT['tren_id'];
            $cam_id  = $rowT['camionero_id'];
            $tren_nm = $rowT['tren_nombre'];
            $cami_nm = $rowT['nombre_usuario']." ".$rowT['apellidos'];
            $value   = $tren_id."|".$cam_id;
            echo "<option value='$value'>Tren#$tren_id - $tren_nm / Camionero: $cami_nm</option>";
          }
          $stmt_tandem->close();
        }
        ?>
      </select>
      <p style="color:gray;">Si eliges un tándem existente, se ocultará la parte de crear tren nuevo.</p>
    </fieldset>

    <br>

    <!-- (D) Crear Tren Nuevo -->
    <fieldset id="nuevo_tren_section">
      <legend>Crear Tren Nuevo + Camionero</legend>

      <h3>Vehículo Principal</h3>
      <label for="vehiculo_existente">Seleccione Vehículo (Cabeza/rigido):</label>
      <select id="vehiculo_existente" name="vehiculo_existente" onchange="rellenarCamposVehiculo()">
        <option value="">(Ninguno)</option>
        <?php
        // Vehículos que sean rigidos o cabeza_tractora
        $sql_veh = "
          SELECT 
            id, matricula, marca, modelo, capacidad, nivel_1, nivel_2, nivel_3
          FROM vehiculos
          WHERE usuario_id = ?
            AND nivel_1 IN ('camion_rigido','cabeza_tractora')
        ";
        $stmt_veh = $conn->prepare($sql_veh);
        $stmt_veh->bind_param("i", $usuario_id);
        $stmt_veh->execute();
        $res_veh = $stmt_veh->get_result();
        while($v=$res_veh->fetch_assoc()){
          $vid  = $v['id'];
          $matr = htmlspecialchars($v['matricula']);
          $marc = htmlspecialchars($v['marca']);
          $modl = htmlspecialchars($v['modelo']);
          $capc = htmlspecialchars($v['capacidad']);
          $n1   = htmlspecialchars(strtolower($v['nivel_1']));
          $n2   = htmlspecialchars($v['nivel_2']);
          $n3   = htmlspecialchars($v['nivel_3']);
          echo "<option value='{$vid}'
                 data-matricula='$matr'
                 data-marca='$marc'
                 data-modelo='$modl'
                 data-capacidad='$capc'
                 data-nivel='$n1'
                 data-nivel2='$n2'
                 data-nivel3='$n3'>
                 $matr - $marc $modl
                </option>";
        }
        $stmt_veh->close();
        ?>
      </select>

      <div id="vehiculo_info" style="border:1px solid #ccc; padding:10px; margin:5px 0; display:none;">
        <p><strong>Matrícula:</strong> <span id="matricula_txt"></span></p>
        <p><strong>Marca:</strong> <span id="marca_txt"></span></p>
        <p><strong>Modelo:</strong> <span id="modelo_txt"></span></p>
        <p><strong>Capacidad (kg):</strong> <span id="capacidad_txt"></span></p>
        <p><strong>Nivel 2:</strong> <span id="nivel2_txt"></span></p>
        <p><strong>Nivel 3:</strong> <span id="nivel3_txt"></span></p>
      </div>

      <div id="semi_remolque_section" style="display:none; border:1px dashed #999; padding:10px; margin:5px 0;">
        <h4>Semirremolque</h4>
        <label>Seleccionar Semirremolque:</label>
        <select name="semi_remolque_existente">
          <option value="">(Ninguno)</option>
          <?php
          // Semirremolques
          $sql_semi = "
            SELECT id, matricula, marca, modelo
            FROM vehiculos
            WHERE usuario_id = ?
              AND nivel_1='semirremolque'
          ";
          $stmt_semi = $conn->prepare($sql_semi);
          $stmt_semi->bind_param("i", $usuario_id);
          $stmt_semi->execute();
          $r_semi = $stmt_semi->get_result();
          while($s = $r_semi->fetch_assoc()){
            $sid = $s['id'];
            $smat = htmlspecialchars($s['matricula']);
            $smar = htmlspecialchars($s['marca']);
            $smod = htmlspecialchars($s['modelo']);
            echo "<option value='$sid'>$smat - $smar $smod</option>";
          }
          $stmt_semi->close();
          ?>
        </select>
      </div>

      <div style="border:1px dashed #999; padding:10px; margin:5px 0;">
        <h4>Remolque (Opcional)</h4>
        <select name="remolque_existente">
          <option value="">(Ninguno)</option>
          <?php
          // Remolques
          $sql_rem = "
            SELECT id, matricula, marca, modelo
            FROM vehiculos
            WHERE usuario_id = ?
              AND nivel_1='remolque'
          ";
          $stmt_rem = $conn->prepare($sql_rem);
          $stmt_rem->bind_param("i", $usuario_id);
          $stmt_rem->execute();
          $r_rem = $stmt_rem->get_result();
          while($rr=$r_rem->fetch_assoc()){
            $rrid = $rr['id'];
            $rmat = htmlspecialchars($rr['matricula']);
            $rmar = htmlspecialchars($rr['marca']);
            $rmod = htmlspecialchars($rr['modelo']);
            echo "<option value='$rrid'>$rmat - $rmar $rmod</option>";
          }
          $stmt_rem->close();
          ?>
        </select>
      </div>

      <h3>Seleccionar Camionero</h3>
      <label for="camionero_existente">Camioneros:</label>
      <select id="camionero_existente" name="camionero_existente">
        <option value="">(Ninguno)</option>
        <?php
        // Camioneros de la misma empresa (admin_id)
        $sql_cam = "
          SELECT c.id AS camionero_id, u.nombre_usuario, u.apellidos, u.telefono
          FROM camioneros c
          JOIN usuarios u ON c.usuario_id=u.id
          WHERE u.admin_id=? 
            AND u.rol='camionero'
            AND u.estado='activo'
        ";
        $stmt_cam = $conn->prepare($sql_cam);
        $stmt_cam->bind_param("i", $admin_id);
        $stmt_cam->execute();
        $r_cam = $stmt_cam->get_result();
        while($cc=$r_cam->fetch_assoc()){
          $cid = $cc['camionero_id'];
          $nom = htmlspecialchars($cc['nombre_usuario']." ".$cc['apellidos']);
          $tel = htmlspecialchars($cc['telefono']);
          echo "<option value='$cid'>$nom - $tel</option>";
        }
        $stmt_cam->close();
        ?>
      </select>
      <p style="color:gray;">(Si eliges vehículo + camionero, se creará un tren nuevo)</p>
    </fieldset>

    <br><br>
    <button type="submit" class="btn btn-success">Guardar Todos</button>
  </form>
</div>
</body>
</html>
