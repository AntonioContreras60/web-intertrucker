<?php
/**********************************************************
 * error_handler.php
 * 
 * Manejo de errores y excepciones en PHP,
 * con almacenamiento en la tabla `php_errores`.
 **********************************************************/

// 1) Ajusta estos datos para tu conexión a la BD.
//    Puedes usar PDO o mysqli. Aquí uso mysqli de ejemplo.
$db_host = 'localhost';  // O el que uses
$db_user = 'tu_usuario';
$db_pass = 'tu_password';
$db_name = 'tu_basedatos';

// (A) Conexión MySQLi
$connErrors = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($connErrors->connect_error) {
    // Si falla esta conexión, no podremos guardar errores en la BD
    // Mostramos un "chivato" para debug
    die("CHIVATO: No se pudo conectar a BD para errores: " . $connErrors->connect_error);
}

// 2) Función para guardar en `php_errores`
function registrarErrorEnBD($conn, $tipo, $mensaje, $archivo, $linea, $backtrace = null) {
    // Preparar la consulta con parámetros
    $sql = "INSERT INTO php_errores
                (tipo_error, mensaje, archivo, linea, fecha, backtrace)
            VALUES (?, ?, ?, ?, NOW(), ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssds", $tipo, $mensaje, $archivo, $linea, $backtrace);
        $stmt->execute();
        $stmt->close();
    } else {
        // Si hay algún problema, mostramos "chivato"
        echo "<p style='color:red'>CHIVATO: Error al preparar insert error => " . $conn->error . "</p>";
    }
}

// 3) Definir nuestro manejador de errores
function miErrorHandler($nivel, $mensaje, $archivo, $linea) {
    // Aquí podemos filtrar si queremos ignorar ciertos errores
    // Por ejemplo, si no quieres notices:
    // if ($nivel === E_NOTICE || $nivel === E_USER_NOTICE) return false;

    // Traducir el $nivel a un texto
    switch ($nivel) {
        case E_ERROR: $tipo = "E_ERROR"; break;
        case E_WARNING: $tipo = "E_WARNING"; break;
        case E_PARSE: $tipo = "E_PARSE"; break;
        case E_NOTICE: $tipo = "E_NOTICE"; break;
        case E_CORE_ERROR: $tipo = "E_CORE_ERROR"; break;
        case E_CORE_WARNING: $tipo = "E_CORE_WARNING"; break;
        case E_COMPILE_ERROR: $tipo = "E_COMPILE_ERROR"; break;
        case E_COMPILE_WARNING: $tipo = "E_COMPILE_WARNING"; break;
        case E_USER_ERROR: $tipo = "E_USER_ERROR"; break;
        case E_USER_WARNING: $tipo = "E_USER_WARNING"; break;
        case E_USER_NOTICE: $tipo = "E_USER_NOTICE"; break;
        case E_STRICT: $tipo = "E_STRICT"; break;
        case E_RECOVERABLE_ERROR: $tipo = "E_RECOVERABLE_ERROR"; break;
        case E_DEPRECATED: $tipo = "E_DEPRECATED"; break;
        case E_USER_DEPRECATED: $tipo = "E_USER_DEPRECATED"; break;
        default: $tipo = "E_UNKNOWN"; break;
    }

    // Un "chivato" en pantalla (opcional):
    echo "<p style='color:red;'>CHIVATO: Se ha producido un error [{$tipo}] en {$archivo} línea {$linea}<br>
          Mensaje: <strong>{$mensaje}</strong></p>";

    // Guardar en BD
    global $connErrors; // uso de la conexión global $connErrors
    $trace = debug_backtrace();
    // Con debug_backtrace() entero, se obtienen arrays muy grandes.
    // Para no saturar, convertimos a string con print_r:
    $traceStr = print_r($trace, true);

    registrarErrorEnBD($connErrors, $tipo, $mensaje, $archivo, $linea, $traceStr);

    /* 
      IMPORTANTE: 
      Devuelve "false" si quieres que PHP continúe con su manejador interno
      Devuelve "true" para indicar que "ya está manejado"
    */
    return true; 
}

// 4) Definir un manejador para excepciones no capturadas
function miExceptionHandler($ex) {
    $tipo = "Excepcion no capturada";
    $mensaje = $ex->getMessage();
    $archivo = $ex->getFile();
    $linea   = $ex->getLine();

    // Un "chivato" en pantalla (opcional):
    echo "<p style='color:red;'>CHIVATO: Excepción no capturada en {$archivo} línea {$linea}<br>
          Mensaje: <strong>{$mensaje}</strong></p>";

    // Guardar en BD
    global $connErrors;
    $traceStr = $ex->getTraceAsString(); // Para excepciones, es más fácil
    registrarErrorEnBD($connErrors, $tipo, $mensaje, $archivo, $linea, $traceStr);

    // Podríamos hacer un exit() o dejar que continue:
    // exit();
}

// 5) Capturar errores fatales al final del script
function miShutdownFunction() {
    $ultimoError = error_get_last();
    if ($ultimoError !== null) {
        $tipo   = $ultimoError['type'];
        $archivo= $ultimoError['file'];
        $linea  = $ultimoError['line'];
        $mensaje= $ultimoError['message'];

        // Manejo similar al handler
        switch ($tipo) {
            case E_ERROR: 
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
                $tipoTxt = "ERROR FATAL";
                break;
            default:
                $tipoTxt = "SHUTDOWN TYPE {$tipo}";
                break;
        }

        echo "<p style='color:red;'>CHIVATO [SHUTDOWN]: Ha ocurrido un error fatal<br>
              Tipo: {$tipoTxt} - {$archivo} línea {$linea}<br>
              Mensaje: <strong>{$mensaje}</strong></p>";

        global $connErrors;
        $traceStr = "No se puede obtener backtrace en error fatal via shutdown.";
        registrarErrorEnBD($connErrors, $tipoTxt, $mensaje, $archivo, $linea, $traceStr);
    }
}

// 6) Activar nuestros manejadores
set_error_handler("miErrorHandler");
set_exception_handler("miExceptionHandler");
register_shutdown_function("miShutdownFunction");

// 7) Opcional: Forzar reporting (por si el hosting lo ignora)
error_reporting(E_ALL);

// 8) Un “chivato” indicando que todo se activó:
echo "<p style='color:blue;'>CHIVATO: Manejadores de errores activados correctamente.</p>";
