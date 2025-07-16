<?php
session_start();  // <--- Asegúrate de iniciarla aquí al comienzo


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}


// Antes de insertar o actualizar el porte, verifica el valor del tipo de palet
$tipo_palet = isset($_POST['tipo_palet']) ? sanitizar($conn, $_POST['tipo_palet']) : 'ninguno';


// Función para buscar en contactos y entidades
function buscarEntidad($conn, $nombre) {
    $sql = "SELECT nombre, direccion, telefono, email, cif FROM contactos WHERE nombre = ?
            UNION
            SELECT nombre, direccion, telefono, email, cif FROM entidades WHERE nombre = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nombre, $nombre);
    $stmt->execute();
    return $stmt->get_result();
}

// Búsqueda de Expedidor
if (isset($_POST['buscar_expedidor'])) {
    $expedidor_nombre = sanitizar($conn, $_POST['recogida_expedidor_nombre']);
    $result = buscarEntidad($conn, $expedidor_nombre);

    if ($result->num_rows > 0) {
        $expedidor = $result->fetch_assoc();
        // Rellenar los campos con los datos encontrados
        $_POST['recogida_direccion'] = $expedidor['direccion'];
        $_POST['recogida_expedidor_telefono'] = $expedidor['telefono'];
        $_POST['recogida_expedidor_email'] = $expedidor['email'];
        $_POST['recogida_expedidor_cif'] = $expedidor['cif'];
    } else {
        echo "Expedidor no encontrado.";
    }
}

// Búsqueda de Receptor
if (isset($_POST['buscar_receptor'])) {
    $receptor_nombre = sanitizar($conn, $_POST['entrega_receptor_nombre']);
    $result = buscarEntidad($conn, $receptor_nombre);

    if ($result->num_rows > 0) {
        $receptor = $result->fetch_assoc();
        // Rellenar los campos con los datos encontrados
        $_POST['entrega_direccion'] = $receptor['direccion'];
        $_POST['entrega_receptor_telefono'] = $receptor['telefono'];
        $_POST['entrega_receptor_email'] = $receptor['email'];
        $_POST['entrega_receptor_cif'] = $receptor['cif'];
    } else {
        echo "Receptor no encontrado.";
    }
}

// Búsqueda de Cliente
if (isset($_POST['buscar_cliente'])) {
    $cliente_nombre = sanitizar($conn, $_POST['cliente_nombre']);
    $result = buscarEntidad($conn, $cliente_nombre);

    if ($result->num_rows > 0) {
        $cliente = $result->fetch_assoc();
        // Rellenar los campos con los datos encontrados
        $_POST['cliente_direccion'] = $cliente['direccion'];
        $_POST['cliente_telefono'] = $cliente['telefono'];
        $_POST['cliente_email'] = $cliente['email'];
        $_POST['cliente_cif'] = $cliente['cif'];
    } else {
        echo "Cliente no encontrado.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Porte</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function abrirVentanaBusqueda(tipo) {
            let url = 'buscar_entidad.php?tipo=' + tipo;
            window.open(url, 'BuscarEntidad', 'width=800,height=600');
        }

        // Función para actualizar los campos ocultos con el ID y tipo seleccionado
        function setEntidad(tipo, id, nombre) {
            if (tipo === 'expedidor') {
                document.getElementById('expedidor_id').value = id;
                document.getElementById('tipo_expedidor').value = 'usuario'; // o 'entidad' dependiendo del origen
                document.getElementById('recogida_expedidor_nombre').value = nombre;
            } else if (tipo === 'destinatario') {
                document.getElementById('destinatario_id').value = id;
                document.getElementById('tipo_destinatario').value = 'usuario'; // o 'entidad'
                document.getElementById('entrega_receptor_nombre').value = nombre;
            } else if (tipo === 'cliente') {
                document.getElementById('cliente_id').value = id;
                document.getElementById('tipo_cliente').value = 'usuario'; // o 'entidad'
                document.getElementById('cliente_nombre').value = nombre;
            }
        }
    </script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h1>Crear Nuevo Porte</h1>
        <form action="guardar_porte.php" method="POST" enctype="multipart/form-data">
            <fieldset>
                <legend class="section-title"><strong>La Mercancía</strong></legend>
                <label for="descripcion_mercancia">Naturaleza y Embalaje:</label>
                <textarea id="descripcion_mercancia" name="descripcion_mercancia" required></textarea><br>

                <label for="conservacion_mercancia">Conservación:</label>
                <select id="conservacion_mercancia" name="conservacion_mercancia" onchange="toggleTemperaturaFields()" required>
                    <option value="Ninguna">Sin condiciones específicas</option>
                    <option value="Refrigerado">Refrigerado</option>
                    <option value="Congelado">Congelado</option>
                    <option value="Isotérmico">Isotérmico</option>
                    <option value="Seco">Seco</option>
                    <option value="ATM">Atmósfera Modificada (ATM)</option>
                    <option value="Personalizado">Rango de Temperaturas Personalizado</option>
                </select><br>

                <label for="cadena_frio">Cadena de frío:</label>
                <input type="checkbox" id="cadena_frio" name="cadena_frio"><br>             
              
                <div id="temperatura_custom" style="display: none;">
                    <label for="temperatura_minima">Temperatura mínima (°C):</label>
                    <input type="number" id="temperatura_minima" name="temperatura_minima" step="0.01"><br>

                    <label for="temperatura_maxima">Temperatura máxima (°C):</label>
                    <input type="number" id="temperatura_maxima" name="temperatura_maxima" step="0.01"><br>
                </div>

                <script>
                    window.onload = function() {
                        toggleTemperaturaFields();
                    };

                    function toggleTemperaturaFields() {
                        const selectConservacion = document.getElementById('conservacion_mercancia');
                        const tempFields = document.getElementById('temperatura_custom');
                        
                        if (selectConservacion.value === 'Personalizado') {
                            tempFields.style.display = 'block';
                        } else {
                            tempFields.style.display = 'none';
                        }
                    }
                </script>

                <label for="adr_mercancia">ADR (Mercancías Peligrosas):</label>
                <input type="checkbox" id="adr_mercancia" name="adr_mercancia"><br>

<label for="tipo_palet">Tipo de Palet:</label><br>
<select id="tipo_palet" name="tipo_palet">
    <option value="">Seleccionar Tipo de Palet</option>
    <option value="europeo">Europeo (120x80 cm)</option>
    <option value="americano">Americano (120x100 cm)</option>
    <option value="ninguno">Ninguno</option>
</select>


                <label for="cantidad">Nº Palets:</label>
                <input type="text" id="cantidad" name="cantidad"><br>

                <label for="peso_total">Peso Total (kg):</label>
                <input type="number" id="peso_total" name="peso_total" step="0.01"><br>

                <label for="volumen_total">Volumen Total (m³):</label>
                <input type="number" id="volumen_total" name="volumen_total" step="0.01"><br>

                <label for="tipo_carga">Tipo de Carga:</label>
                <select id="tipo_carga" name="tipo_carga">
                    <option value="Grupaje">Grupaje</option>
                    <option value="Camión Entero">Camión Entero</option>
                </select><br>

                <label for="observaciones">Observaciones:</label>
                <textarea id="observaciones" name="observaciones"></textarea><br>
            </fieldset>
            <fieldset>
                <legend class="section-title"><strong>Documentos adjuntos</strong></legend>
                <input type="file" name="documentos_porte[]" multiple accept=".pdf,.jpg,.jpeg,.png">
            </fieldset>


            <!-- Opciones de Camión -->
            <fieldset>
                <legend class="section-title"><strong>Opciones de Camión</strong></legend>
                <label for="tipo_camion_detalle">Detalle del Tipo de Camión:</label>
                <select id="tipo_camion_detalle" name="tipo_camion_detalle" required>
                    <option value="camión cerrado">Camión Cerrado</option>
                    <option value="camión abierto">Camión Abierto</option>
                    <option value="camión frigorífico">Camión Frigorífico</option>
                    <option value="camión cisterna">Camión Cisterna</option>
                    <option value="camión de animales">Camión de Animales</option>
                    <option value="camión de plataforma">Camión de Plataforma</option>
                    <option value="camión de lona">Camión de Lona</option>
                </select><br>

                <label for="dimensiones_maximas">Dimensiones Máximas (Largo x Ancho x Alto en metros):</label>
                <input type="text" id="dimensiones_maximas" name="dimensiones_maximas" placeholder="Ej: 12 x 2.5 x 3"><br>

                <label for="intercambio_palets">Intercambio de Palets:</label>
                <input type="checkbox" id="intercambio_palets" name="intercambio_palets"><br>

                <label for="paletizado">Paletizado:</label>
                <input type="checkbox" id="paletizado" name="paletizado"><br>
                
                <label for="no_transbordos">No Permito Transbordos:</label>
                <input type="checkbox" id="no_transbordos" name="no_transbordos"><br>
                
                <label for="no_delegacion_transporte">No Permito Delegación de Transporte:</label>
                <input type="checkbox" id="no_delegacion_transporte" name="no_delegacion_transporte"><br>
                
                <label for="no_se_puede_remontar">No Se Puede Remontar:</label>
                <input type="checkbox" id="no_se_puede_remontar" name="no_se_puede_remontar"><br>
            </fieldset>
            <!-- Sección de Recogida -->
            <fieldset>
                <legend class="section-title"><strong>Recogida</strong></legend>
                <button type="submit" onclick="abrirVentanaBusqueda('expedidor')">Buscar Expedidor</button><br>

                <label for="recogida_expedidor_nombre">Nombre del Expedidor:</label>
                <input type="text" id="recogida_expedidor_nombre" name="recogida_expedidor_nombre" required><br>

                <label for="recogida_direccion">Dirección de Recogida:</label>
                <input type="text" id="recogida_direccion" name="recogida_direccion" required><br>

                <label for="recogida_expedidor_telefono">Teléfono del Expedidor:</label>
                <input type="tel" id="recogida_expedidor_telefono" name="recogida_expedidor_telefono"><br>

                <label for="recogida_expedidor_email">Email del Expedidor:</label>
                <input type="email" id="recogida_expedidor_email" name="recogida_expedidor_email"><br>

                <label for="recogida_expedidor_cif">CIF/NIF/NIE del Expedidor:</label>
                <input type="text" id="recogida_expedidor_cif" name="recogida_expedidor_cif"><br>

                <label for="recogida_fecha">Fecha:</label>
                <input type="date" id="recogida_fecha" name="recogida_fecha" required><br>

                <label for="recogida_hora_inicio">Horario de Recogida:</label>
                <input type="time" id="recogida_hora_inicio" name="recogida_hora_inicio" required>
                <span> a </span>
                <input type="time" id="recogida_hora_fin" name="recogida_hora_fin"><br>

                <label for="observaciones_recogida">Observaciones de Recogida:</label>
                <textarea id="observaciones_recogida" name="observaciones_recogida"></textarea><br>
            </fieldset>

            <!-- Sección de Entrega -->
            <fieldset>
                <legend class="section-title"><strong>Entrega</strong></legend>
                <button type="submit" onclick="abrirVentanaBusqueda('receptor')">Buscar Receptor</button><br>

                <label for="entrega_receptor_nombre">Nombre del Receptor:</label>
                <input type="text" id="entrega_receptor_nombre" name="entrega_receptor_nombre" required><br>

                <label for="entrega_direccion">Dirección de Entrega:</label>
                <input type="text" id="entrega_direccion" name="entrega_direccion" required><br>

                <label for="entrega_receptor_telefono">Teléfono del Receptor:</label>
                <input type="tel" id="entrega_receptor_telefono" name="entrega_receptor_telefono"><br>

                <label for="entrega_receptor_email">Email del Receptor:</label>
                <input type="email" id="entrega_receptor_email" name="entrega_receptor_email"><br>

                <label for="entrega_receptor_cif">CIF/NIF/NIE del Receptor:</label>
                <input type="text" id="entrega_receptor_cif" name="entrega_receptor_cif"><br>

                <label for="entrega_fecha">Fecha de Entrega:</label>
                <input type="date" id="entrega_fecha" name="entrega_fecha" required><br>

                <label for="entrega_hora_inicio">Horario de Entrega:</label>
                <input type="time" id="entrega_hora_inicio" name="entrega_hora_inicio" required>
                <span> a </span>
                <input type="time" id="entrega_hora_fin" name="entrega_hora_fin"><br>
            </fieldset>

            <!-- Sección de Cliente -->
            <fieldset>
                <legend class="section-title"><strong>Cliente (Opcional)</strong></legend>
                <!-- Botón Buscar Cliente -->
                <button type="button" onclick="abrirVentanaBusqueda('cliente')">Buscar Cliente</button><br>

                <label for="cliente_nombre">Nombre del Cliente:</label>
                <input type="text" id="cliente_nombre" name="cliente_nombre"><br>

                <label for="cliente_direccion">Dirección del Cliente:</label>
                <input type="text" id="cliente_direccion" name="cliente_direccion"><br>

                <label for="cliente_telefono">Teléfono del Cliente:</label>
                <input type="tel" id="cliente_telefono" name="cliente_telefono"><br>

                <label for="cliente_email">Email del Cliente:</label>
                <input type="email" id="cliente_email" name="cliente_email"><br>

                <label for="cliente_cif">CIF/NIF/NIE del Cliente:</label>
                <input type="text" id="cliente_cif" name="cliente_cif"><br>
            </fieldset>

            <!-- Campos ocultos para almacenar ID y tipo de cada entidad/usuario -->
            <input type="hidden" id="expedidor_id" name="expedidor_id">
            <input type="hidden" id="tipo_expedidor" name="tipo_expedidor">

            <input type="hidden" id="destinatario_id" name="destinatario_id">
            <input type="hidden" id="tipo_destinatario" name="tipo_destinatario">

            <input type="hidden" id="cliente_id" name="cliente_id">
            <input type="hidden" id="tipo_cliente" name="tipo_cliente">

            <!-- Botones de acción -->
            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
            <button type="submit" name="guardar_porte">Guardar Porte</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
