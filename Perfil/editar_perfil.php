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
            const fields = document.getElementById('camionero_fields');
            fields.style.display = checkbox.checked ? 'block' : 'none';
        }
    </script>
    <style>
        #camionero_fields { display: none; }
    </style>
</head>
<body>
    <?php
    include '../conexion.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/header.php';

    // Obtener el ID de usuario y rol de la sesión
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['rol'])) {
        $usuario_id = $_SESSION['usuario_id'];
        $rol = $_SESSION['rol'];
    } else {
        echo "No se encontró la sesión de usuario. Por favor, inicia sesión.";
        exit();
    }

    // Consulta para obtener la información actual del usuario
    $sql = "SELECT nombre_usuario, apellidos, email, telefono, cif FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    ?>

    <h1>Editar Perfil</h1>
    <form action="guardar_perfil.php" method="post">
        <label for="nombre_usuario">Nombre de la Empresa (o Nombre Completo si es Autónomo):</label>
        <input type="text" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($row['nombre_usuario']); ?>" required><br>

        <label for="apellidos">Apellidos:</label>
        <input type="text" id="apellidos" name="apellidos" value="<?php echo htmlspecialchars($row['apellidos']); ?>" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required><br>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($row['telefono']); ?>" required><br>

        <label for="cif">CIF:</label>
        <input type="text" id="cif" name="cif" value="<?php echo htmlspecialchars($row['cif']); ?>" required><br>

        <!-- Solo el administrador puede definirse como camionero -->
        <?php if ($rol === 'administrador'): ?>
            <h2>Definirme como Camionero</h2>
            <label>
                <input type="checkbox" id="convertir_camionero" name="convertir_camionero" onchange="toggleCamioneroFields()">
                Convertirme en Camionero
            </label>

            <div id="camionero_fields">
                <label for="tipo_carnet">Tipo Carnet:</label>
                <input type="text" id="tipo_carnet" name="tipo_carnet"><br>

                <label for="fecha_caducidad">Fecha Caducidad Carnet:</label>
                <input type="date" id="fecha_caducidad" name="fecha_caducidad"><br>

                <label for="num_licencia">Número de Licencia:</label>
                <input type="text" id="num_licencia" name="num_licencia"><br>

                <label for="caducidad_profesional">Caducidad Profesional:</label>
                <input type="date" id="caducidad_profesional" name="caducidad_profesional"><br>
            </div>
        <?php endif; ?>

        <input type="submit" value="Guardar Cambios">
        <a href="perfil_usuario.php">Volver</a>
    </form>

    <?php
    } else {
        echo "No se encontró la información del usuario.";
    }

    $conn->close();
    include '../footer.php';
    ?>
</body>
</html>
