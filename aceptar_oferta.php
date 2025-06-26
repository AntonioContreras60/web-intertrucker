<?php
session_start();
include 'conexion.php'; // Ajusta la ruta según tu proyecto

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo "Error: Usuario no autenticado.";
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

// Mostrar lo que llega por POST para debug
// (podrías comentar esto en producción, pero en pruebas es útil)
echo "<pre>DEBUG POST:\n";
print_r($_POST);
echo "</pre>";

// 1) Verificar que vengan los datos
//    - oferta_id
//    - porte_id
//    - accion (valor esperado: "aceptar")
if (!isset($_POST['oferta_id'], $_POST['porte_id'], $_POST['accion'])) {
    echo "<p>Error: Faltan campos en el formulario (oferta_id, porte_id o accion).</p>";
    echo "<p>POST recibido:</p><pre>" . print_r($_POST, true) . "</pre>";
    exit;
}

if ($_POST['accion'] !== 'aceptar') {
    echo "<p>Error: accion no es 'aceptar'.</p>";
    echo "<p>POST recibido:</p><pre>" . print_r($_POST, true) . "</pre>";
    exit;
}

// 2) Convertir a enteros y comprobar que no estén vacíos
$oferta_id = intval($_POST['oferta_id']);
$porte_id  = intval($_POST['porte_id']);

if ($oferta_id <= 0 || $porte_id <= 0) {
    echo "<p>Error: oferta_id o porte_id no son válidos (<=0).</p>";
    exit;
}

// 3) Procesar la oferta
//    - Ejemplo: cambiar estado_oferta a "asignado" para la oferta elegida
//    - Cambiar las otras a "asignado_a_otro"

try {
    // Iniciar transacción
    $conn->begin_transaction();

    // A) Actualizar la oferta elegida
    $sql_aceptar = "
        UPDATE ofertas_varios
        SET estado_oferta = 'asignado'
        WHERE id = ? 
          AND porte_id = ?
          AND estado_oferta = 'pendiente'
    ";
    $stmt_aceptar = $conn->prepare($sql_aceptar);
    if (!$stmt_aceptar) {
        throw new Exception("Error al preparar: " . $conn->error);
    }
    $stmt_aceptar->bind_param("ii", $oferta_id, $porte_id);
    $stmt_aceptar->execute();

    if ($stmt_aceptar->affected_rows <= 0) {
        throw new Exception("No se pudo actualizar la oferta. Podría no estar en estado pendiente o no existir.");
    }

    // B) Marcar las demás como 'asignado_a_otro'
    $sql_otras = "
        UPDATE ofertas_varios
        SET estado_oferta = 'asignado_a_otro'
        WHERE porte_id = ?
          AND id != ?
          AND estado_oferta = 'pendiente'
    ";
    $stmt_otras = $conn->prepare($sql_otras);
    if (!$stmt_otras) {
        throw new Exception("Error al preparar 'asignado_a_otro': " . $conn->error);
    }
    $stmt_otras->bind_param("ii", $porte_id, $oferta_id);
    $stmt_otras->execute();

    // Confirmar la transacción
    /* ── VISIBILIDAD COMPLETA TRAS LA ACEPTACIÓN ───────────────────────── */
// recuperamos al ofertante (A)
$stmt_get = $conn->prepare("SELECT ofertante_id FROM ofertas_varios WHERE id = ?");
$stmt_get->bind_param("i", $oferta_id);
$stmt_get->execute();
$stmt_get->bind_result($ofertante_id);
$stmt_get->fetch();
$stmt_get->close();

// A y B pasan a verse con visibilidad ‘completo’
$upd = $conn->prepare("
    INSERT INTO contactos (usuario_id, contacto_usuario_id, visibilidad)
    VALUES (?, ?, 'completo')
    ON DUPLICATE KEY UPDATE visibilidad = 'completo'
");
if ($upd) {
    /* B (quien acepta) ve todo de A */
    $upd->bind_param('ii', $usuario_id, $ofertante_id);
    $upd->execute();
    /* A ve todo de B (por si no estaba hecho) */
    $upd->bind_param('ii', $ofertante_id, $usuario_id);
    $upd->execute();
    $upd->close();
}
/* ──────────────────────────────────────────────────────────────────── */

    $conn->commit();

    // Mensaje de éxito
    echo "<p>Oferta #$oferta_id aceptada correctamente para el porte #$porte_id.</p>";

} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    echo "<p>Error al aceptar la oferta: " . $e->getMessage() . "</p>";
    // Para debug: 
    // echo "<p>Trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

// Cerrar statements
if (isset($stmt_aceptar)) {
    $stmt_aceptar->close();
}
if (isset($stmt_otras)) {
    $stmt_otras->close();
}

// Cerrar conexión
$conn->close();
?>

<!-- Botón para volver -->
<a href="portes_nuevos_recibidos.php"><button>Volver</button></a>
