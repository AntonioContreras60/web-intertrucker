<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

// Comprobamos si la sesión contiene el usuario_id
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No se encontró el usuario en la sesión.");
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado
$admin_id = null; // ID del administrador relacionado
$busqueda = '';
$resultado_contactos = null;
$resultado_entidades = null;

/**
 * Función que muestra las direcciones adicionales para un contacto (usuario)
 * en la tabla `direcciones` (enlazadas por usuario_id).
 */
function mostrar_direcciones($conn, $contactoUsuarioId, $nombre, $cif, $email, $telefono) {
    // Consulta direcciones de la tabla `direcciones` para un usuario
    $sql = "SELECT * FROM direcciones WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $contactoUsuarioId);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        echo "<ul>";
        while ($fila = $resultado->fetch_assoc()) {
            $direccion_completa =
                ($fila['nombre_via'] ?? '') . " " .
                ($fila['numero'] ?? '') . " " .
                ($fila['complemento'] ?? '') . ", " .
                ($fila['ciudad'] ?? '') . " " .
                ($fila['estado_provincia'] ?? '') . ", " .
                ($fila['codigo_postal'] ?? '') . ", " .
                ($fila['pais'] ?? '');

            echo "<li>";
            echo "Dirección: " . htmlspecialchars($direccion_completa) . "<br>";
            // Botón para seleccionar esta dirección concreta
            echo "<button onclick=\"seleccionarDireccion('"
                . htmlspecialchars($nombre, ENT_QUOTES) . "', '"
                . htmlspecialchars($cif, ENT_QUOTES) . "', '"
                . htmlspecialchars($direccion_completa, ENT_QUOTES) . "', '"
                . htmlspecialchars($telefono, ENT_QUOTES) . "', '"
                . htmlspecialchars($email, ENT_QUOTES) . "')\">
                      Seleccionar Dirección
                  </button>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No se encontraron direcciones adicionales para este contacto.</p>";
    }
}

// 1) Buscar el `admin_id` si el usuario es gestor
$sql_admin = "SELECT admin_id FROM usuarios WHERE id = ?";
$stmt_admin = $conn->prepare($sql_admin);
if (!$stmt_admin) {
    die("Error al preparar la consulta del administrador: " . $conn->error);
}
$stmt_admin->bind_param("i", $usuario_id);
$stmt_admin->execute();
$resultado_admin = $stmt_admin->get_result();

if ($resultado_admin->num_rows > 0) {
    $admin_id = $resultado_admin->fetch_assoc()['admin_id'];
} else {
    // Si el usuario no tiene un `admin_id`, asumimos que es un administrador
    $admin_id = $usuario_id;
}
$stmt_admin->close();

// 2) Verificamos si se ha enviado una búsqueda
if (isset($_POST['busqueda'])) {
    $busqueda = '%' . $_POST['busqueda'] . '%';

    // 2a) Consulta para buscar contactos (usuarios) relacionados
    //     que estén en la tabla `contactos` (relación con el usuario actual o su admin)
    $sql_contactos = "
        SELECT 
            u.id, 
            u.nombre_usuario, 
            u.email, 
            u.telefono, 
            u.cif, 
            'contacto' AS tipo
        FROM contactos c
        JOIN usuarios u ON u.id = c.contacto_usuario_id
        WHERE (c.usuario_id = ? OR c.usuario_id = ?)
          AND (u.nombre_usuario LIKE ? OR u.email LIKE ?)
    ";
    $stmt_contactos = $conn->prepare($sql_contactos);
    if (!$stmt_contactos) {
        die("Error al preparar la consulta de contactos: " . $conn->error);
    }
    $stmt_contactos->bind_param("iiss", $usuario_id, $admin_id, $busqueda, $busqueda);
    $stmt_contactos->execute();
    $resultado_contactos = $stmt_contactos->get_result();

    // 2b) Consulta para buscar entidades asociadas al admin (o al usuario si es admin)
    $sql_entidades = "
        SELECT 
            id,
            nombre,
            direccion,
            email,
            telefono,
            cif,
            'entidad' AS tipo
        FROM entidades
        WHERE usuario_id = ?
          AND (nombre LIKE ? OR email LIKE ?)
    ";
    $stmt_entidades = $conn->prepare($sql_entidades);
    if (!$stmt_entidades) {
        die("Error al preparar la consulta de entidades: " . $conn->error);
    }
    $stmt_entidades->bind_param("iss", $admin_id, $busqueda, $busqueda);
    $stmt_entidades->execute();
    $resultado_entidades = $stmt_entidades->get_result();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Contactos y Entidades</title>
    <script>
    /**
     * Envía los datos de la entidad/usuario seleccionado a la ventana principal,
     * rellenando los campos correspondientes (según ?tipo=expedidor|receptor|cliente),
     * y luego cierra la ventana actual.
     */
    function seleccionarDireccion(nombre, cif, direccion, telefono, email) {
        const tipo = '<?php echo isset($_GET['tipo']) ? $_GET['tipo'] : ''; ?>';

        if (window.opener && !window.opener.closed) {
            if (tipo === 'expedidor') {
                window.opener.document.getElementById('recogida_expedidor_nombre').value = nombre;
                window.opener.document.getElementById('recogida_direccion').value = direccion;
                window.opener.document.getElementById('recogida_expedidor_email').value = email;
                window.opener.document.getElementById('recogida_expedidor_telefono').value = telefono;
                window.opener.document.getElementById('recogida_expedidor_cif').value = cif;
            } else if (tipo === 'receptor') {
                window.opener.document.getElementById('entrega_receptor_nombre').value = nombre;
                window.opener.document.getElementById('entrega_direccion').value = direccion;
                window.opener.document.getElementById('entrega_receptor_email').value = email;
                window.opener.document.getElementById('entrega_receptor_telefono').value = telefono;
                window.opener.document.getElementById('entrega_receptor_cif').value = cif;
            } else if (tipo === 'cliente') {
                window.opener.document.getElementById('cliente_nombre').value = nombre;
                window.opener.document.getElementById('cliente_direccion').value = direccion;
                window.opener.document.getElementById('cliente_email').value = email;
                window.opener.document.getElementById('cliente_telefono').value = telefono;
                window.opener.document.getElementById('cliente_cif').value = cif;
            }
            window.close();
        } else {
            alert('La ventana principal no está disponible.');
        }
    }

    function cerrarVentana() {
        window.close();
    }
    </script>
</head>
<body>

<h1>Buscar Contactos y Entidades</h1>

<form method="POST" action="">
    <input type="text" name="busqueda" placeholder="Buscar por nombre o email"
           value="<?php echo isset($_POST['busqueda']) ? htmlspecialchars($_POST['busqueda']) : ''; ?>">
    <button type="submit">Buscar</button>
</form>

<!-- Contactos (usuarios) -->
<h2>Resultados de Contactos</h2>
<?php
if (!empty($busqueda) && $resultado_contactos) {
    if ($resultado_contactos->num_rows > 0) {
        echo "<ul>";
        while ($contacto = $resultado_contactos->fetch_assoc()) {
            $nombre     = $contacto['nombre_usuario'];
            $email      = $contacto['email'];
            $tel        = $contacto['telefono'];
            $cif        = $contacto['cif'];
            $usuarioId  = $contacto['id'];

            echo "<li>";
            echo "Nombre: " . htmlspecialchars($nombre) . "<br>";
            echo "Email: " . htmlspecialchars($email) . "<br>";
            echo "Teléfono: " . htmlspecialchars($tel) . "<br>";
            echo "CIF: " . htmlspecialchars($cif) . "<br><br>";

            // Botón para seleccionar sin dirección concreta
            echo "<button onclick=\"seleccionarDireccion('"
                . htmlspecialchars($nombre, ENT_QUOTES) . "', '"
                . htmlspecialchars($cif, ENT_QUOTES) . "', '', '"
                . htmlspecialchars($tel, ENT_QUOTES) . "', '"
                . htmlspecialchars($email, ENT_QUOTES) . "')\">
                    Seleccionar (sin dirección)
                  </button><br><br>";

            // Mostrar direcciones (si existen) en la tabla 'direcciones'
            mostrar_direcciones($conn, $usuarioId, $nombre, $cif, $email, $tel);

            echo "</li><hr>";
        }
        echo "</ul>";
    } else {
        echo "<p>No se encontraron contactos con esa búsqueda.</p>";
    }
} else {
    echo "<p>Realice una búsqueda para mostrar contactos.</p>";
}
?>

<!-- Entidades (una sola dirección por entidad) -->
<h2>Resultados de Entidades</h2>
<?php
if (!empty($busqueda) && $resultado_entidades) {
    if ($resultado_entidades->num_rows > 0) {
        echo "<ul>";
        while ($entidad = $resultado_entidades->fetch_assoc()) {
            $entNombre    = $entidad['nombre'];
            $entDireccion = $entidad['direccion'];
            $entEmail     = $entidad['email'];
            $entTel       = $entidad['telefono'];
            $entCif       = $entidad['cif'];

            echo "<li>";
            echo "Nombre: " . htmlspecialchars($entNombre) . "<br>";
            echo "Dirección: " . htmlspecialchars($entDireccion) . "<br>";
            echo "Email: " . htmlspecialchars($entEmail) . "<br>";
            echo "Teléfono: " . htmlspecialchars($entTel) . "<br>";
            echo "CIF: " . htmlspecialchars($entCif) . "<br><br>";

            // Botón para seleccionar la entidad con su dirección
            echo "<button onclick=\"seleccionarDireccion('"
                . htmlspecialchars($entNombre, ENT_QUOTES) . "', '"
                . htmlspecialchars($entCif, ENT_QUOTES) . "', '"
                . htmlspecialchars($entDireccion, ENT_QUOTES) . "', '"
                . htmlspecialchars($entTel, ENT_QUOTES) . "', '"
                . htmlspecialchars($entEmail, ENT_QUOTES) . "')\">
                    Seleccionar Entidad
                  </button>";

            echo "</li><hr>";
        }
        echo "</ul>";
    } else {
        echo "<p>No se encontraron entidades con esa búsqueda.</p>";
    }
} else {
    echo "<p>Realice una búsqueda para mostrar entidades.</p>";
}

// Cerrar las consultas y la conexión
if (isset($stmt_contactos)) $stmt_contactos->close();
if (isset($stmt_entidades)) $stmt_entidades->close();
$conn->close();
?>

<button type="button" onclick="cerrarVentana()">Cerrar</button>

</body>
</html>
