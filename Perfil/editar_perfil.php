<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="../styles.css">
    <script>
        function toggleCamioneroFields() {
            const checkbox = document.getElementById('convertir_camionero');
            const fields   = document.getElementById('camionero_fields');
            fields.style.display = checkbox.checked ? 'block' : 'none';
        }
    </script>
    <style>
        #camionero_fields { display: none; }
        form label { display:block;margin-top:.8rem;font-weight:600; }
        form input[type="text"],
        form input[type="email"],
        form input[type="date"]{ width:100%;max-width:420px;padding:.4rem }
    </style>
</head>
<body>
<?php
include '../conexion.php';
include '../header.php';

/* ---------- SESIÓN ---------- */
if (isset($_SESSION['usuario_id']) && isset($_SESSION['rol'])) {
    $usuario_id = (int) $_SESSION['usuario_id'];
    $rol        =        $_SESSION['rol'];
} else {
    echo "No se encontró la sesión de usuario. Por favor, inicia sesión.";
    exit();
}

/* ---------- DATOS DEL USUARIO ---------- */
$stmt = $conn->prepare(
    "SELECT nombre_usuario, apellidos, email, telefono, cif
     FROM usuarios WHERE id = ?"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ---------- DIRECCIÓN FISCAL ---------- */
$stmt = $conn->prepare(
    "SELECT id, nombre_via, numero, complemento,
            codigo_postal, ciudad, pais
     FROM direcciones
     WHERE usuario_id = ? AND tipo_direccion = 'fiscal'
     LIMIT 1"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$dirFiscal = $stmt->get_result()->fetch_assoc();   // null si no existe
$stmt->close();
?>

<h1>Editar Perfil</h1>

<form action="guardar_perfil.php" method="post">
    <!-- ---------- DATOS BÁSICOS ---------- -->
    <label for="nombre_usuario">Nombre de la Empresa (o Nombre Completo si es Autónomo):</label>
    <input type="text" id="nombre_usuario" name="nombre_usuario"
           value="<?= htmlspecialchars($user['nombre_usuario']) ?>" required>

    <label for="apellidos">Apellidos:</label>
    <input type="text" id="apellidos" name="apellidos"
           value="<?= htmlspecialchars($user['apellidos']) ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email"
           value="<?= htmlspecialchars($user['email']) ?>" required>

    <label for="telefono">Teléfono:</label>
    <input type="text" id="telefono" name="telefono"
           value="<?= htmlspecialchars($user['telefono']) ?>" required>

    <label for="cif">CIF:</label>
    <input type="text" id="cif" name="cif"
           value="<?= htmlspecialchars($user['cif']) ?>" required>

    <!-- ---------- DIRECCIÓN FISCAL ---------- -->
    <h2 style="margin-top:1.5rem">Dirección fiscal</h2>

    <!-- escondemos el id (0 si no existe) -->
    <input type="hidden" name="fiscal_id"
           value="<?= $dirFiscal ? (int)$dirFiscal['id'] : 0 ?>">

    <label for="fiscal_nombre_via">Calle / Vía:</label>
    <input type="text" id="fiscal_nombre_via" name="fiscal_nombre_via"
           value="<?= htmlspecialchars($dirFiscal['nombre_via'] ?? '') ?>" required>

    <label for="fiscal_numero">Número:</label>
    <input type="text" id="fiscal_numero" name="fiscal_numero"
           value="<?= htmlspecialchars($dirFiscal['numero'] ?? '') ?>" required>

    <label for="fiscal_complemento">Complemento:</label>
    <input type="text" id="fiscal_complemento" name="fiscal_complemento"
           value="<?= htmlspecialchars($dirFiscal['complemento'] ?? '') ?>">

    <label for="fiscal_codigo_postal">Código Postal:</label>
    <input type="text" id="fiscal_codigo_postal" name="fiscal_codigo_postal"
           value="<?= htmlspecialchars($dirFiscal['codigo_postal'] ?? '') ?>" required>

    <label for="fiscal_ciudad">Ciudad:</label>
    <input type="text" id="fiscal_ciudad" name="fiscal_ciudad"
           value="<?= htmlspecialchars($dirFiscal['ciudad'] ?? '') ?>" required>

    <label for="fiscal_pais">País:</label>
    <input type="text" id="fiscal_pais" name="fiscal_pais"
           value="<?= htmlspecialchars($dirFiscal['pais'] ?? '') ?>" required>

    <!-- ---------- OPCIÓN CAMIONERO (sólo admin) ---------- -->
    <?php if ($rol === 'administrador'): ?>
        <h2 style="margin-top:1.5rem">Definirme como Camionero</h2>
        <label>
            <input type="checkbox" id="convertir_camionero" name="convertir_camionero"
                   onchange="toggleCamioneroFields()">
            Convertirme en Camionero
        </label>

        <div id="camionero_fields">
            <label for="tipo_carnet">Tipo Carnet:</label>
            <input type="text" id="tipo_carnet" name="tipo_carnet">

            <label for="fecha_caducidad">Fecha Caducidad Carnet:</label>
            <input type="date" id="fecha_caducidad" name="fecha_caducidad">

            <label for="num_licencia">Número de Licencia:</label>
            <input type="text" id="num_licencia" name="num_licencia">

            <label for="caducidad_profesional">Caducidad Profesional:</label>
            <input type="date" id="caducidad_profesional" name="caducidad_profesional">
        </div>
    <?php endif; ?>

    <br><br>
    <input type="submit" value="Guardar Cambios">
    <a href="perfil_usuario.php">Volver</a>
</form>

<?php
$conn->close();
include '../footer.php';
?>
</body>
</html>
