<?php
// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/auth.php';
require_login();
require_role(["administrador","gestor","camionero","asociado"]);

// Conexión a la base de datos
include 'conexion.php';

// Mostrar el mensaje guardado en la sesión si existe
if (isset($_SESSION['mensaje'])) {
    echo "<div style='color: green; font-size: 1.5em; text-align: center;'>" . htmlspecialchars($_SESSION['mensaje']) . "</div>";
    unset($_SESSION['mensaje']); // Limpiar el mensaje después de mostrarlo
}

// ID del usuario autenticado
$usuario_id = $_SESSION['usuario_id'];

// Identificar al administrador principal
$sql_administrador = "SELECT admin_id FROM usuarios WHERE id = ?";
$stmt_administrador = $conn->prepare($sql_administrador);
if ($stmt_administrador === false) {
    die("Error en la preparación de la consulta del administrador: " . $conn->error);
}
$stmt_administrador->bind_param("i", $usuario_id);
$stmt_administrador->execute();
$resultado_administrador = $stmt_administrador->get_result();

if ($resultado_administrador->num_rows > 0) {
    $row = $resultado_administrador->fetch_assoc();
    $admin_id = $row['admin_id'] ? $row['admin_id'] : $usuario_id;
} else {
    $admin_id = $usuario_id;
}
$stmt_administrador->close();

// Verificar si se ha recibido el ID del porte desde POST, GET, o sesión
$porte_id = $_POST['porte_id'] ?? $_GET['porte_id'] ?? $_SESSION['porte_id'] ?? null;
if ($porte_id === null) {
    die("Error: No se recibió el ID del porte.<br><a href='inicio.php'><button>Volver al inicio</button></a>");
}
$_SESSION['porte_id'] = $porte_id; // Guardar en sesión para futuras llamadas

// Consulta para obtener la información básica del porte
$sql_porte = "SELECT * FROM portes WHERE id = ?";
$stmt_porte = $conn->prepare($sql_porte);
if ($stmt_porte === false) {
    die("Error en la preparación de la consulta del porte: " . $conn->error);
}
$stmt_porte->bind_param("i", $porte_id);
$stmt_porte->execute();
$resultado_porte = $stmt_porte->get_result();
$porte = $resultado_porte->num_rows > 0 ? $resultado_porte->fetch_assoc() : null;
$stmt_porte->close();

if (!$porte) {
    die("Error: No se encontró el porte.<br><a href='inicio.php'><button>Volver al inicio</button></a>");
}

// ==== determinar publicador y empresa destino ====
$publicador_id = (int)$porte['usuario_creador_id'];
$sql_dest_admin = "SELECT COALESCE(admin_id, id) AS admin_dest_id FROM usuarios WHERE id = ?";
$stmt_dest_admin = $conn->prepare($sql_dest_admin);
if ($stmt_dest_admin === false) {
    die("Error en la preparación de la consulta del destinatario: " . $conn->error);
}
$stmt_dest_admin->bind_param('i', $publicador_id);
$stmt_dest_admin->execute();
$res_dest_admin = $stmt_dest_admin->get_result();
$destinatario_admin_id = $res_dest_admin->num_rows > 0
    ? (int)$res_dest_admin->fetch_assoc()['admin_dest_id']
    : $publicador_id;
$stmt_dest_admin->close();

// Procesar la búsqueda de destinatarios si se envía el formulario
if (isset($_POST['buscar_destinatarios'])) {
    $busqueda_destinatarios = '%' . $_POST['busqueda_destinatarios'] . '%';

    // Inicializar los resultados
    $resultado_contactos = null;
    $resultado_entidades = null;

    // Consulta para buscar contactos del administrador
    $sql_contactos = "SELECT u.id, u.nombre_usuario AS nombre, u.email, 'contacto' AS tipo 
                      FROM usuarios u
                      JOIN contactos c ON u.id = c.contacto_usuario_id
                      WHERE c.usuario_id = ?
                      AND (u.nombre_usuario LIKE ? OR u.email LIKE ?)";

    // Preparar la declaración
    $stmt_contactos = $conn->prepare($sql_contactos);
    if ($stmt_contactos === false) {
        die("Error en la preparación de la consulta de contactos: " . $conn->error);
    }

    // Asignar los parámetros a la consulta
    $stmt_contactos->bind_param("iss", $admin_id, $busqueda_destinatarios, $busqueda_destinatarios);

    // Ejecutar la declaración preparada
    $stmt_contactos->execute();
    $resultado_contactos = $stmt_contactos->get_result();
    $stmt_contactos->close();

    // Consulta para buscar entidades del administrador
    $sql_entidades = "SELECT e.id, e.nombre, e.email, 'entidad' AS tipo 
                      FROM entidades e
                      WHERE e.usuario_id = ?
                      AND (e.nombre LIKE ? OR e.email LIKE ?)
                      AND e.entidad_usuario_id IS NULL";
    $stmt_entidades = $conn->prepare($sql_entidades);
    if ($stmt_entidades === false) {
        die("Error en la preparación de la consulta de entidades: " . $conn->error);
    }
    $stmt_entidades->bind_param("iss", $admin_id, $busqueda_destinatarios, $busqueda_destinatarios);
    $stmt_entidades->execute();
    $resultado_entidades = $stmt_entidades->get_result();
    $stmt_entidades->close();
}

// Procesar la creación de una nueva oferta si se envía el formulario
if (isset($_POST['ofrecer_externamente'])) {
    // Captura los datos del formulario
    $nombre_destinatario = $_POST['nombre_destinatario'];
    $direccion_destinatario = $_POST['direccion_destinatario'];
    $email_destinatario = $_POST['email_destinatario'];
    $precio_externo = $_POST['precio_externo'];
    $deadline = $_POST['deadline'];
    $token = bin2hex(random_bytes(16));
    $enlace = "http://intertrucker.net/aceptar_oferta_externa.php?token=" . $token . "&registro=1";

    // Verificar si la entidad ya existe
    $sql_check_entidad = "SELECT id FROM entidades WHERE email = ?";
    $stmt_check_entidad = $conn->prepare($sql_check_entidad);
    if ($stmt_check_entidad === false) {
        die("Error en la preparación de la consulta de verificación de entidad: " . $conn->error);
    }
    $stmt_check_entidad->bind_param("s", $email_destinatario);
    $stmt_check_entidad->execute();
    $result_check_entidad = $stmt_check_entidad->get_result();

    if ($result_check_entidad->num_rows > 0) {
        $row_entidad = $result_check_entidad->fetch_assoc();
        $entidad_id = $row_entidad['id'];
    } else {
        // Insertar nueva entidad si no existe, vinculada al administrador
        $sql_insert_entidad = "INSERT INTO entidades (nombre, direccion, email, usuario_id, creado_en_porte) VALUES (?, ?, ?, ?, 1)";
        $stmt_insert_entidad = $conn->prepare($sql_insert_entidad);
        if ($stmt_insert_entidad === false) {
            die("Error en la preparación de la consulta de inserción de entidad: " . $conn->error);
        }
        $stmt_insert_entidad->bind_param("sssi", $nombre_destinatario, $direccion_destinatario, $email_destinatario, $admin_id);

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
    $sql_oferta_externa = "INSERT INTO ofertas_externas (porte_id, entidad_id, token, enlace, estado, fecha_creacion, ofertante_id, precio_externo, deadline)
                           VALUES (?, ?, ?, ?, 'pendiente', NOW(), ?, ?, ?)";
    $stmt_oferta_externa = $conn->prepare($sql_oferta_externa);
    if ($stmt_oferta_externa === false) {
        die("Error en la preparación de la consulta de inserción de oferta externa: " . $conn->error);
    }
    $stmt_oferta_externa->bind_param("iissids", $porte_id, $entidad_id, $token, $enlace, $usuario_id, $precio_externo, $deadline);

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
    $stmt_oferta_externa->close();
}

// Consultar ofertas realizadas a contactos en ofertas_varios
$sql_ofertas_contactos = "SELECT o.id AS oferta_id, o.precio, o.estado_oferta, o.fecha_oferta, u.nombre_usuario AS destinatario, o.deadline
                          FROM ofertas_varios o
                          JOIN usuarios u ON u.id = o.usuario_id
                          WHERE o.porte_id = ? AND o.ofertante_id = ?";
$stmt_ofertas_contactos = $conn->prepare($sql_ofertas_contactos);
if ($stmt_ofertas_contactos === false) {
    die("Error en la preparación de la consulta de ofertas a contactos: " . $conn->error);
}
$stmt_ofertas_contactos->bind_param("ii", $porte_id, $usuario_id);
$stmt_ofertas_contactos->execute();
$resultado_ofertas_contactos = $stmt_ofertas_contactos->get_result();
$stmt_ofertas_contactos->close();




// Verificar si el formulario ha sido enviado y los datos requeridos están presentes
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['precio'], $_POST['moneda_seleccionada'])) {
    // Capturar y validar los datos enviados por el formulario
    $precio = $_POST['precio'] ?? null;
    $moneda = $_POST['moneda_seleccionada'] ?? null;

    if (empty($precio) || !is_numeric($precio)) {
        echo "<div style='color:red;'>Error: El precio debe ser un número válido.</div>";
    } elseif (empty($moneda)) {
        echo "<div style='color:red;'>Error: Debe seleccionar una moneda.</div>";
    } else {
        /* ---------- insertar la oferta pendiente ---------- */
        $sql = "INSERT INTO ofertas_varios
                   (porte_id, usuario_id, estado_oferta, deadline,
                    ofertante_id, fecha_oferta, precio, moneda)
                VALUES (?, ?, 'pendiente', ?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error en la preparación de la consulta: " . $conn->error);
        }
        $stmt->bind_param("iisids",
                          $porte_id,
                          $destinatario_admin_id, // B (empresa del publicador)
                          $deadline,
                          $usuario_id,          // A (ofertante actual)
                          $precio,
                          $moneda);

        if ($stmt->execute()) {
            /* === NUEVO: dar visibilidad COMPLETA al destinatario === */
            $upd = $conn->prepare(
                "UPDATE contactos SET visibilidad = 'completo'\n                 WHERE usuario_id = ? AND contacto_usuario_id = ?"
            );
            if ($upd) {
                $upd->bind_param('ii', $destinatario_admin_id, $usuario_id);
                $upd->execute();
                if ($upd->affected_rows === 0) {
                    $ins = $conn->prepare(
                        "INSERT INTO contactos (usuario_id, contacto_usuario_id, visibilidad)\n                         VALUES (?, ?, 'completo')"
                    );
                    if ($ins) {
                        $ins->bind_param('ii', $destinatario_admin_id, $usuario_id);
                        $ins->execute();
                        $ins->close();
                    }
                }
                $upd->close();
            }
            /* ------------------------------------------------------- */

            echo "<div style='color:green;'>Oferta enviada correctamente.</div>";
        } else {
            echo "<div style='color:red;'>Error al insertar la oferta: "
               . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}


$sql_precio_asignado = "SELECT precio FROM seleccionados_oferta WHERE porte_id = ? AND usuario_id = ?";
$stmt_precio_asignado = $conn->prepare($sql_precio_asignado);

if ($stmt_precio_asignado) {
    $stmt_precio_asignado->bind_param("ii", $porte_id, $usuario_id);
    $stmt_precio_asignado->execute();
    $result_precio_asignado = $stmt_precio_asignado->get_result();

    $precio_asignado = $result_precio_asignado->num_rows > 0 ? $result_precio_asignado->fetch_assoc()['precio'] : null;

    $stmt_precio_asignado->close();
}

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
    <?php include 'header.php'; ?>
    
    <!-- Mostrar el mensaje guardado en la sesión si existe -->
    <?php
    if (isset($mensaje)) {
        echo $mensaje;
    }
    ?>

    <h1>Hacer Oferta</h1>

    <!-- Mostrar la información del porte -->
    <h3>Información del Porte</h3>
    <p><strong>ID del porte:</strong> <?php echo htmlspecialchars($porte['id'] ?? 'N/A'); ?></p>
    <p> <strong>Mercancía:</strong> <?php echo htmlspecialchars($porte['mercancia_descripcion'] ?? 'N/A'); ?></p>
    <p><strong>Desde:</strong> <?php echo htmlspecialchars($porte['localizacion_recogida'] ?? 'N/A'); ?> 
       <strong>Recogida:</strong> <?php echo htmlspecialchars($porte['fecha_recogida'] ?? 'N/A'); ?></p>
    <p><strong>Hasta:</strong> <?php echo htmlspecialchars($porte['localizacion_entrega'] ?? 'N/A'); ?> 
       <strong>Entrega:</strong> <?php echo htmlspecialchars($porte['fecha_entrega'] ?? 'N/A'); ?></p>
<!-- Mostrar el precio al que se asignó el porte -->
<?php if (!empty($precio_asignado)): ?>
    <p><strong>Precio asignado:</strong> <?php echo htmlspecialchars(number_format($precio_asignado, 2)); ?> €</p>
<?php else: ?>
    <p><strong>Precio asignado:</strong> No disponible (porte no fue reofrecido).</p>
<?php endif; ?>



    <!-- Formulario de búsqueda de destinatarios -->
    <form method="POST" action="">
        <label for="busqueda_destinatarios">Buscar destinatarios:</label>
        <input type="text" name="busqueda_destinatarios" id="busqueda_destinatarios">
        <button type="submit" name="buscar_destinatarios">Buscar</button>
    </form>

    <!-- Mostrar los resultados de búsqueda de contactos y entidades -->
    <?php
    if (isset($resultado_contactos) || isset($resultado_entidades)) {
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

            echo "</ul>";
        } else {
            echo "<p>No se encontraron contactos ni entidades que coincidan con la búsqueda.</p>";
        }
    }
    ?>

    <h3>Contactos Seleccionados</h3>
    <div id="listaSeleccionados"></div>



<form method="POST" action="enviar_oferta.php" onsubmit="return verificarSeleccionados();">
    <input type="hidden" name="porte_id" value="<?php echo htmlspecialchars($porte_id); ?>">
    <input type="hidden" id="destinatarios_seleccionados" name="destinatarios_seleccionados">

    <label for="precio">Precio de la oferta:</label>
    <input type="text" name="precio" id="precio" required>

    <label for="moneda">Moneda:</label>
    <input type="text" id="buscador_monedas" placeholder="Buscar moneda...">
    <div id="resultados_monedas"></div>
    <input type="text" id="moneda_mostrada" readonly placeholder="Moneda seleccionada">
    <input type="hidden" id="moneda_seleccionada" name="moneda_seleccionada" required> 

    <label for="deadline">Fecha Límite para Aceptar la Oferta:</label>
    <input type="datetime-local" name="deadline" id="deadline" required>
    <button type="submit">Mandar Oferta</button>
</form>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
 $(document).ready(function () {
    // Búsqueda de monedas
    $("#buscador_monedas").keyup(function () {
        var termino_busqueda = $(this).val();
        $.ajax({
            url: "buscar_monedas.php",
            method: "POST",
            data: { termino: termino_busqueda },
            success: function (respuesta) {
                var monedas = JSON.parse(respuesta);
                $("#resultados_monedas").empty();
                // Mostrar resultados de búsqueda
                $.each(monedas, function (index, moneda) {
                    $("#resultados_monedas").append("<div data-codigo='" + moneda.codigo + "'>" + moneda.nombre + "</div>");
                });
            },
            error: function (xhr, status, error) {
                console.error("Error en la solicitud AJAX: " + error);
            },
        });
    });

    // Selección de moneda
    $(document).on("click", "#resultados_monedas div", function () {
        var codigo_moneda = $(this).data("codigo"); // Código de la moneda
        var nombre_moneda = $(this).text(); // Nombre de la moneda

        // Asignar valores a los campos correspondientes
        $("#moneda_mostrada").val(nombre_moneda); // Mostrar nombre de la moneda seleccionada
        $("#moneda_seleccionada").val(codigo_moneda); // Guardar código de la moneda

        // Limpiar resultados y buscador
        $("#buscador_monedas").val('');
        $("#resultados_monedas").empty();
    });
});

// Validación del formulario antes de enviarlo
function verificarSeleccionados() {
    const moneda = $("#moneda_seleccionada").val();
    const precio = $("#precio").val();
    const destinatarios = $("#destinatarios_seleccionados").val();

    // Mostrar el valor actual de destinatarios seleccionados
    console.log("Destinatarios seleccionados:", destinatarios);

    if (!moneda) {
        alert("Debe seleccionar una moneda válida antes de enviar la oferta.");
        return false;
    }
    if (!precio || isNaN(precio)) {
        alert("Debe ingresar un precio numérico válido antes de enviar la oferta.");
        return false;
    }
    if (!destinatarios || destinatarios.length === 0) {
        alert("Debe seleccionar al menos un destinatario antes de enviar la oferta.");
        return false;
    }

    return true; // Todo está validado
}


</script>


    <!-- Formulario para ofrecer a empresa nueva -->
    <h3>Ofrecer a Empresa Nueva</h3>
    <form method="POST" action="">
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
        <label for="deadline">Fecha Límite para Aceptar la Oferta:</label>
        <input type="datetime-local" name="deadline" id="deadline" required>
        <br>
        <button type="submit" name="ofrecer_externamente">Generar Enlace de Oferta</button>
    </form>
    <button onclick="window.history.back()">Volver</button>

    <!-- JavaScript para la funcionalidad de selección -->
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
</body>
</html>
