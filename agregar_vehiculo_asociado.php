<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php'; // Ajusta la ruta si tu archivo de conexión está en otro directorio

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Verificamos si llega ?usuario_id= por GET
if (!isset($_GET['usuario_id'])) {
    die("Falta el usuario_id del asociado en la URL (?usuario_id=XXX).");
}
$asociado_id = intval($_GET['usuario_id']);

// Variable para manejar el estado
$vehiculo_creado = false;
$error_msg = "";

// -------------------------------------------------------
// LÓGICA PARA AÑADIR VEHÍCULO
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accion'])
    && $_POST['accion'] === 'agregar_vehiculo'
) {
    // Recogemos campos del formulario
    $nivel_1       = $_POST['nivel_1'] ?? '';
    $nivel_2       = $_POST['nivel_2'] ?? '';
    $nivel_3       = $_POST['nivel_3'] ?? '';
    $matricula     = trim($_POST['matricula'] ?? '');
    $marca         = trim($_POST['marca'] ?? '');
    $modelo        = trim($_POST['modelo'] ?? '');
    $ano_fabricacion = $_POST['ano_fabricacion'] ?? null;
    $capacidad       = $_POST['capacidad'] ?? null;
    $volumen         = $_POST['volumen'] ?? null;
    $capacidad_arrastre = $_POST['capacidad_arrastre'] ?? null;
    $numero_ejes       = $_POST['numero_ejes'] ?? null;
    $temperatura_controlada = isset($_POST['temperatura_controlada']) ? 1 : 0;

    $forma_carga_lateral = isset($_POST['forma_carga_lateral']) ? 1 : 0;
    $forma_carga_detras  = isset($_POST['forma_carga_detras'])  ? 1 : 0;
    $forma_carga_arriba  = isset($_POST['forma_carga_arriba'])  ? 1 : 0;

    $adr               = isset($_POST['adr']) ? 1 : 0;
    $doble_conductor   = isset($_POST['doble_conductor']) ? 1 : 0;
    $plataforma_elevadora = isset($_POST['plataforma_elevadora']) ? 1 : 0;
    $telefono       = trim($_POST['telefono'] ?? '');
    $observaciones  = trim($_POST['observaciones'] ?? '');

    // Insert en la tabla `vehiculos` con `usuario_id = $asociado_id`
    $sqlInsert = "
        INSERT INTO vehiculos (
            nivel_1,
            nivel_2,
            nivel_3,
            matricula,
            marca,
            modelo,
            ano_fabricacion,
            capacidad,
            volumen,
            capacidad_arrastre,
            numero_ejes,
            temperatura_controlada,
            forma_carga_lateral,
            forma_carga_detras,
            forma_carga_arriba,
            adr,
            doble_conductor,
            plataforma_elevadora,
            telefono,
            observaciones,
            usuario_id,
            activo
        ) VALUES (
            ?,?,?,?,?,?,
            ?,?,?,?,?,?,
            ?,?,?,?,?,?,
            ?,?,
            ?,  -- user_id
            1  -- activo=1 por defecto
        )
    ";
    $stmt_ins = $conn->prepare($sqlInsert);
    if (!$stmt_ins) {
        die("Error al preparar inserción de vehículo: ".$conn->error);
    }

    $stmt_ins->bind_param(
        "ssssssiiiii"    // 11
        ."iii"           // +3 => 14
        ."iii"           // +3 => 17
        ."ssi"           // +3 => 20
        ."i",            // +1 => 21
        $nivel_1,
        $nivel_2,
        $nivel_3,
        $matricula,
        $marca,
        $modelo,
        $ano_fabricacion,
        $capacidad,
        $volumen,
        $capacidad_arrastre,
        $numero_ejes,
        $temperatura_controlada,
        $forma_carga_lateral,
        $forma_carga_detras,
        $forma_carga_arriba,
        $adr,
        $doble_conductor,
        $plataforma_elevadora,
        $telefono,
        $observaciones,
        $asociado_id
    );
    if ($stmt_ins->execute()) {
        // Éxito: indicamos que se creó el vehículo
        $vehiculo_creado = true;
    } else {
        // Error al insertar
        $error_msg = "Error al insertar vehículo: " . $stmt_ins->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Vehículo</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .hidden { display:none; }
        form {
            margin-top: 20px;
        }
        label {
            display:block;
            margin-bottom: 5px;
        }
        .checkbox-group label {
            display:inline-block;
            margin-right: 15px;
        }
        /* Ajuste visual para grupos de campos */
        #formas_carga, #capacidad_field, #volumen_field, #tractora_fields, #temperatura_field {
            margin-bottom: 10px;
        }
        .mensaje-exito {
            background-color: #4caf50;
            color: #fff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .mensaje-error {
            background-color: #f44336;
            color: #fff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
  <link rel="stylesheet" href="/header.css">
  <script src="/header.js"></script>
</head>
<body>
<?php include 'header.php'; ?>

<h1>Añadir Vehículo (Asociado ID: <?= htmlspecialchars($asociado_id) ?>)</h1>

<?php if ($vehiculo_creado): ?>
    <!-- Mostrar mensaje de éxito y botón para volver al listado -->
    <div class="mensaje-exito">
        ¡Vehículo creado exitosamente!
    </div>
    <p>
        <a href="vehiculo_asociado.php?usuario_id=<?= $asociado_id ?>">
            Volver al listado de vehículos
        </a>
    </p>

<?php elseif (!empty($error_msg)): ?>
    <!-- Mostrar mensaje de error -->
    <div class="mensaje-error">
        <?= htmlspecialchars($error_msg) ?>
    </div>
    <!-- Volver a mostrar el formulario para que corrija -->
    <?php mostrarFormulario($asociado_id); ?>

<?php else: ?>
    <!-- Si no se ha enviado el formulario o no hay error, mostrar el formulario normalmente -->
    <?php mostrarFormulario($asociado_id); ?>
<?php endif; ?>

<?php include 'footer.php'; ?>

<!-- Bloque de script para la lógica de submenús (tomado de my_trucks.js) -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const nivel1Select = document.getElementById("nivel_1");
    const nivel2Select = document.getElementById("nivel_2");
    const nivel3Select = document.getElementById("nivel_3");

    const nivel2Options = {
        "camion_rigido": [
            "Cerrado", "Abierto", "Cisterna", "Volquete",
            "Frigorifico", "Jaula", "Plataforma",
            "Portacontenedores", "Basculante", "Extensible"
        ],
        "cabeza_tractora": [
            "Tractora Estandar", "Tractora Pesado", "Tractora Internacional"
        ],
        "semirremolque": [
            "Cerrado", "Abierto", "Cisterna", "Volquete",
            "Frigorifico", "Jaula", "Plataforma",
            "Portacontenedores", "Basculante", "Extensible"
        ],
        "remolque": [
            "Cerrado", "Abierto", "Cisterna", "Volquete",
            "Frigorifico", "Jaula", "Plataforma",
            "Portacontenedores", "Basculante", "Extensible"
        ]
    };

    const nivel3Options = {
        "Tractora Estandar": [
            "Tractora de Largo Alcance", "Tractora con Cabina Extendida"
        ],
        "Tractora Pesado": [
            "Sistema de Enganche Rapido", "Sistema de Reduccion de Peso"
        ],
        "Tractora Internacional": [
            "Tractora con Sistema GPS", "Cabina con Cama Adicional"
        ],
        // Ejemplos de las opciones para subcategorías
        "Cerrado": [
            "Caja Blindada", "Caja Hermética", "Camión con Rampa Hidráulica",
            "Camión con Paneles Extraíbles", "Con Puertas Traseras Doble Hoja",
            "Con Puertas Laterales"
        ],
        "Abierto": [
            "Plataforma con Soporte para Grua", "Plataforma con Extensiones",
            "Laterales Abatibles", "Con Lona (Tauliner)",
            "Plataforma Portamáquinas", "Con Barras Laterales"
        ],
        "Cisterna": [
            "Sistema de Bombeo Interno", "Cisterna Presurizada",
            "Cisterna de Acero Inoxidable", "Cisterna con Recubrimiento Especial",
            "Compartimentada", "Con Sistema de Calentamiento"
        ],
        "Volquete": [
            "Volquete Trasero", "Volquete Lateral", "Volquete con Laterales Desmontables",
            "Volquete con Tolva", "Con Elevación Hidráulica", "Con Estabilizadores"
        ],
        "Frigorifico": [
            "Sistema de Refrigeración", "Sistema de Congelación",
            "Refrigeración de Doble Temperatura", "Con Separador Interno",
            "Con Termómetro Exterior", "Con Control de Humedad"
        ],
        "Jaula": [
            "Jaula Desmontable", "Jaula con Puertas Laterales",
            "Jaula con Techo Abatible", "Jaula con Rejilla Fina",
            "Jaula con Ventilación Forzada", "Jaula con Refuerzo Estructural"
        ],
        "Plataforma": [
            "Plataforma con Soporte para Carga Larga", "Plataforma con Soporte para Bobinas",
            "Plataforma con Faldones Laterales", "Plataforma Baja",
            "Plataforma Portacontenedores", "Con Estabilizadores"
        ],
        "Portacontenedores": [
            "Soporte para Contenedores ISO", "Portacontenedores Doble",
            "Portacontenedores con Fijación Rápida", "Portacontenedores con Freno Hidráulico",
            "Con Sistema de Elevación para Carga y Descarga", "Portacontenedores Basculante"
        ],
        "Basculante": [
            "Basculante Trasero", "Basculante Lateral", "Con Tolva",
            "Con Estabilizadores", "Sistema de Volcado Rápido",
            "Con Paneles Laterales Desmontables"
        ],
        "Extensible": [
            "Plataforma Extensible", "Con Extensión de Longitud",
            "Con Extensión Lateral", "Con Bloqueo de Seguridad",
            "Extensible para Tubos", "Con Sistema de Ajuste Automático"
        ]
    };

    // Campos específicos
    const capacidadField = document.getElementById("capacidad_field");
    const volumenField = document.getElementById("volumen_field");
    const cargaFormas = document.getElementById("formas_carga");
    const temperaturaField = document.getElementById("temperatura_field");
    const tractoraField = document.getElementById("tractora_fields");

    const capacidadInput = document.getElementById("capacidad_input");
    const volumenInput = document.getElementById("volumen_input");
    const arrastreInput = document.getElementById("capacidad_arrastre_input");
    const numeroEjesInput = document.getElementById("numero_ejes_input");

    // Al cambiar nivel_1 => Rellenar nivel_2 y mostrar/ocultar campos
    nivel1Select.addEventListener("change", function () {
        const nivel1Value = nivel1Select.value;

        // Reset subcategorías
        nivel2Select.innerHTML = '<option value="">(Seleccionar)</option>';
        nivel3Select.innerHTML = '<option value="">(Seleccionar)</option>';

        if (nivel2Options[nivel1Value]) {
            nivel2Options[nivel1Value].forEach(function (subcat) {
                let option = document.createElement("option");
                option.value = subcat;
                option.text = subcat;
                nivel2Select.appendChild(option);
            });
        }

        // Actualizar qué campos se muestran
        updateFieldVisibility(nivel1Value);
    });

    // Al cambiar nivel_2 => Rellenar nivel_3
    nivel2Select.addEventListener("change", function () {
        const nivel2Value = nivel2Select.value;
        nivel3Select.innerHTML = '<option value="">(Seleccionar)</option>';

        if (nivel3Options[nivel2Value]) {
            nivel3Options[nivel2Value].forEach(function (det) {
                let option = document.createElement("option");
                option.value = det;
                option.text = det;
                nivel3Select.appendChild(option);
            });
        }
    });

    // Funciones para mostrar/ocultar campos
    function resetVisibility() {
        if (capacidadField) capacidadField.classList.add("hidden");
        if (volumenField) volumenField.classList.add("hidden");
        if (cargaFormas) cargaFormas.classList.add("hidden");
        if (temperaturaField) temperaturaField.classList.add("hidden");
        if (tractoraField) tractoraField.classList.add("hidden");
    }

    function updateFieldVisibility(nivel1Value) {
        resetVisibility();
        switch (nivel1Value) {
            case "camion_rigido":
                if (capacidadField) capacidadField.classList.remove("hidden");
                if (volumenField) volumenField.classList.remove("hidden");
                if (cargaFormas) cargaFormas.classList.remove("hidden");
                if (temperaturaField) temperaturaField.classList.remove("hidden");
                capacidadInput.placeholder = "Capacidad (Ton)";
                volumenInput.placeholder = "Volumen (m³)";
                break;

            case "cabeza_tractora":
                if (tractoraField) tractoraField.classList.remove("hidden");
                arrastreInput.placeholder = "Cap. Arrastre (Ton)";
                numeroEjesInput.placeholder = "Número de Ejes";
                break;

            case "semirremolque":
                if (capacidadField) capacidadField.classList.remove("hidden");
                if (volumenField) volumenField.classList.remove("hidden");
                if (cargaFormas) cargaFormas.classList.remove("hidden");
                capacidadInput.placeholder = "Capacidad (Ton)";
                volumenInput.placeholder = "Volumen (m³)";
                break;

            case "remolque":
                if (capacidadField) capacidadField.classList.remove("hidden");
                if (volumenField) volumenField.classList.remove("hidden");
                if (cargaFormas) cargaFormas.classList.remove("hidden");
                capacidadInput.placeholder = "Capacidad (Ton)";
                volumenInput.placeholder = "Volumen (m³)";
                break;

            default:
                resetVisibility();
                break;
        }
    }
});
</script>

</body>
</html>

<?php
// -----------------------------------------------------
// Función para mostrar el formulario (para evitar duplicar HTML)
// -----------------------------------------------------
function mostrarFormulario($asociado_id)
{
    ?>
    <form method="POST" action="agregar_vehiculo_asociado.php?usuario_id=<?= $asociado_id ?>" enctype="multipart/form-data">
        <input type="hidden" name="accion" value="agregar_vehiculo">

        <label for="nivel_1">Tipo Principal:</label>
        <select name="nivel_1" id="nivel_1" required>
            <option value="">(Seleccionar)</option>
            <option value="camion_rigido">Camión Rígido</option>
            <option value="cabeza_tractora">Cabeza Tractora</option>
            <option value="semirremolque">Semirremolque</option>
            <option value="remolque">Remolque</option>
        </select>

        <br><br>
        <label for="nivel_2">Subcategoría:</label>
        <select name="nivel_2" id="nivel_2" required>
            <option value="">(Seleccionar)</option>
        </select>

        <br><br>
        <label for="nivel_3">Especificación (opcional):</label>
        <select name="nivel_3" id="nivel_3">
            <option value="">(Seleccionar)</option>
        </select>

        <br><br>
        <label>Matrícula:</label>
        <input type="text" name="matricula" placeholder="Matrícula" required>

        <label>Marca:</label>
        <input type="text" name="marca" placeholder="Marca" required>

        <label>Modelo:</label>
        <input type="text" name="modelo" placeholder="Modelo" required>

        <label>Año de Fabricación (opcional):</label>
        <input type="number" name="ano_fabricacion">

        <div id="formas_carga" class="hidden">
            <h3>Formas de Carga</h3>
            <label><input type="checkbox" name="forma_carga_lateral" value="1"> Lateral</label>
            <label><input type="checkbox" name="forma_carga_detras" value="1"> Detrás</label>
            <label><input type="checkbox" name="forma_carga_arriba" value="1"> Arriba</label>
        </div>

        <div id="capacidad_field" class="hidden">
            <label for="capacidad_input">Capacidad (Ton):</label>
            <input id="capacidad_input" type="number" step="0.1" name="capacidad">
        </div>
        <div id="volumen_field" class="hidden">
            <label for="volumen_input">Volumen (m³):</label>
            <input id="volumen_input" type="number" step="0.1" name="volumen">
        </div>

        <div id="tractora_fields" class="hidden">
            <h3>Características de Arrastre</h3>
            <label>Cap. Arrastre (Ton):</label>
            <input id="capacidad_arrastre_input" type="number" step="0.1" name="capacidad_arrastre">
            <br>
            <label>Número de Ejes:</label>
            <input id="numero_ejes_input" type="number" step="0.1" name="numero_ejes">
        </div>

        <div id="temperatura_field" class="hidden">
            <h3>Temperatura Controlada</h3>
            <label><input type="checkbox" name="temperatura_controlada" value="1"> Dispone de sistema de frío</label>
        </div>

        <h3>Características Adicionales</h3>
        <label><input type="checkbox" name="adr" value="1"> ADR</label>
        <label><input type="checkbox" name="doble_conductor" value="1"> Doble Conductor</label>
        <label><input type="checkbox" name="plataforma_elevadora" value="1"> Plataforma Elevadora</label>

        <br><br>
        <label>Teléfono (opcional):</label>
        <input type="text" name="telefono" placeholder="Teléfono">

        <br><br>
        <label>Observaciones (opcional):</label>
        <textarea name="observaciones" placeholder="Observaciones" rows="3" cols="40"></textarea>

        <br><br>
        <!-- (Opcional) Subir documentos del vehículo, si así lo deseas
        <input type="file" name="docsVehiculo[]" multiple>
        -->

        <br><br>
        <button type="submit">Guardar Vehículo</button>
        <br>
        <button type="button" onclick="history.back()">Volver</button>
    </form>
    <?php
}
