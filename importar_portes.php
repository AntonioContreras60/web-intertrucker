<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
session_start();

// -----------------------------------------------------------------------------
// 1. Comprobamos que el usuario tiene sesión iniciada
// -----------------------------------------------------------------------------
if (!isset($_SESSION['usuario_id'])) {
    die("Sesión no iniciada");
}
$usuarioId = (int)$_SESSION['usuario_id'];

// -----------------------------------------------------------------------------
// 2. Si se ha enviado el formulario, procesamos la subida
// -----------------------------------------------------------------------------
if (isset($_POST['importar']) && isset($_FILES['fichero_datos'])) {
    // ------------------------------------------------
    // 2.1. Validar el tipo de porte
    // ------------------------------------------------
    $modo = $_POST['tipo_porte'] ?? 'propio'; // 'propio' | 'recibido_externo'
    if (!in_array($modo, ['propio','recibido_externo'])) {
        $modo = 'propio';
    }

    // ------------------------------------------------
    // 2.2. Datos de la empresa externa (si procede)
    // ------------------------------------------------
    $cedente = [
      'nombre'   => trim($_POST['cedente_nombre']   ?? ''),
      'cif'      => trim($_POST['cedente_cif']      ?? ''),
      'telefono' => trim($_POST['cedente_telefono'] ?? ''),
      'email'    => trim($_POST['cedente_email']    ?? ''),
      'obs'      => trim($_POST['cedente_observ']   ?? '')
    ];

    // ------------------------------------------------
    // 2.3. Validamos archivo subido: tamaño y extensión
    // ------------------------------------------------
    $maxSize = 5 * 1024 * 1024; // 5 MB
    if ($_FILES['fichero_datos']['size'] > $maxSize) {
        die("El archivo excede los 5 MB permitidos");
    }

    $tmp  = $_FILES['fichero_datos']['tmp_name'];
    $name = $_FILES['fichero_datos']['name'];

    if (!is_uploaded_file($tmp)) {
        die("Archivo no válido");
    }

    // Solo permitimos CSV o XML basándonos en la extensión
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv','xml'])) {
        die("Formato de fichero no admitido. Sube un .csv o .xml");
    }

    // (Opcional) Podríamos chequear MIME real, p.ej. con finfo_file().

    // ------------------------------------------------
    // 2.4. Conexión PDO + transacción
    // ------------------------------------------------
    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    $dbName = getenv('DB_NAME');
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }

    // Iniciamos la transacción:
    $pdo->beginTransaction();

    // ------------------------------------------------
    // 2.5. Preparamos las sentencias
    // ------------------------------------------------

    // Insertar un registro en `portes`
    $sqlPorte = "INSERT INTO portes (
        usuario_creador_id, num_referencia, mercancia_descripcion,
        fecha_recogida, recogida_hora_inicio, localizacion_recogida,
        fecha_entrega, entrega_hora_inicio, localizacion_entrega,
        adr, no_transbordos, cadena_frio,
        paletizado, intercambio_palets, tipo_camion,
        observaciones
    ) VALUES (
        :usuario_creador_id, :num_referencia, :mercancia_descripcion,
        :fecha_recogida, :recogida_hora_inicio, :localizacion_recogida,
        :fecha_entrega, :entrega_hora_inicio, :localizacion_entrega,
        :adr, :no_transbordos, :cadena_frio,
        :paletizado, :intercambio_palets, :tipo_camion,
        :observaciones
    )";
    $stmtPorte = $pdo->prepare($sqlPorte);

    // Insertar (o reutilizar) la empresa cedente
    $sqlCedente = "INSERT INTO entidad_cedente
                   (nombre, cif, telefono, email, observaciones)
                   VALUES (:nombre, :cif, :telefono, :email, :obs)";
    $stmtCedente = $pdo->prepare($sqlCedente);

    // Tabla puente
    $sqlPuente = "INSERT INTO portes_importados (porte_id, cedente_id)
                  VALUES (:porte_id, :cedente_id)";
    $stmtPuente = $pdo->prepare($sqlPuente);

    // (Opcional) Chequear duplicados
    $sqlCheckDup = "SELECT id FROM portes WHERE num_referencia = :ref LIMIT 1";
    $stmtCheckDup = $pdo->prepare($sqlCheckDup);

    // ------------------------------------------------
    // 2.6. Leer e insertar filas (CSV o XML)
    // ------------------------------------------------
    $ok   = 0;  // conteo portes insertados
    $fail = 0;  // conteo fallos

    // Para simplificar, si $ext=csv lo procesamos como CSV;
    // si no, asumimos XML
    if ($ext === 'csv') {
        $h = fopen($tmp, 'r');
        if (!$h) {
            $pdo->rollBack();
            die("No se pudo abrir el CSV");
        }

        // Leemos cabecera
        $header = fgetcsv($h, 0, ';');
        if (!$header) {
            $pdo->rollBack();
            die("CSV vacío o sin cabecera");
        }

        // Normalizamos cabeceras a minúscula
        $header = array_map(function($col){
            return mb_strtolower(trim($col));
        }, $header);
        
        while (($row = fgetcsv($h, 0, ';')) !== false) {
            if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                continue; // línea vacía
            }

            // "Mapeamos" la fila a un array asociativo
            $data = parseRow($header, $row);
            if (!insertarPorte($pdo, $stmtPorte, $stmtCheckDup, $stmtCedente, $stmtPuente, $data, $modo, $cedente, $usuarioId)) {
                $fail++;
            } else {
                $ok++;
            }
        }
        fclose($h);

    } else {
        // Proceso de XML
        $xml = @simplexml_load_file($tmp);
        if (!$xml) {
            $pdo->rollBack();
            die("XML inválido o no se pudo leer");
        }
        foreach ($xml->Porte as $nodo) {
            // Convertimos cada hijo a (tag => valor)
            $data = parseXmlNode($nodo);
            if (!insertarPorte($pdo, $stmtPorte, $stmtCheckDup, $stmtCedente, $stmtPuente, $data, $modo, $cedente, $usuarioId)) {
                $fail++;
            } else {
                $ok++;
            }
        }
    }

    // Si todo fue bien, confirmamos transacción
    $pdo->commit();

    // Mostramos resumen
    echo "<h2>Importación finalizada</h2>";
    echo "<p>Portes insertados: $ok &nbsp;&nbsp;|&nbsp;&nbsp; Fallidos: $fail</p>";
    echo "<a href='https://intertrucker.net/portes_nuevos_propios.php' class='btn btn-primary'>
            Volver
          </a>";
    exit;
}

// -----------------------------------------------------------------------------
// Formulario HTML (si no se ha enviado nada, mostramos el form)
// -----------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Portes</title>
    <style>.hidden{display:none}</style>
</head>
<body>
<h1>Importar Portes</h1>
<form method="post" enctype="multipart/form-data">
  <!-- Para limitar a 5MB en el lado del cliente -->
  <input type="hidden" name="MAX_FILE_SIZE" value="5242880">

  <label>Archivo (CSV o XML):</label><br>
  <input type="file" name="fichero_datos" required><br><br>

  <label>Tipo de porte:</label><br>
  <input type="radio" name="tipo_porte" value="propio"  id="tProp" checked> Propio<br>
  <input type="radio" name="tipo_porte" value="recibido_externo" id="tExt"> Recibido Externo<br><br>

  <fieldset id="cedenteBox" class="hidden">
    <legend>Datos empresa externa</legend>
    Nombre<br><input name="cedente_nombre" style="width:250px"><br>
    CIF<br><input name="cedente_cif"><br>
    Teléfono<br><input name="cedente_telefono"><br>
    Email<br><input name="cedente_email" style="width:250px"><br>
    Observaciones<br><textarea name="cedente_observ"></textarea>
  </fieldset><br>

  <button type="submit" name="importar">Importar</button>
</form>

<script>
const tProp=document.getElementById('tProp');
const tExt =document.getElementById('tExt');
const box  =document.getElementById('cedenteBox');
function toggle(){ box.classList.toggle('hidden',!tExt.checked);}
tProp.addEventListener('change',toggle);
tExt.addEventListener('change',toggle);
toggle();
</script>
</body>
</html>

<?php
// *****************************************************************************
// Funciones auxiliares
// *****************************************************************************

/**
 * parseRow()
 * Dada la cabecera normalizada y la fila, devuelve un array asociativo con
 * las columnas que nos interesan: num_referencia, mercancia, fechas...
 * Ajusta a tus nombres reales de columnas.
 */
function parseRow(array $header, array $row): array {
    $data = [
        'num_referencia'        => '',
        'mercancia_descripcion' => '',
        'fecha_recogida'        => null,
        'recogida_hora_inicio'  => null,
        'localizacion_recogida' => '',
        'fecha_entrega'         => null,
        'entrega_hora_inicio'   => null,
        'localizacion_entrega'  => '',
        'adr'                   => 0,
        'no_transbordos'        => 0,
        'cadena_frio'           => 0,
        'paletizado'            => 0,
        'intercambio_palets'    => 0,
        'tipo_camion'           => '',
        'observaciones'         => '',
    ];

    // Ejemplo: si en el CSV hay una columna "referencia", la volcamos a data
    // El resto, igual que hacías con tu $mapa. Aquí lo simplifico.
    foreach ($header as $i => $col) {
        $val = trim($row[$i] ?? '');
        switch ($col) {
            case 'referencia':
            case 'num_referencia':
                $data['num_referencia'] = $val;
                break;

            case 'mercancia':
            case 'mercancia_descripcion':
                $data['mercancia_descripcion'] = $val;
                break;

            case 'recogida':
            case 'origen':
                processFechaHoraLoc($data, $val, 'recogida');
                break;

            case 'entrega':
            case 'destino':
                processFechaHoraLoc($data, $val, 'entrega');
                break;

            case 'adr':
                $data['adr'] = boolTextToInt($val);
                break;

            default:
                // Si algo no encaja, podemos meterlo en observaciones
                if ($val !== '') {
                    $data['observaciones'] .= "[$col=$val] ";
                }
                break;
        }
    }

    return $data;
}

/**
 * parseXmlNode()
 * Versión para XML: recorremos <Porte> y <campos> hijo a hijo
 */
function parseXmlNode(SimpleXMLElement $nodo): array {
    $data = [
        'num_referencia'        => '',
        'mercancia_descripcion' => '',
        'fecha_recogida'        => null,
        'recogida_hora_inicio'  => null,
        'localizacion_recogida' => '',
        'fecha_entrega'         => null,
        'entrega_hora_inicio'   => null,
        'localizacion_entrega'  => '',
        'adr'                   => 0,
        'no_transbordos'        => 0,
        'cadena_frio'           => 0,
        'paletizado'            => 0,
        'intercambio_palets'    => 0,
        'tipo_camion'           => '',
        'observaciones'         => '',
    ];

    foreach ($nodo->children() as $tag => $v) {
        $val = trim((string)$v);
        $tag = strtolower($tag);

        switch ($tag) {
            case 'referencia':
            case 'num_referencia':
                $data['num_referencia'] = $val;
                break;
            case 'mercancia':
            case 'mercancia_descripcion':
                $data['mercancia_descripcion'] = $val;
                break;
            case 'recogida':
            case 'origen':
                processFechaHoraLoc($data, $val, 'recogida');
                break;
            case 'entrega':
            case 'destino':
                processFechaHoraLoc($data, $val, 'entrega');
                break;
            case 'adr':
                $data['adr'] = boolTextToInt($val);
                break;
            default:
                if ($val !== '') {
                    $data['observaciones'] .= "[$tag=$val] ";
                }
                break;
        }
    }

    return $data;
}

/**
 * processFechaHoraLoc()
 * Decide si $val es fecha (dd/mm/yyyy), hora (HH:mm) o un texto (localización)
 * y lo asigna a $data.
 */
function processFechaHoraLoc(array &$data, string $val, string $tipo): void {
    if (isFecha($val)) {
        $data["fecha_$tipo"] = dateYmd($val);
    } elseif (isHora($val)) {
        $data["{$tipo}_hora_inicio"] = fixHora($val);
    } else {
        $data["localizacion_$tipo"] = $val;
    }
}

/**
 * insertarPorte()
 * Ejecuta el insert en `portes` usando la sentencia preparada $stmtPorte,
 * luego (si $modo='recibido_externo') inserta/reutiliza entidad_cedente y crea
 * la relación en portes_importados.
 */
function insertarPorte(
    PDO $pdo,
    PDOStatement $stmtPorte,
    PDOStatement $stmtCheckDup,
    PDOStatement $stmtCedente,
    PDOStatement $stmtPuente,
    array $data,
    string $modo,
    array $cedente,
    int $usuarioId
): bool
{
    // --------------------------------------------------
    // 1. Si hay un "num_referencia", chequeamos duplicado
    // --------------------------------------------------
    if (!empty($data['num_referencia'])) {
        $stmtCheckDup->execute([':ref' => $data['num_referencia']]);
        $existe = $stmtCheckDup->fetch();
        if ($existe) {
            // Ya existe un porte con esa referencia
            return false; 
        }
    }

    // --------------------------------------------------
    // 2. Insertar en portes
    // --------------------------------------------------
    try {
        $stmtPorte->execute([
            ':usuario_creador_id'  => $usuarioId,
            ':num_referencia'      => $data['num_referencia'],
            ':mercancia_descripcion'=> $data['mercancia_descripcion'] ?? '',
            ':fecha_recogida'      => $data['fecha_recogida'],
            ':recogida_hora_inicio'=> $data['recogida_hora_inicio'],
            ':localizacion_recogida'=> $data['localizacion_recogida'] ?? '',
            ':fecha_entrega'       => $data['fecha_entrega'],
            ':entrega_hora_inicio' => $data['entrega_hora_inicio'],
            ':localizacion_entrega'=> $data['localizacion_entrega'] ?? '',
            ':adr'                 => $data['adr'] ?? 0,
            ':no_transbordos'      => $data['no_transbordos'] ?? 0,
            ':cadena_frio'         => $data['cadena_frio'] ?? 0,
            ':paletizado'          => $data['paletizado'] ?? 0,
            ':intercambio_palets'  => $data['intercambio_palets'] ?? 0,
            ':tipo_camion'         => $data['tipo_camion'] ?? '',
            ':observaciones'       => $data['observaciones'] ?? '',
        ]);
    } catch (PDOException $e) {
        // Aquí podríamos loguear el error en una tabla o fichero
        return false;
    }
    $porteId = $pdo->lastInsertId();

    // --------------------------------------------------
    // 3. Si es externo, metemos en cedente + puente
    // --------------------------------------------------
    if ($modo === 'recibido_externo') {
        // (OJO) Aquí podrías “reutilizar cedente” si ya existe con mismo nombre/cif:
        // SELECT id FROM entidad_cedente WHERE cif=? OR nombre=?...
        // De momento, insertamos siempre.
        try {
            $stmtCedente->execute([
                ':nombre'   => $cedente['nombre'],
                ':cif'      => $cedente['cif'],
                ':telefono' => $cedente['telefono'],
                ':email'    => $cedente['email'],
                ':obs'      => $cedente['obs'],
            ]);
            $cedenteId = $pdo->lastInsertId();

            $stmtPuente->execute([
                ':porte_id'   => $porteId,
                ':cedente_id' => $cedenteId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    return true;
}

// -----------------------------------------------------------------------------
// Pequeñas funciones de ayuda
// -----------------------------------------------------------------------------
function isFecha(string $val): bool {
    // 1 o 2 dígitos de día/mes, 4 de año, con barras
    return (bool)preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $val);
}
function dateYmd(string $val): string {
    // Convierte dd/mm/yyyy -> yyyy-mm-dd usando DateTime
    $d = DateTime::createFromFormat('d/m/Y', $val);
    return $d ? $d->format('Y-m-d') : '';
}

function isHora(string $val): bool {
    // h:mm(:ss) opcional
    return (bool)preg_match('/^\d{1,2}:\d{1,2}(\:\d{1,2})?$/', $val);
}
function fixHora(string $val): string {
    // Normaliza a HH:MM:SS
    $parts = explode(':', $val);
    $h = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    $m = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
    $s = isset($parts[2]) ? str_pad($parts[2], 2, '0', STR_PAD_LEFT) : '00';
    return "$h:$m:$s";
}

/**
 * boolTextToInt()
 * Convierte “sí”, “yes”, “true”, etc. a 1 y “no”, “false” a 0
 */
function boolTextToInt(string $s): int {
    $s = mb_strtolower(trim($s));
    $yes = ["si","sí","yes","oui","ja","sim","true","1"];
    $no  = ["no","false","nein","non","nao","não","0"];
    if (in_array($s, $yes)) return 1;
    if (in_array($s, $no))  return 0;
    // por defecto, 0
    return 0;
}
?>
