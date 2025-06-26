<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener el admin_id del usuario logueado
$sql_admin_id = "SELECT admin_id FROM usuarios WHERE id = ?";
$stmt_admin_id = $conn->prepare($sql_admin_id);
$stmt_admin_id->bind_param("i", $_SESSION['usuario_id']);
$stmt_admin_id->execute();
$result_admin_id = $stmt_admin_id->get_result();
$admin_id = $result_admin_id->fetch_assoc()['admin_id'] ?? $_SESSION['usuario_id'];

// Consulta para obtener el listado de trenes y sus camioneros
$sql_listado_trenes = "
    SELECT 
        t.id AS tren_id, 
        t.tren_nombre, 
        u.nombre_usuario AS conductor_nombre, 
        u.apellidos AS conductor_apellidos, 
        tc.inicio_tren_camionero, 
        tc.fin_tren_camionero
    FROM 
        tren t
    INNER JOIN 
        tren_camionero tc ON t.id = tc.tren_id
    INNER JOIN 
        camioneros c ON tc.camionero_id = c.id
    INNER JOIN 
        usuarios u ON c.usuario_id = u.id
    WHERE 
        u.admin_id = ?
    ORDER BY 
        tc.inicio_tren_camionero DESC";

$stmt_listado_trenes = $conn->prepare($sql_listado_trenes);

if (!$stmt_listado_trenes) {
    die("Error en la consulta SQL: " . $conn->error);
}

$stmt_listado_trenes->bind_param("i", $admin_id);
$stmt_listado_trenes->execute();
$result_listado_trenes = $stmt_listado_trenes->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        main {
            width: 80%;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        select, input {
            width: 100%;
            max-width: 600px;
            margin: 5px 0;
            padding: 10px;
        }

        .info-section {
            display: none;
        }

        .info-section.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
<form method="POST" action="crear_asignacion.php">

        <h1>Tren - Condutor</h1>

            <h2>Crear tren de carretera</h2>
            <label for="vehiculo_existente">Seleccionar Vehículo:</label>
            <select id="vehiculo_existente" name="vehiculo_existente" required onchange="rellenarCamposVehiculo()">

                <option value="">Seleccionar Vehículo</option>
                <?php
                // Consulta para obtener los vehículos del usuario actual, incluyendo el campo nivel_1
                $sql_vehiculos = "SELECT id, matricula, marca, modelo, capacidad, nivel_1 FROM vehiculos WHERE usuario_id = ? AND nivel_1 IN ('Camion_rigido', 'Cabeza_tractora')";
                $stmt_vehiculos = $conn->prepare($sql_vehiculos);
                $stmt_vehiculos->bind_param("i", $admin_id);
                $stmt_vehiculos->execute();
                $result_vehiculos = $stmt_vehiculos->get_result();

                if ($result_vehiculos->num_rows > 0) {
while ($vehiculo = $result_vehiculos->fetch_assoc()) {
    echo "<option value='" . $vehiculo['id'] . "' data-matricula='" . $vehiculo['matricula'] . "' data-marca='" . $vehiculo['marca'] . "' data-modelo='" . $vehiculo['modelo'] . "' data-capacidad='" . $vehiculo['capacidad'] . "' data-nivel='" . $vehiculo['nivel_1'] . "' data-nivel2='" . $vehiculo['nivel_2'] . "' data-nivel3='" . $vehiculo['nivel_3'] . "'>" 
    . $vehiculo['matricula'] . " - " . $vehiculo['marca'] . " " . $vehiculo['modelo'] . " (" . ucfirst($vehiculo['nivel_1']) . ")</option>";
}
                } else {
                    echo "<option value=''>No hay vehículos registrados</option>";
                }
                ?>
            </select>

            <!-- Información del vehículo -->
            <div id="vehiculo_info" class="info-section">
                <label for="matricula">Matrícula:</label>
                <input type="text" id="matricula" name="matricula" readonly>
                
                <label for="marca">Marca:</label>
                <input type="text" id="marca" name="marca" readonly>

                <label for="modelo">Modelo:</label>
                <input type="text" id="modelo" name="modelo" readonly>

                <label for="capacidad">Capacidad (kg):</label>
                <input type="text" id="capacidad" name="capacidad" readonly>

              <!-- Nuevos campos para Nivel 2 y Nivel 3 -->
                 <label for="nivel_2">Nivel 2:</label>
                 <input type="text" id="nivel_2" name="nivel_2" readonly>
    
                  <label for="nivel_3">Nivel 3:</label>
                  <input type="text" id="nivel_3" name="nivel_3" readonly>
            </div>

            <!-- Select para el semirremolque -->
            <div id="semi_remolque_section" class="info-section">
                <h2>Semirremolque</h2>
                <label for="semi_remolque_existente">Seleccionar Semirremolque:</label>
                <select id="semi_remolque_existente" name="semi_remolque_existente">
                    <option value="">Seleccionar Semirremolque</option>
                    <?php
                    // Consulta para obtener los semirremolques disponibles para el usuario actual
                    $sql_semirremolques = "SELECT id, matricula, marca, modelo FROM vehiculos WHERE usuario_id = ? AND nivel_1 = 'semirremolque'";
                    $stmt_semirremolques = $conn->prepare($sql_semirremolques);
                    $stmt_semirremolques->bind_param("i", $admin_id);
                    $stmt_semirremolques->execute();
                    $result_semirremolques = $stmt_semirremolques->get_result();

                    if ($result_semirremolques->num_rows > 0) {
                        while ($semi = $result_semirremolques->fetch_assoc()) {
                            echo "<option value='" . $semi['id'] . "'>" . $semi['matricula'] . " - " . $semi['marca'] . " " . $semi['modelo'] . "</option>";
                        }
                    } else {
                        echo "<option value=''>No hay semirremolques disponibles</option>";
                    }
                    ?>
                </select>
            </div>
            
<!-- Menú Desplegable para Selección de Remolque -->
<div id="remolque_container">
    <div id="remolque_field">
        <label for="remolque_existente">Seleccionar Remolque:</label>
        <select name="remolque_existente[]" id="remolque_existente">
            <option value="">Seleccionar Remolque</option>
            <?php
            // Consulta para obtener los remolques del usuario actual
            $sql_remolques = "SELECT id, matricula, marca, modelo FROM vehiculos WHERE usuario_id = ? AND nivel_1 = 'remolque'";
            $stmt_remolques = $conn->prepare($sql_remolques);
            $stmt_remolques->bind_param("i", $admin_id);
            $stmt_remolques->execute();
            $result_remolques = $stmt_remolques->get_result();

            if ($result_remolques->num_rows > 0) {
                while ($remolque = $result_remolques->fetch_assoc()) {
                    echo "<option value='" . $remolque['id'] . "'>" . $remolque['matricula'] . " - " . $remolque['marca'] . " " . $remolque['modelo'] . "</option>";
                }
            } else {
                echo "<option value=''>No hay remolques disponibles</option>";
            }
            ?>
        </select>
    </div>
</div>



<!-- Sección de Camionero -->

            <h2>Camionero</h2>
            <label for="camionero_existente">Seleccionar Camionero:</label>
            <select id="camionero_existente" name="camionero_existente" onchange="rellenarCamposCamionero()">
                <option value="">Seleccionar Camionero</option>
                <?php
               $sql_camioneros = "
    SELECT u.id, u.nombre_usuario AS nombre, u.apellidos, u.telefono 
    FROM usuarios u
    WHERE u.admin_id = ?";

                $stmt_camioneros = $conn->prepare($sql_camioneros);
                $stmt_camioneros->bind_param("i", $admin_id);
                $stmt_camioneros->execute();
                $result_camioneros = $stmt_camioneros->get_result();

                if ($result_camioneros->num_rows > 0) {
                    while ($camionero = $result_camioneros->fetch_assoc()) {
                        echo "<option value='" . $camionero['id'] . "' data-nombre='" . $camionero['nombre'] . "' data-apellidos='" . $camionero['apellidos'] . "'>" . $camionero['nombre'] . " " . $camionero['apellidos'] . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay camioneros registrados</option>";
                }
                ?>
            </select>

            <div id="camionero_info" class="info-section">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" readonly>
            </div>
                <!-- ID del porte a enviar -->
            <button type="submit">Crear Asignación Tren-Camionero</button>
     
     </form>
    
  
    <script>
        function rellenarCamposVehiculo() {
            const select = document.getElementById("vehiculo_existente");
            const vehiculoInfo = document.getElementById("vehiculo_info");
            const semiRemolqueSection = document.getElementById("semi_remolque_section");
            const option = select.options[select.selectedIndex];
            
            if (select.value !== "") {
                document.getElementById("matricula").value = option.getAttribute("data-matricula");
                document.getElementById("marca").value = option.getAttribute("data-marca");
                document.getElementById("modelo").value = option.getAttribute("data-modelo");
                document.getElementById("capacidad").value = option.getAttribute("data-capacidad");
                document.getElementById("nivel_2").value = option.getAttribute("data-nivel2");
                document.getElementById("nivel_3").value = option.getAttribute("data-nivel3");
                vehiculoInfo.classList.add("active");

                // Mostrar selector de semirremolques si el nivel es cabeza tractora
                if (option.getAttribute("data-nivel") === "cabeza_tractora") {
                    semiRemolqueSection.classList.add("active");
                } else {
                    semiRemolqueSection.classList.remove("active");
                }
            } else {
                vehiculoInfo.classList.remove("active");
                semiRemolqueSection.classList.remove("active");
            }
        }

        function rellenarCamposCamionero() {
            const select = document.getElementById("camionero_existente");
            const camioneroInfo = document.getElementById("camionero_info");
            const option = select.options[select.selectedIndex];
            if (select.value !== "") {
                document.getElementById("nombre").value = option.getAttribute("data-nombre");
                camioneroInfo.classList.add("active");
            } else {
                camioneroInfo.classList.remove("active");
            }
        }
    </script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const nivel1Select = document.getElementById("nivel_1");
    const remolqueField = document.getElementById("remolque_field");
    const remolqueSelect = document.getElementById("remolque");
    const remolqueDisponibilidad = document.getElementById("remolque_disponibilidad");

    // Mostrar el campo de remolque solo para Camión Rígido o Cabeza Tractora
    nivel1Select.addEventListener("change", function () {
        const nivel1Value = nivel1Select.value;

        // Mostrar u ocultar el selector de remolque según la selección del nivel 1
        if (nivel1Value === "camion_rigido" || nivel1Value === "cabeza_tractora") {
            remolqueField.classList.remove("hidden");
            cargarRemolquesDisponibles();
        } else {
            remolqueField.classList.add("hidden");
        }
    });

    // Función para cargar los remolques disponibles (puede usarse AJAX)
    function cargarRemolquesDisponibles() {
        // Opciones estáticas de ejemplo - sustituir con llamada a la base de datos si es necesario
        const remolques = [
            { id: 1, nombre: "Remolque Plataforma 1" },
            { id: 2, nombre: "Remolque Cisterna 2" }
        ];

        // Limpiar las opciones existentes
        remolqueSelect.innerHTML = '<option value="">Sin Remolque</option>';

        // Comprobar si hay remolques disponibles
        if (remolques.length > 0) {
            remolques.forEach(remolque => {
                let option = document.createElement("option");
                option.value = remolque.id;
                option.text = remolque.nombre;
                remolqueSelect.appendChild(option);
            });
            remolqueDisponibilidad.textContent = ""; // Borrar mensaje de no disponibilidad
        } else {
            remolqueDisponibilidad.textContent = "No hay remolques disponibles para esta unidad.";
        }
    }
});
</script>
<script>
// Función para clonar el select de remolques y evitar la selección repetida
function agregarOtroRemolque() {
    // Clonar el campo del remolque
    const remolqueField = document.getElementById('remolque_field');
    const nuevoRemolqueField = remolqueField.cloneNode(true);

    // Limpiar la selección del nuevo remolque
    const selectNuevoRemolque = nuevoRemolqueField.querySelector('select');
    selectNuevoRemolque.value = '';

    // Actualizar el id del nuevo select (incrementalmente)
    const selectCount = document.querySelectorAll('#remolque_container select').length + 1;
    selectNuevoRemolque.id = 'remolque_' + selectCount;

    // Verificar y eliminar opciones seleccionadas previamente
    const seleccionados = Array.from(document.querySelectorAll('#remolque_container select')).map(select => select.value);
    Array.from(selectNuevoRemolque.options).forEach(option => {
        if (seleccionados.includes(option.value)) {
            option.disabled = true; // Deshabilitar opción ya seleccionada
        }
    });

    // Insertar el nuevo campo de remolque antes del camionero
    const remolqueContainer = document.getElementById('remolque_container');
    remolqueContainer.appendChild(nuevoRemolqueField);

    // Escuchar cuando el usuario seleccione un remolque
    selectNuevoRemolque.addEventListener('change', agregarOtroRemolque);
}

// Agregar el listener inicial al primer campo de remolque
document.getElementById('remolque_existente').addEventListener('change', agregarOtroRemolque);

</script>
<table>
        <tr>
            <th>Nombre Tren</th>
            <th>Nombre Conductor</th>
            <th>Apellidos Conductor</th>
            <th>Inicio</th>
            <th>Fin</th>
        </tr>
        <?php while ($tren = $result_listado_trenes->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($tren['tren_nombre']); ?></td>
                <td><?php echo htmlspecialchars($tren['conductor_nombre']); ?></td>
                <td><?php echo htmlspecialchars($tren['conductor_apellidos']); ?></td>
                <td><?php echo htmlspecialchars($tren['inicio_tren_camionero']); ?></td>
                <td><?php echo htmlspecialchars($tren['fin_tren_camionero']) ?: 'Activo'; ?></td>
                 <td>
                    <?php if (!$tren['fin_tren_camionero']): // Mostrar botón solo si está activo ?>
                        <form method="POST" action="finalizar_tren.php">
                            <input type="hidden" name="tren_id" value="<?php echo htmlspecialchars($tren['tren_id']); ?>">
                            <button type="submit">Finalizar</button>
                        </form>
                    <?php else: ?>
                        Finalizado
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    
    <?php include 'footer.php'; ?>
</body>
</html>
