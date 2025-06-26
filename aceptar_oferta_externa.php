<?php
session_start();
include 'conexion.php';   // Conexión a la base de datos

/* ────── 1. Comprobaciones básicas ────── */
if (!isset($_SESSION['usuario_id'])) {
    echo "Error: Usuario no autenticado.";
    exit;
}
$usuario_id = $_SESSION['usuario_id'];              // B (quien acepta)

if (
    !isset($_POST['oferta_id'], $_POST['porte_id'], $_POST['accion']) ||
    $_POST['accion'] !== 'aceptar'
) {
    echo "<p>Error: No se recibieron los datos necesarios.</p>";
    echo "<a href='portes_nuevos_propios.php'><button>Volver</button></a>";
    exit;
}

$oferta_id = (int)$_POST['oferta_id'];
$porte_id  = (int)$_POST['porte_id'];

/* ────── 2. Procesar la aceptación ────── */
try {
    $conn->begin_transaction();

    /* 2-A) Marcar la oferta elegida como ‘asignado’ */
    $sql_aceptar = "
        UPDATE ofertas_varios
        SET estado_oferta = 'asignado'
        WHERE id = ? AND porte_id = ? AND estado_oferta = 'pendiente'
    ";
    $stmt_aceptar = $conn->prepare($sql_aceptar);
    $stmt_aceptar->bind_param("ii", $oferta_id, $porte_id);
    $stmt_aceptar->execute();

    if ($stmt_aceptar->affected_rows <= 0) {
        throw new Exception("No se pudo aceptar la oferta indicada.");
    }

    /* 2-B) Rechazar las demás pendientes del mismo porte */
    $sql_rechazar = "
        UPDATE ofertas_varios
        SET estado_oferta = 'asignado_a_otro'
        WHERE porte_id = ? AND id <> ? AND estado_oferta = 'pendiente'
    ";
    $stmt_rechazar = $conn->prepare($sql_rechazar);
    $stmt_rechazar->bind_param("ii", $porte_id, $oferta_id);
    $stmt_rechazar->execute();

    /* ────── 2-C) Visibilidad completa entre ofertante (A) y receptor (B) ────── */
    // Obtener al ofertante A
    $stmt_getA = $conn->prepare("SELECT ofertante_id FROM ofertas_varios WHERE id = ?");
    $stmt_getA->bind_param("i", $oferta_id);
    $stmt_getA->execute();
    $stmt_getA->bind_result($ofertante_id);  // A
    $stmt_getA->fetch();
    $stmt_getA->close();

    // Registrar visibilidad ‘completo’ en ambas direcciones
    $upd = $conn->prepare("
        INSERT INTO contactos (usuario_id, contacto_usuario_id, visibilidad)
        VALUES (?, ?, 'completo')
        ON DUPLICATE KEY UPDATE visibilidad = 'completo'
    ");
    if ($upd) {
        /* B (quien acepta) ve todo de A */
        $upd->bind_param('ii', $usuario_id, $ofertante_id);
        $upd->execute();
        /* A ve todo de B */
        $upd->bind_param('ii', $ofertante_id, $usuario_id);
        $upd->execute();
        $upd->close();
    }
    /* ─────────────────────────────────────────────────────────────────────── */

    $conn->commit();
    echo "<p>La oferta fue aceptada exitosamente.</p>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

/* ────── 3. Limpieza ────── */
$stmt_aceptar->close();
$stmt_rechazar->close();
$conn->close();
?>

<!-- Botón para volver -->
<a href="portes_nuevos_propios.php"><button>Volver</button></a>

<link rel="stylesheet" href="styles.css">
