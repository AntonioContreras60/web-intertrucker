<?php
session_start();
require_once __DIR__.'/auth.php';
require_login();
require_role(['administrador','gestor','camionero','asociado']);

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

// Verificar la conexión a la base de datos
if (!$conn) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $porte_id = isset($_POST['porte_id']) ? intval($_POST['porte_id']) : null;
    $tipo_evento = isset($_POST['tipo_evento']) ? $_POST['tipo_evento'] : null;

    // Validar que se recibió el porte_id y tipo_evento
    if (!$porte_id || !$tipo_evento) {
        echo json_encode(['success'=>false,'messages'=>['Datos insuficientes']]);
        exit;
    }

    // Validar porte pertenece al admin
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $chk = $conn->prepare("SELECT p.id FROM portes p JOIN usuarios u ON p.usuario_creador_id=u.id WHERE p.id=? AND u.admin_id=? LIMIT 1");
    $chk->bind_param('ii', $porte_id, $admin_id);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        echo json_encode(['success'=>false,'messages'=>['acceso denegado']]);
        exit;
    }
    $chk->close();

    $messages = [];

    // Registrar la llegada
    if (isset($_POST['registrar_llegada']) && $_POST['registrar_llegada'] == true) {
        $geolocalizacion_llegada = isset($_POST['geolocalizacion_llegada']) ? $_POST['geolocalizacion_llegada'] : '';
        $hora_llegada = isset($_POST['hora_llegada']) ? $_POST['hora_llegada'] : '';

        // Verificar que se recibieron las variables correctamente
        if (empty($geolocalizacion_llegada) || empty($hora_llegada)) {
            echo json_encode(['success'=>false,'messages'=>['Datos de llegada incompletos']]);
            exit;
        }

        // Consulta SQL para actualizar la llegada
        $sql_llegada = "UPDATE eventos SET geolocalizacion_llegada = ?, hora_llegada = ? WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_llegada = $conn->prepare($sql_llegada);
        if (!$stmt_llegada) {
            echo json_encode(['success'=>false,'messages'=>['Error prepare llegada']]);
            exit;
        }
        $stmt_llegada->bind_param("ssis", $geolocalizacion_llegada, $hora_llegada, $porte_id, $tipo_evento);

        // Ejecutar la consulta y verificar el resultado
        if ($stmt_llegada->execute()) {
            $messages[] = 'Llegada registrada';
        } else {
            $messages[] = 'Error al registrar la llegada: ' . $stmt_llegada->error;
        }
        $stmt_llegada->close();
    }

    // Registrar la salida
    if (isset($_POST['registrar_salida']) && $_POST['registrar_salida'] == true) {
        $geolocalizacion_salida = isset($_POST['geolocalizacion_salida']) ? $_POST['geolocalizacion_salida'] : '';
        $hora_salida = isset($_POST['hora_salida']) ? $_POST['hora_salida'] : '';

        // Verificar que los datos no estén vacíos
        if (!empty($geolocalizacion_salida) && !empty($hora_salida)) {
            // Consulta SQL para actualizar la salida
            $sql_salida = "UPDATE eventos SET geolocalizacion_salida = ?, hora_salida = ? WHERE porte_id = ? AND tipo_evento = ?";
            $stmt_salida = $conn->prepare($sql_salida);
            if (!$stmt_salida) {
                echo json_encode(['success'=>false,'messages'=>['Error prepare salida']]);
                exit;
            }
            $stmt_salida->bind_param("ssis", $geolocalizacion_salida, $hora_salida, $porte_id, $tipo_evento);

            if ($stmt_salida->execute()) {
                $messages[] = 'Salida registrada';

                // Actualizar el estado en la tabla portes dependiendo del tipo de evento
                $estado_recogida_entrega = '';
                if ($tipo_evento === 'recogida') {
                    $estado_recogida_entrega = 'Recogido';
                } elseif ($tipo_evento === 'entrega') {
                    $estado_recogida_entrega = 'Entregado';
                }

                // Solo actualizar el estado si el tipo de evento es recogida o entrega
                if (!empty($estado_recogida_entrega)) {
                    $sql_update_estado = "UPDATE portes SET estado_recogida_entrega = ? WHERE id = ?";
                    $stmt_estado = $conn->prepare($sql_update_estado);
                    if (!$stmt_estado) {
                        echo json_encode(['success'=>false,'messages'=>['Error prepare estado']]);
                        exit;
                    }
                    $stmt_estado->bind_param("si", $estado_recogida_entrega, $porte_id);

                    if ($stmt_estado->execute()) {
                        $messages[] = 'Estado actualizado a ' . $estado_recogida_entrega;
                    } else {
                        $messages[] = 'Error al actualizar el estado: ' . $stmt_estado->error;
                    }
                    $stmt_estado->close();
                }

            } else {
                $messages[] = 'Error al registrar la salida: ' . $stmt_salida->error;
            }
            $stmt_salida->close();
        } else {
            $messages[] = 'Datos incompletos para registrar la salida.';
        }
    }

    // Eliminar registro de llegada
    if (isset($_POST['eliminar_llegada']) && $_POST['eliminar_llegada'] == true) {
        $sql_eliminar_llegada = "UPDATE eventos SET geolocalizacion_llegada = '', hora_llegada = '' WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_eliminar_llegada = $conn->prepare($sql_eliminar_llegada);
        if (!$stmt_eliminar_llegada) {
            echo json_encode(['success'=>false,'messages'=>['Error prepare eliminar llegada']]);
            exit;
        }
        $stmt_eliminar_llegada->bind_param("is", $porte_id, $tipo_evento);

        if ($stmt_eliminar_llegada->execute()) {
            $messages[] = 'Registro de llegada eliminado';
        } else {
            $messages[] = 'Error al eliminar llegada: ' . $stmt_eliminar_llegada->error;
        }
        $stmt_eliminar_llegada->close();
    }

    // Eliminar registro de salida
    if (isset($_POST['eliminar_salida']) && $_POST['eliminar_salida'] == true) {
        $sql_eliminar_salida = "UPDATE eventos SET geolocalizacion_salida = '', hora_salida = '' WHERE porte_id = ? AND tipo_evento = ?";
        $stmt_eliminar_salida = $conn->prepare($sql_eliminar_salida);
        if (!$stmt_eliminar_salida) {
            echo json_encode(['success'=>false,'messages'=>['Error prepare eliminar salida']]);
            exit;
        }
        $stmt_eliminar_salida->bind_param("is", $porte_id, $tipo_evento);

        if ($stmt_eliminar_salida->execute()) {
            $messages[] = 'Registro de salida eliminado';
        } else {
            $messages[] = 'Error al eliminar salida: ' . $stmt_eliminar_salida->error;
        }
        $stmt_eliminar_salida->close();
    }
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

// Cerrar la conexión a la base de datos
mysqli_close($conn);
echo json_encode(['success' => false, 'messages' => ['Método no permitido']]);
?>
