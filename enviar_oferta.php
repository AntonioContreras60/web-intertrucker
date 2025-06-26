<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    die("Error: Usuario no autenticado.");
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado

// Validar los datos recibidos por POST
if (!isset($_POST['porte_id'], $_POST['precio'], $_POST['deadline'], $_POST['destinatarios_seleccionados'], $_POST['moneda_seleccionada'])) {
    die("<p>Error: Faltan datos necesarios para procesar la oferta. Por favor, revise el formulario.</p>
         <a href='javascript:history.back();'><button>Volver al formulario</button></a>");
}

// Asignar variables recibidas
$porte_id = $_POST['porte_id'];
$precio = $_POST['precio'];
$moneda = $_POST['moneda_seleccionada'];
$deadline_mysql = str_replace('T', ' ', $_POST['deadline']); // Convertir formato para MySQL

// Decodificar los destinatarios seleccionados
$destinatarios = json_decode($_POST['destinatarios_seleccionados'], true);
if ($destinatarios === null || empty($destinatarios)) {
    die("<p>Error: Los destinatarios seleccionados no son válidos o están vacíos.</p>
         <a href='javascript:history.back();'><button>Volver al formulario</button></a>");
}

// Procesar las inserciones
$errores = [];
foreach ($destinatarios as $destinatario) {
    if (!isset($destinatario['id']) || !isset($destinatario['tipo'])) {
        $errores[] = "Faltan datos en el destinatario.";
        continue;
    }

    if ($destinatario['tipo'] === 'contacto') {
        // Inserción para contactos en la tabla ofertas_varios
        $sql = "INSERT INTO ofertas_varios (porte_id, usuario_id, estado_oferta, fecha_oferta, ofertante_id, precio, deadline, moneda)
                VALUES (?, ?, 'pendiente', NOW(), ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errores[] = "Error en la preparación de la consulta para contacto: " . $conn->error;
            continue;
        }
        $stmt->bind_param("iiisss", $porte_id, $destinatario['id'], $usuario_id, $precio, $deadline_mysql, $moneda);
        if (!$stmt->execute()) {
            $errores[] = "Error al insertar contacto " . htmlspecialchars($destinatario['id']) . ": " . $stmt->error;
        }
        $stmt->close();
    } elseif ($destinatario['tipo'] === 'entidad') {
        // Procesar entidades y enviar la oferta
        $token = bin2hex(random_bytes(16));
        $enlace = "http://intertrucker.net/aceptar_oferta_externa.php?token=" . $token;

        $sql_oferta_externa = "INSERT INTO ofertas_externas (porte_id, entidad_id, token, enlace, estado, fecha_creacion, ofertante_id, precio_externo, deadline)
                               VALUES (?, ?, ?, ?, 'pendiente', NOW(), ?, ?, ?)";
        $stmt = $conn->prepare($sql_oferta_externa);
        if (!$stmt) {
            $errores[] = "Error en la preparación para entidad: " . $conn->error;
            continue;
        }
        $stmt->bind_param("iississ", $porte_id, $destinatario['id'], $token, $enlace, $usuario_id, $precio, $deadline_mysql);
        if (!$stmt->execute()) {
            $errores[] = "Error al insertar entidad " . htmlspecialchars($destinatario['id']) . ": " . $stmt->error;
        }
        $stmt->close();
    }
}

// Cerrar conexión
$conn->close();

// Mostrar resultados
if (empty($errores)) {
    echo "<p>Todas las ofertas se han procesado correctamente.</p>";
} else {
    echo "<p>Se encontraron errores:</p><ul>";
    foreach ($errores as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}
echo "<a href='portes_nuevos_recibidos.php'><button>Volver al inicio</button></a>";
?>
