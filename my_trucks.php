<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener el admin_id para centralizar los vehículos
$sql_admin_id = "SELECT admin_id FROM usuarios WHERE id = ?";
$stmt_admin_id = $conn->prepare($sql_admin_id);
$stmt_admin_id->bind_param("i", $_SESSION['usuario_id']);
$stmt_admin_id->execute();
$result_admin_id = $stmt_admin_id->get_result();
if ($result_admin_id->num_rows > 0) {
    $admin_id = $result_admin_id->fetch_assoc()['admin_id'];
} else {
    // Si no hay admin_id, lo consideramos igual al usuario actual
    $admin_id = $_SESSION['usuario_id'];
}

// Filtrar listado de vehículos
$filtro_vehiculo = "";
if (isset($_POST['filtro_vehiculo'])) {
    $filtro_vehiculo = $_POST['filtro_vehiculo'];
    $sql_vehiculos = "SELECT * FROM vehiculos 
        WHERE (matricula LIKE ? OR marca LIKE ? OR nivel_1 LIKE ? OR nivel_2 LIKE ?) 
        AND usuario_id = ?";
    $stmt_vehiculos = $conn->prepare($sql_vehiculos);
    if (!$stmt_vehiculos) {
        die("Error en la preparación de la consulta de vehículos: " . $conn->error);
    }
    $search = '%' . $filtro_vehiculo . '%';
    $stmt_vehiculos->bind_param("ssssi", $search, $search, $search, $search, $admin_id);
} else {
    $sql_vehiculos = "SELECT * FROM vehiculos WHERE usuario_id = ?";
    $stmt_vehiculos = $conn->prepare($sql_vehiculos);
    if (!$stmt_vehiculos) {
        die("Error en la preparación de la consulta de vehículos: " . $conn->error);
    }
    $stmt_vehiculos->bind_param("i", $admin_id);
}

if (!$stmt_vehiculos->execute()) {
    die("Error al ejecutar la consulta de vehículos: " . $stmt_vehiculos->error);
}
$resultado_vehiculos = $stmt_vehiculos->get_result();

// Cambiar estado del vehículo
// (Se usa en cambiar_estado_vehiculo.php, pero si prefieres hacerlo aquí,
// descomenta y ajusta en consecuencia; en el ejemplo, mantenemos la lógica aparte.)
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehiculo_id'], $_POST['estado_actual'])) {
    $vehiculo_id = $_POST['vehiculo_id'];
    $nuevo_estado = ($_POST['estado_actual'] == 1) ? 0 : 1;

    $sql_toggle_estado = "UPDATE vehiculos SET activo = ? WHERE id = ? AND usuario_id = ?";
    $stmt_toggle_estado = $conn->prepare($sql_toggle_estado);
    $stmt_toggle_estado->bind_param("iii", $nuevo_estado, $vehiculo_id, $admin_id);

    if ($stmt_toggle_estado->execute()) {
        header("Location: my_trucks.php");
        exit();
    } else {
        die("Error al cambiar el estado del vehículo: " . $stmt_toggle_estado->error);
    }
}
*/
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Vehículos</title>
    <link rel="stylesheet" href="styles.css?v=1.1">
    <style>
        .hidden {
            display: none; /* Oculta los elementos */
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        .activo {
            background-color: #8bc34a; /* Verde suave para “Activo” */
        }
        .no_activo {
            background-color: #f44336; /* Rojo suave para “No Activo” */
        }
    </style>
</head>
<body>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; ?>
    <h1>Gestión de Vehículos</h1>

    <!-- Botón para desplegar/ocultar formulario "Añadir Vehículo" -->
    <button id="toggleFormBtn">Añadir camión</button>
    <div id="addVehicleForm" class="hidden" style="margin-top: 20px;">
        <h2>Añadir Vehículo</h2>
        <form method="POST" action="agregar_vehiculo.php" enctype="multipart/form-data">
            <select name="nivel_1" id="nivel_1" required>
                <option value=""><b>Seleccione Tipo Principal</b></option>
                <option value="camion_rigido"><b>Camión Rígido</b></option>
                <option value="cabeza_tractora"><b>Cabeza Tractora</b></option>
                <option value="semirremolque"><b>Semirremolque</b></option>
                <option value="remolque"><b>Remolque</b></option>
            </select>
            <br><br>
            <select name="nivel_2" id="nivel_2" required>
                <option value=""><b>Seleccione Subcategoría</b></option>
            </select>
            <br><br>
            <select name="nivel_3" id="nivel_3">
                <option value=""><b>Seleccione Especificación</b></option>
            </select>

            <br><br>
            <input type="text" name="matricula" placeholder="Matrícula" required>
            <input type="text" name="marca" placeholder="Marca" required>
            <input type="text" name="modelo" placeholder="Modelo" required>
            <input type="number" name="ano_fabricacion" placeholder="Año de Fabricación">
            <br><br>

            <!-- Formas de Carga -->
            <div id="formas_carga" class="hidden">
                <h3>Formas de Carga</h3>
                <input type="checkbox" name="forma_carga_lateral" value="1"> Lateral
                <input type="checkbox" name="forma_carga_detras" value="1"> Detrás
                <input type="checkbox" name="forma_carga_arriba" value="1"> Arriba<br>
            </div>

            <!-- Capacidad y Volumen -->
            <div id="capacidad_field" class="hidden">
                <label for="capacidad_input">Capacidad de Carga (Toneladas):</label>
                <input id="capacidad_input" type="number" step="0.1" name="capacidad">
            </div>
            <div id="volumen_field" class="hidden">
                <label for="volumen_input">Volumen de Carga (m³):</label>
                <input id="volumen_input" type="number" step="0.1" name="volumen">
            </div>

            <!-- Características de Arrastre -->
            <div id="tractora_fields" class="hidden">
                <h3>Características de Arrastre</h3>
                <label for="capacidad_arrastre_input">Capacidad de Arrastre (Toneladas):</label>
                <input id="capacidad_arrastre_input" type="number" step="0.1" name="capacidad_arrastre">
                <br>
                <label for="numero_ejes_input">Número de Ejes:</label>
                <input id="numero_ejes_input" type="number" step="0.1" name="numero_ejes">
            </div>

            <!-- Temperatura controlada (camiones frigoríficos) -->
            <div id="temperatura_field" class="hidden">
                <h3>Temperatura Controlada</h3>
                <input type="checkbox" name="temperatura_controlada" value="1"> Dispone de sistema de frío
            </div>

            <h3>Características Adicionales</h3>
            <input type="checkbox" name="adr" value="1"> Mercancías Peligrosas (ADR)<br>
            <input type="checkbox" name="doble_conductor" value="1"> Doble Conductor<br>
            <input type="checkbox" name="plataforma_elevadora" value="1"> Plataforma Elevadora<br><br>

            <input type="text" name="telefono" placeholder="Teléfono de Contacto"><br><br>
            <textarea name="observaciones" placeholder="Observaciones (Opcional)" rows="4" cols="50"></textarea><br><br>
            <label>Documentos del Vehículo:</label>
            <input type="file" name="documentosVehiculo[]" multiple accept="image/*,application/pdf"><br><br>
            <button type="submit">Guardar Vehículo</button>
            <br>
        </form>
    </div>

    <hr>
    <!-- Filtro de búsqueda de vehículos -->
    <form method="POST" action="my_trucks.php">
        <label for="filtro_vehiculo">Buscar vehículo (Matrícula / Marca / Tipo): </label>
        <input type="text" id="filtro_vehiculo" name="filtro_vehiculo" value="<?php echo htmlspecialchars($filtro_vehiculo); ?>">
        <button type="submit">Buscar</button>
    </form>

    <h2>Listado de vehículos</h2>
    <?php
    if ($resultado_vehiculos->num_rows > 0) {
        echo "<table>";
        echo "<tr>
                <th>Matrícula</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Año</th>
                <th>Tipo Principal</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>";

        while ($fila = $resultado_vehiculos->fetch_assoc()) {
            $estado = $fila['activo'] == 1 ? "Activo" : "No Activo";
            $boton_clase = $fila['activo'] == 1 ? "activo" : "no_activo";
            $boton_texto = $fila['activo'] == 1 ? "Activo" : "No Activo";

            echo "<tr>";
            echo "<td>" . $fila['matricula'] . "</td>";
            echo "<td>" . $fila['marca'] . "</td>";
            echo "<td>" . $fila['modelo'] . "</td>";
            echo "<td>" . $fila['ano_fabricacion'] . "</td>";
            echo "<td>" . $fila['nivel_1'] . "</td>";

            // Botón para cambiar el estado (activo/no_activo)
            echo "<td>
                    <form method='POST' action='cambiar_estado_vehiculo.php' style='display:inline;'>
                        <input type='hidden' name='vehiculo_id' value='" . $fila['id'] . "'>
                        <input type='hidden' name='estado_actual' value='" . $fila['activo'] . "'>
                        <button type='submit' class='$boton_clase'>$boton_texto</button>
                    </form>
                  </td>";

            // Acción ver detalle, editar, etc.
            echo "<td>
                    <a href='ver_detalles_vehiculo.php?vehiculo_id=" . $fila['id'] . "'>Ver Detalles</a>
                  </td>";

            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "No se encontraron vehículos.";
    }
    ?>

    <?php include 'footer.php'; ?>

    <script>
        // Toggle del formulario "Añadir Vehículo"
        const toggleBtn = document.getElementById('toggleFormBtn');
        const addVehicleForm = document.getElementById('addVehicleForm');

        toggleBtn.addEventListener('click', () => {
            if (addVehicleForm.classList.contains('hidden')) {
                addVehicleForm.classList.remove('hidden');
            } else {
                addVehicleForm.classList.add('hidden');
            }
        });

        // Scripts para la lógica de los selects (nivel_1, nivel_2, nivel_3)
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

            // Cambio en nivel_1 => Rellenar nivel_2 y mostrar/ocultar campos
            nivel1Select.addEventListener("change", function () {
                const nivel1Value = nivel1Select.value;
                // Reset subcategorías
                nivel2Select.innerHTML = '<option value="">Seleccione Subcategoría</option>';
                nivel3Select.innerHTML = '<option value="">Seleccione Especificación</option>';

                if (nivel2Options[nivel1Value]) {
                    nivel2Options[nivel1Value].forEach(function (subcat) {
                        let option = document.createElement("option");
                        option.value = subcat;
                        option.text = subcat;
                        nivel2Select.appendChild(option);
                    });
                }

                updateFieldVisibility(nivel1Value);
            });

            // Cambio en nivel_2 => Rellenar nivel_3
            nivel2Select.addEventListener("change", function () {
                const nivel2Value = nivel2Select.value;
                nivel3Select.innerHTML = '<option value="">Seleccione Especificación</option>';

                if (nivel3Options[nivel2Value]) {
                    nivel3Options[nivel2Value].forEach(function (det) {
                        let option = document.createElement("option");
                        option.value = det;
                        option.text = det;
                        nivel3Select.appendChild(option);
                    });
                }
            });

            function resetVisibility() {
                capacidadField.classList.add("hidden");
                volumenField.classList.add("hidden");
                cargaFormas.classList.add("hidden");
                temperaturaField.classList.add("hidden");
                tractoraField.classList.add("hidden");
            }

            function updateFieldVisibility(nivel1Value) {
                resetVisibility();
                switch (nivel1Value) {
                    case "camion_rigido":
                        capacidadField.classList.remove("hidden");
                        volumenField.classList.remove("hidden");
                        cargaFormas.classList.remove("hidden");
                        temperaturaField.classList.remove("hidden");
                        capacidadInput.placeholder = "Capacidad de Carga (Toneladas)";
                        volumenInput.placeholder = "Volumen de Carga (m³)";
                        break;

                    case "cabeza_tractora":
                        tractoraField.classList.remove("hidden");
                        // placeholders
                        arrastreInput.placeholder = "Capacidad de Arrastre (Toneladas)";
                        numeroEjesInput.placeholder = "Número de Ejes";
                        break;

                    case "semirremolque":
                        capacidadField.classList.remove("hidden");
                        volumenField.classList.remove("hidden");
                        cargaFormas.classList.remove("hidden");
                        capacidadInput.placeholder = "Capacidad de Carga (Toneladas)";
                        volumenInput.placeholder = "Volumen de Carga (m³)";
                        break;

                    case "remolque":
                        // Similar a semirremolque, pero sin tractor
                        capacidadField.classList.remove("hidden");
                        volumenField.classList.remove("hidden");
                        cargaFormas.classList.remove("hidden");
                        capacidadInput.placeholder = "Capacidad de Carga (Toneladas)";
                        volumenInput.placeholder = "Volumen de Carga (m³)";
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
