<?php
session_start();
include 'conexion.php'; // Ajusta si tu ruta es distinta

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Asegurar que el usuario esté en sesión
if (!isset($_SESSION['usuario_id'])) {
    echo "<p style='color:red;'>No hay usuario_id en la sesión. No se puede continuar.</p>";
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

// Verificar si el ID del porte viene en POST o GET
if (isset($_POST['porte_id'])) {
    $porte_id = $_POST['porte_id'];
} elseif (isset($_GET['porte_id'])) {
    $porte_id = $_GET['porte_id'];
} else {
    echo "<h2 style='color:red;'>No se recibió el ID del porte.</h2>";
    exit();
}

// 1. Cargar datos del porte
// Mantenemos tus columnas. Si en tu tabla hay más, agrégalas; si faltan, quítalas:
$sql_porte = "
    SELECT
        mercancia_descripcion,
        mercancia_conservacion,
        mercancia_temperatura,
        tipo_camion,
        tipo_palet,
        cantidad,
        peso_total,
        volumen_total,
        se_puede_remontar,
        tipo_carga,
        observaciones,
        localizacion_recogida,
        fecha_recogida,
        recogida_hora_inicio,
        observaciones_recogida,
        localizacion_entrega,
        fecha_entrega,
        entrega_hora_inicio,
        observaciones_entrega,
        no_transbordos,
        no_delegacion_transporte,
        adr,
        paletizado,
        intercambio_palets,
        dimensiones_maximas,
        recogida_hora_fin,
        entrega_hora_fin,
        temperatura_minima,
        temperatura_maxima,
        cadena_frio
    FROM portes
    WHERE id = ?
";
$stmt_porte = $conn->prepare($sql_porte);
if (!$stmt_porte) {
    die("Error en prepare() del SQL de portes: " . $conn->error);
}
$stmt_porte->bind_param("i", $porte_id);
$stmt_porte->execute();
$result_porte = $stmt_porte->get_result();

if ($result_porte->num_rows === 0) {
    echo "<p style='color:red;'>No se encontró el porte con ID: $porte_id</p>";
    exit();
}
$row_porte = $result_porte->fetch_assoc();
$stmt_porte->close();

// 2. Obtener admin_id del usuario para filtrar trenes y camioneros
$sql_admin = "SELECT admin_id FROM usuarios WHERE id = ?";
$stmt_admin = $conn->prepare($sql_admin);
if (!$stmt_admin) {
    die("Error al preparar consulta admin_id: " . $conn->error);
}
$stmt_admin->bind_param("i", $usuario_id);
$stmt_admin->execute();
$res_admin = $stmt_admin->get_result();
if ($res_admin->num_rows > 0) {
    $row_adm = $res_admin->fetch_assoc();
    $admin_id = $row_adm['admin_id'];
} else {
    $admin_id = null;
}
$stmt_admin->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hacer Porte</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        main {
            width: 80%;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        select, input {
            width: 100%;
            max-width: 600px;
            margin: 5px 0;
            padding: 10px;
        }
        .info-section { display: none; }
        .info-section.active { display: block; }
    </style>
</head>
<body>
<?php require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; ?>

<main>
    <h1>Hacer Porte</h1>

    <!-- Mostrar datos del porte -->
    <div style="border:1px solid #007bff; padding:20px; margin-bottom:20px; border-radius:5px;">
      <h3>Detalles del Porte</h3>
      <?php
      // Muestra los campos existentes si no están vacíos
      // Ejemplo:

      if (!empty($row_porte['mercancia_descripcion'])) {
          echo "<p><strong>Mercancía:</strong> " . htmlspecialchars($row_porte['mercancia_descripcion']) . "</p>";
      }
      if (!empty($row_porte['mercancia_conservacion'])) {
          echo "<p><strong>Conservación:</strong> " . htmlspecialchars($row_porte['mercancia_conservacion']) . "</p>";
      }
      if (!empty($row_porte['mercancia_temperatura'])) {
          echo "<p><strong>Temperatura:</strong> " . htmlspecialchars($row_porte['mercancia_temperatura']) . " °C</p>";
      }
      if (!empty($row_porte['tipo_camion'])) {
          echo "<p><strong>Tipo de Camión:</strong> " . htmlspecialchars($row_porte['tipo_camion']) . "</p>";
      }
      if (!empty($row_porte['tipo_palet'])) {
          echo "<p><strong>Tipo Palet:</strong> " . htmlspecialchars($row_porte['tipo_palet']) . "</p>";
      }
      if (!empty($row_porte['cantidad'])) {
          echo "<p><strong>Cantidad:</strong> " . htmlspecialchars($row_porte['cantidad']) . "</p>";
      }
      if (!empty($row_porte['peso_total'])) {
          echo "<p><strong>Peso Total:</strong> " . htmlspecialchars($row_porte['peso_total']) . " kg</p>";
      }
      if (!empty($row_porte['volumen_total'])) {
          echo "<p><strong>Volumen Total:</strong> " . htmlspecialchars($row_porte['volumen_total']) . " m³</p>";
      }
      if (!empty($row_porte['localizacion_recogida'])) {
          echo "<p><strong>Localización de Recogida:</strong> " . htmlspecialchars($row_porte['localizacion_recogida']) . "</p>";
      }
      if (!empty($row_porte['fecha_recogida'])) {
          echo "<p><strong>Fecha de Recogida:</strong> " . htmlspecialchars($row_porte['fecha_recogida']) . "</p>";
      }
      if (!empty($row_porte['recogida_hora_inicio'])) {
          echo "<p><strong>Hora de Inicio (Recogida):</strong> " . htmlspecialchars($row_porte['recogida_hora_inicio']) . "</p>";
      }
      if (!empty($row_porte['recogida_hora_fin'])) {
          echo "<p><strong>Hora Fin (Recogida):</strong> " . htmlspecialchars($row_porte['recogida_hora_fin']) . "</p>";
      }
      if (!empty($row_porte['observaciones_recogida'])) {
          echo "<p><strong>Observaciones Recogida:</strong> " . htmlspecialchars($row_porte['observaciones_recogida']) . "</p>";
      }
      if (!empty($row_porte['localizacion_entrega'])) {
          echo "<p><strong>Localización de Entrega:</strong> " . htmlspecialchars($row_porte['localizacion_entrega']) . "</p>";
      }
      if (!empty($row_porte['fecha_entrega'])) {
          echo "<p><strong>Fecha de Entrega:</strong> " . htmlspecialchars($row_porte['fecha_entrega']) . "</p>";
      }
      if (!empty($row_porte['entrega_hora_inicio'])) {
          echo "<p><strong>Hora de Inicio (Entrega):</strong> " . htmlspecialchars($row_porte['entrega_hora_inicio']) . "</p>";
      }
      if (!empty($row_porte['entrega_hora_fin'])) {
          echo "<p><strong>Hora Fin (Entrega):</strong> " . htmlspecialchars($row_porte['entrega_hora_fin']) . "</p>";
      }
      if (!empty($row_porte['observaciones_entrega'])) {
          echo "<p><strong>Observaciones Entrega:</strong> " . htmlspecialchars($row_porte['observaciones_entrega']) . "</p>";
      }

      if (!is_null($row_porte['no_transbordos'])) {
          echo "<p><strong>¿Sin transbordos?:</strong> " . ($row_porte['no_transbordos'] ? 'Sí' : 'No') . "</p>";
      }
      if (!is_null($row_porte['no_delegacion_transporte'])) {
          echo "<p><strong>¿Sin delegación?:</strong> " . ($row_porte['no_delegacion_transporte'] ? 'Sí' : 'No') . "</p>";
      }
      if (!is_null($row_porte['adr'])) {
          echo "<p><strong>¿ADR?:</strong> " . ($row_porte['adr'] ? 'Sí' : 'No') . "</p>";
      }
      if (!is_null($row_porte['paletizado'])) {
          echo "<p><strong>¿Paletizado?:</strong> " . ($row_porte['paletizado'] ? 'Sí' : 'No') . "</p>";
      }
      if (!is_null($row_porte['intercambio_palets'])) {
          echo "<p><strong>¿Intercambio de Palets?:</strong> " . ($row_porte['intercambio_palets'] ? 'Sí' : 'No') . "</p>";
      }
      if (!empty($row_porte['dimensiones_maximas'])) {
          echo "<p><strong>Dimensiones Máximas:</strong> " . htmlspecialchars($row_porte['dimensiones_maximas']) . "</p>";
      }
      if (!is_null($row_porte['cadena_frio'])) {
          echo "<p><strong>¿Cadena de Frío?:</strong> " . ($row_porte['cadena_frio'] ? 'Sí' : 'No') . "</p>";
      }
      if (!empty($row_porte['temperatura_minima']) || !empty($row_porte['temperatura_maxima'])) {
          echo "<p><strong>Rango de Temperatura:</strong> "
               . htmlspecialchars($row_porte['temperatura_minima']) ." °C - "
               . htmlspecialchars($row_porte['temperatura_maxima']) ." °C</p>";
      }
      ?>
    </div>

    <form method="POST" action="guardar_hacer_porte.php">
        <input type="hidden" name="porte_id" value="<?= htmlspecialchars($porte_id) ?>">

        <!-- 1. Tándem Existente -->
        <h2>Seleccionar Tándem (Tren+Camionero) Existente</h2>
        <label for="tandem_existente">Tándems Disponibles:</label>
        <select id="tandem_existente" name="tandem_existente" onchange="onTandemChange()">
            <option value="">(Elegir tren+camionero)</option>
            <?php
            // Consulta para traer trenes + camionero activos
            $sql_tandems = "
                SELECT 
                    t.id AS tren_id,
                    c.id AS camionero_id,
                    t.tren_nombre,
                    u.nombre_usuario,
                    u.apellidos
                FROM tren t
                JOIN tren_camionero tc ON t.id=tc.tren_id
                                      AND tc.fin_tren_camionero IS NULL
                JOIN camioneros c ON tc.camionero_id=c.id
                JOIN usuarios u ON c.usuario_id=u.id
                WHERE u.admin_id = ?
            ";
            $stmt_tandem = $conn->prepare($sql_tandems);
            if ($stmt_tandem) {
                $stmt_tandem->bind_param("i", $admin_id);
                $stmt_tandem->execute();
                $res_tandem = $stmt_tandem->get_result();
                if ($res_tandem->num_rows > 0) {
                    while ($row_t = $res_tandem->fetch_assoc()) {
                        $tren_id   = $row_t['tren_id'];
                        $cam_id    = $row_t['camionero_id'];
                        $tren_name = $row_t['tren_nombre'];
                        $conductor = $row_t['nombre_usuario']." ".$row_t['apellidos'];

                        $value = $tren_id."|".$cam_id;
                        echo "<option value='{$value}'>Tren #{$tren_id} - {$tren_name} / Conductor: "
                             . htmlspecialchars($conductor)
                             . "</option>";
                    }
                }
                $stmt_tandem->close();
            }
            ?>
        </select>
        <p style="color:gray;">(Si seleccionas uno, se ocultará la opción de crear un tren nuevo)</p>

        <hr>

        <!-- 2. Campos para CREAR un tren nuevo -->
        <div id="nuevo_tren_section">
        <h1>Crear nuevo tren+camionero</h1>
            <h2>Crear tren de carretera</h2>
            <label for="vehiculo_existente">Seleccionar Vehículo (Cabeza/rigido):</label>
            <select id="vehiculo_existente" name="vehiculo_existente" onchange="rellenarCamposVehiculo()">
                <option value="">Seleccionar Vehículo</option>
                <?php
                // Vehículos del usuario
                $sql_veh = "
                    SELECT
                        id, matricula, marca, modelo, capacidad,
                        nivel_1, nivel_2, nivel_3
                    FROM vehiculos
                    WHERE usuario_id = ?
                      AND nivel_1 IN ('camion_rigido','cabeza_tractora')
                ";
                $stmt_veh = $conn->prepare($sql_veh);
                $stmt_veh->bind_param("i", $usuario_id);
                $stmt_veh->execute();
                $res_veh = $stmt_veh->get_result();
                while($v = $res_veh->fetch_assoc()) {
                    echo "<option value='{$v['id']}'
                           data-matricula='" . htmlspecialchars($v['matricula']) . "'
                           data-marca='" . htmlspecialchars($v['marca']) . "'
                           data-modelo='" . htmlspecialchars($v['modelo']) . "'
                           data-capacidad='" . htmlspecialchars($v['capacidad']) . "'
                           data-nivel='" . strtolower(htmlspecialchars($v['nivel_1'])) . "'
                           data-nivel2='" . htmlspecialchars($v['nivel_2']) . "'
                           data-nivel3='" . htmlspecialchars($v['nivel_3']) . "'>"
                         . htmlspecialchars($v['matricula']." - ".$v['marca']." ".$v['modelo']." (".ucfirst($v['nivel_1']).")")
                         . "</option>";
                }
                $stmt_veh->close();
                ?>
            </select>

            <!-- Info Vehículo -->
            <div id="vehiculo_info" class="info-section">
                <label for="matricula">Matrícula:</label>
                <input type="text" id="matricula" name="matricula" readonly>

                <label for="marca">Marca:</label>
                <input type="text" id="marca" name="marca" readonly>

                <label for="modelo">Modelo:</label>
                <input type="text" id="modelo" name="modelo" readonly>

                <label for="capacidad">Capacidad (kg):</label>
                <input type="text" id="capacidad" name="capacidad" readonly>

                <label for="nivel_2">Nivel 2:</label>
                <input type="text" id="nivel_2" name="nivel_2" readonly>

                <label for="nivel_3">Nivel 3:</label>
                <input type="text" id="nivel_3" name="nivel_3" readonly>
            </div>

            <div id="semi_remolque_section" class="info-section">
                <h2>Semirremolque</h2>
                <label for="semi_remolque_existente">Seleccionar Semirremolque:</label>
                <select id="semi_remolque_existente" name="semi_remolque_existente">
                    <option value="">Seleccionar Semirremolque</option>
                    <?php
                    $sql_semi = "
                        SELECT id, matricula, marca, modelo
                        FROM vehiculos
                        WHERE usuario_id=?
                          AND nivel_1='semirremolque'
                    ";
                    $stmt_semi = $conn->prepare($sql_semi);
                    $stmt_semi->bind_param("i", $usuario_id);
                    $stmt_semi->execute();
                    $res_semi = $stmt_semi->get_result();
                    while($semi = $res_semi->fetch_assoc()) {
                        echo "<option value='{$semi['id']}'>"
                             . htmlspecialchars($semi['matricula']." - ".$semi['marca']." ".$semi['modelo'])
                             . "</option>";
                    }
                    $stmt_semi->close();
                    ?>
                </select>
            </div>

            <div id="remolque_container">
                <div id="remolque_field">
                    <label for="remolque_existente">Seleccionar Remolque:</label>
                    <select id="remolque_existente" name="remolque_existente[]">
                        <option value="">Seleccionar Remolque</option>
                        <?php
                        $sql_rem = "
                            SELECT id, matricula, marca, modelo
                            FROM vehiculos
                            WHERE usuario_id=?
                              AND nivel_1='remolque'
                        ";
                        $stmt_rem = $conn->prepare($sql_rem);
                        $stmt_rem->bind_param("i", $usuario_id);
                        $stmt_rem->execute();
                        $res_rem = $stmt_rem->get_result();
                        while($rr = $res_rem->fetch_assoc()) {
                            echo "<option value='{$rr['id']}'>"
                                 . htmlspecialchars($rr['matricula']." - ".$rr['marca']." ".$rr['modelo'])
                                 . "</option>";
                        }
                        $stmt_rem->close();
                        ?>
                    </select>
                </div>
            </div>

            <h2>Seleccionar Camionero</h2>
            <label for="camionero_existente">Camioneros Disponibles:</label>
            <select id="camionero_existente" name="camionero_existente">
                <option value="">Seleccionar Camionero</option>
                <?php
                $sql_cam = "
                    SELECT c.id AS camionero_id,
                           u.nombre_usuario,
                           u.apellidos,
                           u.telefono
                    FROM camioneros c
                    JOIN usuarios u ON c.usuario_id = u.id
                    WHERE u.admin_id=?
                      AND u.rol='camionero'
                      AND u.estado='activo'
                ";
                $stmt_cam = $conn->prepare($sql_cam);
                $stmt_cam->bind_param("i", $admin_id);
                $stmt_cam->execute();
                $res_cam = $stmt_cam->get_result();
                while($cc = $res_cam->fetch_assoc()) {
                    $cid = $cc['camionero_id'];
                    $texto = $cc['nombre_usuario']." ".$cc['apellidos'];
                    echo "<option value='{$cid}'>"
                         . htmlspecialchars($texto." - ".$cc['telefono'])
                         . "</option>";
                }
                $stmt_cam->close();
                ?>
            </select>
            <p style="color:gray;">(Para crear un tren nuevo, elige vehículo y camionero aquí)</p>
        </div>

        <hr>
        <button type="submit">Guardar Porte</button>
    </form>
</main>

<script>
function onTandemChange(){
    const tandemValue = document.getElementById("tandem_existente").value;

    // Sección de crear tren
    const nuevoTrenSection = document.getElementById("nuevo_tren_section");

    if (tandemValue !== "") {
        // Se ha elegido un TÁNDEM existente => ocultar la parte de crear tren
        nuevoTrenSection.style.display = "none";
    } else {
        // Quieren crear un tren => mostrar
        nuevoTrenSection.style.display = "block";
    }
}

function rellenarCamposVehiculo() {
    const select = document.getElementById("vehiculo_existente");
    const option = select.options[select.selectedIndex];
    const vehiculoInfo = document.getElementById("vehiculo_info");
    const semiRemolqueSection = document.getElementById("semi_remolque_section");

    if (select.value !== "") {
        document.getElementById("matricula").value = option.getAttribute("data-matricula");
        document.getElementById("marca").value     = option.getAttribute("data-marca");
        document.getElementById("modelo").value    = option.getAttribute("data-modelo");
        document.getElementById("capacidad").value = option.getAttribute("data-capacidad");
        document.getElementById("nivel_2").value   = option.getAttribute("data-nivel2");
        document.getElementById("nivel_3").value   = option.getAttribute("data-nivel3");
        vehiculoInfo.classList.add("active");

        if (option.getAttribute("data-nivel")==="cabeza_tractora") {
            semiRemolqueSection.classList.add("active");
        } else {
            semiRemolqueSection.classList.remove("active");
        }
    } else {
        vehiculoInfo.classList.remove("active");
        semiRemolqueSection.classList.remove("active");
    }
}
</script>

</body>
</html>
