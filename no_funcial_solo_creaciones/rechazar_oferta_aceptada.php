<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Mostrar el mensaje guardado en la sesión si existe
if (isset($_SESSION['mensaje'])) {
    echo "<div style='color: green; font-size: 1.5em; text-align: center;'>" . $_SESSION['mensaje'] . "</div>";
    unset($_SESSION['mensaje']); // Limpiar el mensaje después de mostrarlo
}

include 'conexion.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo "Error: Usuario no autenticado.";
    exit;
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado

// Verificar si se ha recibido el ID del porte desde POST, GET, o sesión
if (isset($_POST['porte_id'])) {
    $porte_id = $_POST['porte_id'];
    $_SESSION['porte_id'] = $porte_id; // Guardar en sesión para futuras llamadas
} elseif (isset($_GET['porte_id'])) {
    $porte_id = $_GET['porte_id'];
    $_SESSION['porte_id'] = $porte_id;
} elseif (isset($_SESSION['porte_id'])) {
    $porte_id = $_SESSION['porte_id'];
} else {
    echo "Error: No se recibió el ID del porte.<br>";
    echo "<a href='inicio.php'><button>Volver al inicio</button></a>";
    exit;
}

// Consulta para obtener la información básica del porte
$sql_porte = "SELECT * FROM portes WHERE id = ?";
$stmt_porte = $conn->prepare($sql_porte);
$stmt_porte->bind_param("i", $porte_id);
$stmt_porte->execute();
$resultado_porte = $stmt_porte->get_result();

if ($resultado_porte->num_rows > 0) {
    $porte = $resultado_porte->fetch_assoc();
} else {
    echo "Error: No se encontró el porte.<br>";
    echo "<a href='inicio.php'><button>Volver al inicio</button></a>";
    exit;
}

// Consultar ofertas realizadas a contactos en ofertas_varios
$sql_ofertas_contactos = "SELECT o.id AS oferta_id, o.precio, o.estado_oferta, o.fecha_oferta, u.nombre_usuario AS destinatario
                          FROM ofertas_varios o
                          JOIN usuarios u ON u.id = o.usuario_id
                          WHERE o.porte_id = ? AND o.ofertante_id = ?";
$stmt_ofertas_contactos = $conn->prepare($sql_ofertas_contactos);
$stmt_ofertas_contactos->bind_param("ii", $porte_id, $usuario_id);
$stmt_ofertas_contactos->execute();
$resultado_ofertas_contactos = $stmt_ofertas_contactos->get_result();

// Consultar ofertas realizadas a entidades en ofertas_externas
$sql_ofertas_entidades = "SELECT o.id AS oferta_id, o.precio_externo AS precio, o.estado, o.fecha_creacion AS fecha_oferta, 
                          e.nombre AS destinatario, o.ofertante_gestor_id, o.usuario_gestor_id
                          FROM ofertas_externas o
                          JOIN entidades e ON e.id = o.entidad_id
                          WHERE o.porte_id = ? AND o.ofertante_id = ?";
$stmt_ofertas_entidades = $conn->prepare($sql_ofertas_entidades);
$stmt_ofertas_entidades->bind_param("ii", $porte_id, $usuario_id);
$stmt_ofertas_entidades->execute();
$resultado_ofertas_entidades = $stmt_ofertas_entidades->get_result();

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Hacer Oferta</title>
</head>
<body>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/header.php'; ?>
<h1>Hacer Oferta</h1>

<!-- Mostrar información del porte -->
<h3>Información del Porte</h3>
<p><strong>ID del porte:</strong> <?php echo isset($porte['id']) ? $porte['id'] : 'N/A'; ?></p>
<p><strong>Mercancía:</strong> <?php echo isset($porte['mercancia_descripcion']) ? htmlspecialchars($porte['mercancia_descripcion']) : 'N/A'; ?></p>
<p><strong>Desde:</strong> <?php echo isset($porte['localizacion_recogida']) ? htmlspecialchars($porte['localizacion_recogida']) : 'N/A'; ?> 
   <strong>Fecha de recogida:</strong> <?php echo isset($porte['fecha_recogida']) ? htmlspecialchars($porte['fecha_recogida']) : 'N/A'; ?></p>
<p><strong>Hasta:</strong> <?php echo isset($porte['localizacion_entrega']) ? htmlspecialchars($porte['localizacion_entrega']) : 'N/A'; ?> 
   <strong>Fecha de entrega:</strong> <?php echo isset($porte['fecha_entrega']) ? htmlspecialchars($porte['fecha_entrega']) : 'N/A'; ?></p>

<!-- Formulario para buscar y seleccionar contactos -->
<h3>Ofrecer a Contactos</h3>
<form method="POST" action="hacer_oferta.php">
    <input type="text" name="busqueda_destinatarios" placeholder="Buscar por nombre o email">
    <button type="submit" name="buscar_destinatarios">Buscar</button>
</form>

<?php
if (isset($_POST['buscar_destinatarios'])) {
    $busqueda_destinatarios = '%' . $_POST['busqueda_destinatarios'] . '%';

    // Identificar al administrador principal
    $sql_administrador = "SELECT administrador_id FROM gestores WHERE gestor_id = ?";
    $stmt_administrador = $conn->prepare($sql_administrador);
    $stmt_administrador->bind_param("i", $usuario_id);
    $stmt_administrador->execute();
    $resultado_administrador = $stmt_administrador->get_result();

    if ($resultado_administrador->num_rows > 0) {
        // Si el usuario es un gestor, obtenemos el administrador principal
        $row = $resultado_administrador->fetch_assoc();
        $admin_id = $row['administrador_id'];
    } else {
        // Si el usuario no es un gestor, él mismo es el administrador
        $admin_id = $usuario_id;
    }

    // Inicializar los resultados
    $resultado_contactos = null;
    $resultado_entidades = null;

    // Consulta para buscar contactos del administrador y los gestores asociados
    $sql_contactos = "SELECT u.id, u.nombre_usuario AS nombre, u.email, 'contacto' AS tipo 
                      FROM usuarios u
                      JOIN contactos c ON u.id = c.contacto_usuario_id
                      WHERE c.usuario_id IN (
                          SELECT gestor_id FROM gestores WHERE administrador_id = ?
                          UNION
                          SELECT ?
                      )
                      AND (u.nombre_usuario LIKE ? OR u.email LIKE ?)";
    $stmt_contactos = $conn->prepare($sql_contactos);
    if ($stmt_contactos) {
        $stmt_contactos->bind_param("isss", $admin_id, $admin_id, $busqueda_destinatarios, $busqueda_destinatarios);
        $stmt_contactos->execute();
        $resultado_contactos = $stmt_contactos->get_result();
    }

    // Consulta para buscar entidades del administrador y los gestores asociados
    $sql_entidades = "SELECT e.id, e.nombre, e.email, 'entidad' AS tipo 
                      FROM entidades e
                      WHERE e.usuario_id IN (
                          SELECT gestor_id FROM gestores WHERE administrador_id = ?
                          UNION
                          SELECT ?
                      )
                      AND (e.nombre LIKE ? OR e.email LIKE ?)
                      AND e.entidad_usuario_id IS NULL";
    $stmt_entidades = $conn->prepare($sql_entidades);
    if ($stmt_entidades) {
        $stmt_entidades->bind_param("isss", $admin_id, $admin_id, $busqueda_destinatarios, $busqueda_destinatarios);
        $stmt_entidades->execute();
        $resultado_entidades = $stmt_entidades->get_result();
    }

    // Mostrar resultados de contactos y entidades
    if (($resultado_contactos && $resultado_contactos->num_rows > 0) || ($resultado_entidades && $resultado_entidades->num_rows > 0)) {
        echo "<ul>";

        // Mostrar contactos si hay resultados
        if ($resultado_contactos && $resultado_contactos->num_rows > 0) {
            echo "<h3>Contactos Usuarios</h3>";
            echo "<ul>";
            while ($contacto = $resultado_contactos->fetch_assoc()) {
                echo "<li>Contacto: " . htmlspecialchars($contacto['nombre']) . " - " . htmlspecialchars($contacto['email']);
                echo " <button type='button' onclick='agregarSeleccionado(" . $contacto['id'] . ", \"" . htmlspecialchars($contacto['nombre']) . "\", \"contacto\")'>Seleccionar</button></li>";
            }
            echo "</ul>";
        }

        // Mostrar entidades si hay resultados
        if ($resultado_entidades && $resultado_entidades->num_rows > 0) {
            echo "<h3>Contactos No Usuarios</h3>";
            echo "<ul>";
            while ($entidad = $resultado_entidades->fetch_assoc()) {
                echo "<li>Entidad: " . htmlspecialchars($entidad['nombre']) . " - " . htmlspecialchars($entidad['email']);
                echo " <button type='button' onclick='agregarSeleccionado(" . $entidad['id'] . ", \"" . htmlspecialchars($entidad['nombre']) . "\", \"entidad\")'>Seleccionar</button></li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p>No se encontraron contactos ni entidades que coincidan con la búsqueda.</p>";
    }
}
?>

<h3>Contactos Seleccionados</h3>
<div id="listaSeleccionados"></div>

<!-- Formulario para enviar la oferta -->
<form method="POST" action="enviar_oferta.php" onsubmit="return verificarSeleccionados();">
    <input type="hidden" name="porte_id" value="<?php echo $porte_id; ?>">
    <input type="hidden" id="destinatarios_seleccionados" name="destinatarios_seleccionados">
    <label for="precio">Precio de la oferta:</label>
    <input type="text" name="precio" id="precio" required>
    <button type="submit">Mandar Oferta</button>
</form>
  
<!-- Formulario para ofrecer a empresa nueva -->
<h3>Ofrecer a Empresa Nueva</h3>
<form method="POST" action="hacer_oferta.php">
    <label for="nombre_destinatario">Nombre de la Empresa</label>
    <input type="text" name="nombre_destinatario" id="nombre_destinatario" required>
    <br>
    <label for="direccion_destinatario">Dirección</label>
    <input type="text" name="direccion_destinatario" id="direccion_destinatario" required>
    <br>
    <label for="email_destinatario">Email del Destinatario</label>
    <input type="email" name="email_destinatario" id="email_destinatario" required>
    <br>
    <label for="precio_externo">Precio de la Oferta:</label>
    <input type="text" name="precio_externo" id="precio_externo" required>
    <br>
    <button type="submit" name="ofrecer_externamente">Generar Enlace de Oferta</button>
</form>

<?php
// Procesar el formulario si se envía
if (isset($_POST['ofrecer_externamente'])) {
    // Captura los datos del formulario
    $nombre_destinatario = $_POST['nombre_destinatario'];
    $direccion_destinatario = $_POST['direccion_destinatario'];
    $email_destinatario = $_POST['email_destinatario'];
    $precio_externo = $_POST['precio_externo'];
    $token = bin2hex(random_bytes(16));
    $enlace = "http://intertrucker.net/aceptar_oferta_externa.php?token=" . $token . "&registro=1";

    // Verificar si la entidad ya existe
    $sql_check_entidad = "SELECT id FROM entidades WHERE email = ?";
    $stmt_check_entidad = $conn->prepare($sql_check_entidad);
    $stmt_check_entidad->bind_param("s", $email_destinatario);
    $stmt_check_entidad->execute();
    $result_check_entidad = $stmt_check_entidad->get_result();

    if ($result_check_entidad->num_rows > 0) {
        $row_entidad = $result_check_entidad->fetch_assoc();
        $entidad_id = $row_entidad['id'];
    } else {
        // Insertar nueva entidad si no existe
        $sql_insert_entidad = "INSERT INTO entidades (nombre, direccion, email, creado_en_porte) VALUES (?, ?, ?, 1)";
        $stmt_insert_entidad = $conn->prepare($sql_insert_entidad);
        $stmt_insert_entidad->bind_param("sss", $nombre_destinatario, $direccion_destinatario, $email_destinatario);

        if ($stmt_insert_entidad->execute()) {
            $entidad_id = $stmt_insert_entidad->insert_id;
            $mensaje = "<div style='color: green; text-align: center;'>Entidad creada correctamente.</div>";
        } else {
            $mensaje = "<div style='color: red; text-align: center;'>Error al crear la entidad.</div>";
        }
        $stmt_insert_entidad->close();
    }
    $stmt_check_entidad->close();

    // Insertar oferta en la tabla ofertas_externas
    $sql_oferta_externa = "INSERT INTO ofertas_externas (porte_id, entidad_id, token, enlace, estado, fecha_creacion, ofertante_id, precio_externo)
                           VALUES (?, ?, ?, ?, 'pendiente', NOW(), ?, ?)";
    $stmt_oferta_externa = $conn->prepare($sql_oferta_externa);
    $stmt_oferta_externa->bind_param("iissid", $porte_id, $entidad_id, $token, $enlace, $usuario_id, $precio_externo);
    if ($stmt_oferta_externa->execute()) {
    // Enviar enlace por correo
    $subject = "Oferta de Transporte - InterTrucker";
    $message = "Hola $nombre_destinatario,\n\nSe le ha enviado una oferta de transporte. Puede verla y registrarse como usuario en InterTrucker mediante el siguiente enlace:\n\n$enlace\n\nSaludos,\nInterTrucker";
    $headers = "From: no-reply@intertrucker.net";

    if (mail($email_destinatario, $subject, $message, $headers)) {
        $mensaje = "<div style='color: green; text-align: center;'>Oferta creada y email enviado a $email_destinatario.</div>";
    } else {
        $mensaje = "<div style='color: orange; text-align: center;'>Oferta creada, pero hubo un error al enviar el email.</div>";
    }
} else {
    $mensaje = "<div style='color: red; text-align: center;'>Error al crear la oferta.</div>";
}
} ?>

<script>
    let seleccionados = [];

    function agregarSeleccionado(id, nombre, tipo) {
        // Añadir el contacto o entidad seleccionada a la lista de seleccionados
        seleccionados.push({ id: id, nombre: nombre, tipo: tipo });
        actualizarSeleccionados();
    }

    function actualizarSeleccionados() {
        const seleccionadosDiv = document.getElementById('listaSeleccionados');
        seleccionadosDiv.innerHTML = ''; // Limpiar la lista antes de mostrar los seleccionados

        // Mostrar cada contacto o entidad seleccionada
        seleccionados.forEach((item, index) => {
            const div = document.createElement('div');
            div.innerHTML = `${item.nombre} <button type="button" onclick="eliminarSeleccionado(${index})">Eliminar</button>`;
            seleccionadosDiv.appendChild(div);
        });

        // Actualizar el campo oculto con la lista de seleccionados en formato JSON
        document.getElementById('destinatarios_seleccionados').value = JSON.stringify(seleccionados);
    }

    function eliminarSeleccionado(index) {
        // Eliminar el contacto o entidad de la lista
        seleccionados.splice(index, 1);
        actualizarSeleccionados();
    }

    function verificarSeleccionados() {
        const seleccionados = document.getElementById('destinatarios_seleccionados').value;
        if (seleccionados.length === 0) {
            alert("Debe seleccionar al menos un destinatario antes de enviar la oferta.");
            return false;
        }
        const precio = document.getElementById('precio').value;
        if (!precio) {
            alert("Debe ingresar un precio antes de enviar la oferta.");
            return false;
        }
        return true;
    }
</script>
 <h3>Ofertas a Contactos Usuarios</h3> 
 <?php if ($resultado_ofertas_contactos->num_rows > 0) { ?>
    <table border='1'>
        <tr>
            <th>Destinatario</th>
            <th>Precio</th>
            <th>Estado</th>
            <th>Seleccionar Oferta</th>
        </tr>
        <?php while ($oferta = $resultado_ofertas_contactos->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($oferta['destinatario']); ?></td>
                <td><?php echo number_format($oferta['precio'], 2); ?> €</td>
                <td><?php echo htmlspecialchars($oferta['estado_oferta']); ?></td>
                <td>
                    <?php 
                    // Mostrar botón solo si la oferta está en estado "aceptado"
                    if ($oferta['estado_oferta'] === 'aceptado') { ?>
                        <form method="POST" action="seleccionar_oferta.php" style="display:inline;">
                            <input type="hidden" name="ofertas_varios_id" value="<?php echo htmlspecialchars($oferta['oferta_id']); ?>">
                            <input type="hidden" name="porte_id" value="<?php echo htmlspecialchars($porte_id); ?>">
                            <button type="submit" onclick="return confirm('¿Confirma que selecciona esta oferta para hacer el porte?')">Seleccionar</button>
                        </form>
                    <?php } else {
                        echo "No disponible";
                    } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
<?php } else { ?>
    <p>No se han realizado ofertas a contactos.</p>
<?php } ?>


<h3>Ofertas a Contactos No Usuarios</h3> <?php if ($resultado_ofertas_entidades->num_rows > 0) { echo "<table border='1'> <tr> <th>Destinatario</th> <th>Precio</th> <th>Estado</th> <th>Seleccionar Oferta</th> </tr>";
bash
Copiar código
while ($oferta = $resultado_ofertas_entidades->fetch_assoc()) {
    echo "<tr>
            <td>" . htmlspecialchars($oferta['destinatario']) . "</td>
            <td>" . number_format($oferta['precio'], 2) . " €</td>
            <td>" . htmlspecialchars($oferta['estado']) . "</td>";

    // Botón "Seleccionar oferta" (solo si la oferta está en estado "aceptado")
    echo "<td>";
    if (strtolower(trim($oferta['estado'])) === 'aceptado') {
        echo "<form method='POST' action='seleccionar_oferta.php' style='display:inline;'>
                <input type='hidden' name='ofertas_externas_id' value='" . htmlspecialchars($oferta['oferta_id']) . "'>
                <input type='hidden' name='porte_id' value='" . htmlspecialchars($porte_id) . "'>
                <button type='submit' onclick='return confirm(\"¿Confirma que selecciona esta oferta para hacer el porte?\")'>Seleccionar</button>
              </form>";
    } else {
        echo "No disponible";
    }
    
    echo "</td></tr>";
}
echo "</table>";
while ($oferta = $resultado_ofertas_entidades->fetch_assoc()) {
    echo "<tr>
            <td>" . htmlspecialchars($oferta['destinatario']) . "</td>
            <td>" . number_format($oferta['precio'], 2) . " €</td>
            <td>" . htmlspecialchars($oferta['estado']) . "</td>";

    // Botón "Seleccionar oferta" (solo si la oferta está en estado "aceptado")
    echo "<td>";
    if (strtolower(trim($oferta['estado'])) === 'aceptado') {
        echo "<form method='POST' action='seleccionar_oferta.php' style='display:inline;'>
                <input type='hidden' name='ofertas_externas_id' value='" . htmlspecialchars($oferta['oferta_id']) . "'>
                <input type='hidden' name='porte_id' value='" . htmlspecialchars($porte_id) . "'>
                <button type='submit' onclick='return confirm(\"¿Confirma que selecciona esta oferta para hacer el porte?\")'>Seleccionar</button>
              </form>";
    } else {
        echo "No disponible";
    }
    
    echo "</td></tr>";
}
echo "</table>";
} else { echo "<p>No se han realizado ofertas a entidades.</p>"; } ?>

<button onclick="window.history.back()">Volver</button>

</body> 
</html>
