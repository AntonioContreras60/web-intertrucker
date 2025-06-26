<?php
session_start();
include 'conexion.php'; // Aquí se supone que creas $conn, la conexión a la BD

// Definimos la función sanitizar(...) para usarla si es necesario
function sanitizar($conn, $valor) {
    return $conn->real_escape_string(trim($valor));
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validar el token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        die("Error: Solicitud duplicada o inválida.");
    }
    // Destruir el token tras la validación
    unset($_SESSION['form_token']);

    // Procesar según el botón pulsado
    if (!isset($_POST['guardar_porte'])) {
        die("Error: No se envió el formulario correctamente. Intente nuevamente.");
    }
}

// Función para buscar o guardar la entidad o contacto (usuario o entidad)
function buscar_o_guardar_empresa($conn, $nombre, $direccion, $telefono, $email, $cif, $usuario_creador_id) {
    // Buscar en usuarios
    $sql_buscar_usuario = "SELECT id FROM usuarios WHERE cif = ?";
    $stmt = $conn->prepare($sql_buscar_usuario);
    if (!$stmt) {
        die("Error en consulta `sql_buscar_usuario`: " . $conn->error);
    }
    $stmt->bind_param("s", $cif);
    $stmt->execute();
    $resultado_usuario = $stmt->get_result();

    if ($resultado_usuario->num_rows > 0) {
        // Si el usuario existe, verificar si es contacto
        $usuario_id = $resultado_usuario->fetch_assoc()['id'];
        $sql_buscar_contacto = "SELECT contacto_usuario_id
                                FROM contactos
                                WHERE usuario_id = ?
                                  AND contacto_usuario_id = ?";
        $stmt = $conn->prepare($sql_buscar_contacto);
        if (!$stmt) {
            die("Error en consulta `sql_buscar_contacto`: " . $conn->error);
        }
        $stmt->bind_param("ii", $usuario_creador_id, $usuario_id);
        $stmt->execute();
        $resultado_contacto = $stmt->get_result();

        if ($resultado_contacto->num_rows > 0) {
            // Si ya es contacto
            return ['id' => $usuario_id, 'tipo' => 'usuario'];
        } else {
            // Si no es contacto, agregarlo
            $sql_agregar_contacto = "INSERT INTO contactos (usuario_id, contacto_usuario_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql_agregar_contacto);
            if (!$stmt) {
                die("Error en consulta `sql_agregar_contacto`: " . $conn->error);
            }
            $stmt->bind_param("ii", $usuario_creador_id, $usuario_id);
            $stmt->execute();
            return ['id' => $usuario_id, 'tipo' => 'usuario'];
        }
    } else {
        // Buscar en entidades
        $sql_buscar_entidad = "SELECT id FROM entidades WHERE cif = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql_buscar_entidad);
        if (!$stmt) {
            die("Error en consulta `sql_buscar_entidad`: " . $conn->error);
        }
        $stmt->bind_param("si", $cif, $usuario_creador_id);
        $stmt->execute();
        $resultado_entidad = $stmt->get_result();

        if ($resultado_entidad->num_rows > 0) {
            return ['id' => $resultado_entidad->fetch_assoc()['id'], 'tipo' => 'entidad'];
        } else {
            // Si no existe como entidad, crear una nueva
            $sql_insertar_entidad = "INSERT INTO entidades (nombre, direccion, telefono, email, cif, usuario_id) 
                                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insertar_entidad);
            if (!$stmt) {
                die("Error en consulta `sql_insertar_entidad`: " . $conn->error);
            }
            $stmt->bind_param("sssssi", $nombre, $direccion, $telefono, $email, $cif, $usuario_creador_id);
            if ($stmt->execute()) {
                return ['id' => $conn->insert_id, 'tipo' => 'entidad'];
            } else {
                die("Error al insertar entidad: " . $conn->error);
            }
        }
    }
}

// Procesar la solicitud POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_creador_id = $_SESSION['usuario_id'];

    // Recibir y sanitizar los datos del formulario
    $mercancia_descripcion = $conn->real_escape_string($_POST['descripcion_mercancia']);
    $mercancia_conservacion = $conn->real_escape_string($_POST['conservacion_mercancia']);
    $cadena_frio = isset($_POST['cadena_frio']) ? 1 : 0;
    $adr = isset($_POST['adr_mercancia']) ? 1 : 0;
    $tipo_palet = isset($_POST['tipo_palet']) ? $conn->real_escape_string($_POST['tipo_palet']) : 'ninguno';
    $cantidad = isset($_POST['cantidad']) ? $conn->real_escape_string($_POST['cantidad']) : null;
    $peso_total = isset($_POST['peso_total']) ? $conn->real_escape_string($_POST['peso_total']) : null;
    $volumen_total = isset($_POST['volumen_total']) ? $conn->real_escape_string($_POST['volumen_total']) : null;
    $tipo_carga = isset($_POST['tipo_carga']) ? $conn->real_escape_string($_POST['tipo_carga']) : null;
    $observaciones = isset($_POST['observaciones']) ? $conn->real_escape_string($_POST['observaciones']) : null;

    // Recogida y entrega
    $nombre_expedidor = isset($_POST['recogida_expedidor_nombre'])
        ? $conn->real_escape_string($_POST['recogida_expedidor_nombre'])
        : '';

    $recogida_direccion = isset($_POST['recogida_direccion'])
        ? $conn->real_escape_string($_POST['recogida_direccion'])
        : null;
    $recogida_fecha = isset($_POST['recogida_fecha'])
        ? $conn->real_escape_string($_POST['recogida_fecha'])
        : null;
    $recogida_hora_inicio = isset($_POST['recogida_hora_inicio'])
        ? $conn->real_escape_string($_POST['recogida_hora_inicio'])
        : null;
    $recogida_hora_fin = isset($_POST['recogida_hora_fin'])
        ? $conn->real_escape_string($_POST['recogida_hora_fin'])
        : null;
    $observaciones_recogida = isset($_POST['observaciones_recogida'])
        ? $conn->real_escape_string($_POST['observaciones_recogida'])
        : null;

    $nombre_destinatario = isset($_POST['entrega_receptor_nombre'])
        ? $conn->real_escape_string($_POST['entrega_receptor_nombre'])
        : '';
    $entrega_direccion = isset($_POST['entrega_direccion'])
        ? $conn->real_escape_string($_POST['entrega_direccion'])
        : null;
    $entrega_fecha = isset($_POST['entrega_fecha'])
        ? $conn->real_escape_string($_POST['entrega_fecha'])
        : null;
    $entrega_hora_inicio = isset($_POST['entrega_hora_inicio'])
        ? $conn->real_escape_string($_POST['entrega_hora_inicio'])
        : null;
    $entrega_hora_fin = isset($_POST['entrega_hora_fin'])
        ? $conn->real_escape_string($_POST['entrega_hora_fin'])
        : null;
    $observaciones_entrega = isset($_POST['observaciones_entrega'])
        ? $conn->real_escape_string($_POST['observaciones_entrega'])
        : null;

    // Otros datos
    $no_transbordos = isset($_POST['no_transbordos']) ? 1 : 0;
    $no_delegacion_transporte = isset($_POST['no_delegacion_transporte']) ? 1 : 0;
    $se_puede_remontar = isset($_POST['se_puede_remontar']) ? 1 : 0;
    $dimensiones_maximas = isset($_POST['dimensiones_maximas'])
        ? $conn->real_escape_string($_POST['dimensiones_maximas'])
        : null;

    // Procesar datos del expedidor
    if (!isset($_POST['recogida_expedidor_nombre']) || !isset($_POST['recogida_expedidor_cif'])) {
        die("Error: Datos del expedidor incompletos. Verifique el formulario.");
    }

    $expedidor = buscar_o_guardar_empresa(
        $conn,
        $_POST['recogida_expedidor_nombre'],
        $_POST['recogida_direccion'],
        $_POST['recogida_expedidor_telefono'],
        $_POST['recogida_expedidor_email'],
        $_POST['recogida_expedidor_cif'],
        $usuario_creador_id
    );
    if ($expedidor['tipo'] == 'usuario') {
        $expedidor_usuario_id = $expedidor['id'];
        $expedidor_entidad_id = null;
    } else {
        $expedidor_usuario_id = null;
        $expedidor_entidad_id = $expedidor['id'];
    }

    // Procesar datos del destinatario
    $destinatario = buscar_o_guardar_empresa(
        $conn,
        $_POST['entrega_receptor_nombre'],
        null,
        null,
        null,
        $_POST['entrega_receptor_cif'],
        $usuario_creador_id
    );
    if ($destinatario['tipo'] == 'usuario') {
        $destinatario_usuario_id = $destinatario['id'];
        $destinatario_entidad_id = null;
    } else {
        $destinatario_usuario_id = null;
        $destinatario_entidad_id = $destinatario['id'];
    }

    // Procesar datos del cliente
    $cliente = buscar_o_guardar_empresa(
        $conn,
        $_POST['cliente_nombre'],
        null,
        null,
        null,
        $_POST['cliente_cif'],
        $usuario_creador_id
    );
    if ($cliente['tipo'] == 'usuario') {
        $cliente_usuario_id = $cliente['id'];
        $cliente_entidad_id = null;
    } else {
        $cliente_usuario_id = null;
        $cliente_entidad_id = $cliente['id'];
    }

    // Preparar la consulta SQL
    $sql = "INSERT INTO portes (
        mercancia_descripcion, mercancia_conservacion, adr, cantidad, peso_total, volumen_total,
        tipo_carga, observaciones, localizacion_recogida, fecha_recogida, recogida_hora_inicio, recogida_hora_fin,
        observaciones_recogida, localizacion_entrega, fecha_entrega, entrega_hora_inicio, entrega_hora_fin,
        observaciones_entrega, no_transbordos, no_delegacion_transporte, se_puede_remontar, dimensiones_maximas,
        cadena_frio, tipo_palet, usuario_creador_id, expedidor_usuario_id, expedidor_entidad_id, nombre_expedidor,
        destinatario_usuario_id, destinatario_entidad_id, nombre_destinatario, cliente_usuario_id, cliente_entidad_id
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    // Vincular parámetros
    $valor_cantidad = $cantidad ?: null;
    $valor_peso_total = $peso_total ?: null;
    $valor_volumen_total = $volumen_total ?: null;
    $valor_tipo_carga = $tipo_carga ?: null;
    $valor_observaciones = $observaciones ?: null;
    $valor_recogida_hora_inicio = $recogida_hora_inicio ?: null;
    $valor_recogida_hora_fin = $recogida_hora_fin ?: null;
    $valor_observaciones_recogida = $observaciones_recogida ?: null;
    $valor_entrega_hora_inicio = $entrega_hora_inicio ?: null;
    $valor_entrega_hora_fin = $entrega_hora_fin ?: null;
    $valor_observaciones_entrega = $observaciones_entrega ?: null;
    $valor_dimensiones_maximas = $dimensiones_maximas ?: null;
    $valor_tipo_palet = $tipo_palet ?: 'ninguno';
    $valor_expedidor_usuario_id = $expedidor_usuario_id ?: null;
    $valor_expedidor_entidad_id = $expedidor_entidad_id ?: null;
    $valor_nombre_expedidor = $nombre_expedidor ?: null;
    $valor_destinatario_usuario_id = $destinatario_usuario_id ?: null;
    $valor_destinatario_entidad_id = $destinatario_entidad_id ?: null;
    $valor_nombre_destinatario = $nombre_destinatario ?: null;
    $valor_cliente_usuario_id = $cliente_usuario_id ?: null;
    $valor_cliente_entidad_id = $cliente_entidad_id ?: null;

    if (!$stmt->bind_param(
        "sssiddddsssssssssssssiiissiissiii",
        $mercancia_descripcion,
        $mercancia_conservacion,
        $adr,
        $valor_cantidad,
        $valor_peso_total,
        $valor_volumen_total,
        $valor_tipo_carga,
        $valor_observaciones,
        $recogida_direccion,
        $recogida_fecha,
        $valor_recogida_hora_inicio,
        $valor_recogida_hora_fin,
        $valor_observaciones_recogida,
        $entrega_direccion,
        $entrega_fecha,
        $valor_entrega_hora_inicio,
        $valor_entrega_hora_fin,
        $valor_observaciones_entrega,
        $no_transbordos,
        $no_delegacion_transporte,
        $se_puede_remontar,
        $valor_dimensiones_maximas,
        $cadena_frio,
        $valor_tipo_palet,
        $usuario_creador_id,
        $valor_expedidor_usuario_id,
        $valor_expedidor_entidad_id,
        $valor_nombre_expedidor,
        $valor_destinatario_usuario_id,
        $valor_destinatario_entidad_id,
        $valor_nombre_destinatario,
        $valor_cliente_usuario_id,
        $valor_cliente_entidad_id
    )) {
        die("Error al vincular parámetros: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }

    // Aquí se ha creado el porte sin problemas
    echo "Porte creado correctamente.";

    // Validar y crear cliente solo si tiene nombre y email
    if (!empty($_POST['cliente_nombre']) && !empty($_POST['cliente_email'])) {
        $cliente_nombre = sanitizar($conn, $_POST['cliente_nombre']);
        $cliente_email  = sanitizar($conn, $_POST['cliente_email']);
        $cliente_direccion = !empty($_POST['cliente_direccion']) ? sanitizar($conn, $_POST['cliente_direccion']) : null;
        $cliente_telefono  = !empty($_POST['cliente_telefono']) ? sanitizar($conn, $_POST['cliente_telefono']) : null;
        $cliente_cif       = !empty($_POST['cliente_cif']) ? sanitizar($conn, $_POST['cliente_cif']) : null;

        // Verificar si el cliente ya existe
        $sql_check = "SELECT id FROM entidades WHERE nombre = ? AND email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $cliente_nombre, $cliente_email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows == 0) {
            // Insertar cliente si no existe
            $sql_insert = "INSERT INTO entidades (nombre, direccion, telefono, email, cif)
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sssss",
                $cliente_nombre,
                $cliente_direccion,
                $cliente_telefono,
                $cliente_email,
                $cliente_cif
            );
            $stmt_insert->execute();
            $stmt_insert->close();
        } else {
            // Con esta línea sabrás que el cliente ya estaba
            // pero no romperá el flujo
        }
        $stmt_check->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar Porte</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div>
        <!-- Solo muestra enlace de vuelta sin mensajes adicionales -->
        <br><a href="portes_nuevos_propios.php">Volver a portes</a>
    </div>
</body>
</html>
