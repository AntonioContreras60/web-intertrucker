<?php
session_start();
include 'conexion.php'; // Ajusta la ruta si es distinta

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo "Error: Usuario no autenticado. Por favor, inicia sesión.";
    exit;
}

// Tomamos info de la sesión (si la tienes disponible)
$usuario_id = $_SESSION['usuario_id'];
$rol        = $_SESSION['rol']        ?? '';
$admin_id   = $_SESSION['admin_id']   ?? 0;

// Obtener la fecha de hoy (YYYY-MM-DD) para usarla por defecto
$fecha_hoy = date('Y-m-d');

// --- 1) Buscar trenes disponibles (opcional) ---
$trenes_disponibles = [];

if ($rol === 'administrador' || $rol === 'gestor') {
    // Listar trenes asociados a la empresa (admin_id), 
    // a través de la relación: tren -> tren_camionero -> camioneros -> usuarios
    $sql_trenes = "
        SELECT DISTINCT t.id, t.tren_nombre
        FROM tren t
        JOIN tren_camionero tc ON t.id = tc.tren_id
        JOIN camioneros c      ON tc.camionero_id = c.id
        JOIN usuarios u        ON c.usuario_id    = u.id
        WHERE u.admin_id = ?
    ";
    $stmt = $conn->prepare($sql_trenes);
    if (!$stmt) {
        die('Error al preparar la lista de trenes (admin/gestor): ' . $conn->error);
    }
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $trenes_disponibles[] = [
            'id'     => $row['id'],
            'nombre' => $row['tren_nombre']
        ];
    }
    $stmt->close();

} elseif ($rol === 'camionero') {
    // 1) Averiguar el camionero.id (tabla camioneros) según este usuario
    $sql_cam = "SELECT id FROM camioneros WHERE usuario_id = ? AND activo = 1 LIMIT 1";
    $stmtCam = $conn->prepare($sql_cam);
    if (!$stmtCam) {
        die('Error en prepare (camionero): ' . $conn->error);
    }
    $stmtCam->bind_param('i', $usuario_id);
    $stmtCam->execute();
    $res_cam = $stmtCam->get_result();
    $row_cam = $res_cam->fetch_assoc();
    $stmtCam->close();

    // 2) Sacar los trenes de ese camionero
    if ($row_cam) {
        $camionero_id = $row_cam['id'];
        $sql_trenes = "
            SELECT DISTINCT t.id, t.tren_nombre
            FROM tren t
            JOIN tren_camionero tc ON t.id = tc.tren_id
            WHERE tc.camionero_id = ?
        ";
        $stmtT = $conn->prepare($sql_trenes);
        if (!$stmtT) {
            die('Error en prepare trenes (camionero): ' . $conn->error);
        }
        $stmtT->bind_param('i', $camionero_id);
        $stmtT->execute();
        $resT = $stmtT->get_result();
        while ($row = $resT->fetch_assoc()) {
            $trenes_disponibles[] = [
                'id'     => $row['id'],
                'nombre' => $row['tren_nombre']
            ];
        }
        $stmtT->close();
    }
}
// --- fin de trenes_disponibles ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>InterTrucker - Registrar Nueva Factura</title>
    <!-- Si tienes una hoja de estilos, enlázala. Aquí un poco de estilo inline para demo: -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 400px;
            /* Ajusta el tamaño a tu gusto */
        }
        .container {
            background: #fff;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            color: #333;
        }
        label {
            font-weight: bold;
        }
        input[type="date"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            margin: 5px 0 15px 0;
            padding: 8px;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical; /* Permite redimensionar en vertical si hace falta */
        }
        .btn-submit {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <!-- Cabecera con logo -->
    <header>
        <!-- Ajusta la ruta 'logo_intertrucker.png' si tu archivo se llama distinto o está en otra carpeta -->
        <img src="/imagenes/logos/intertrucker_chato.jpg" alt="InterTrucker Logo" class="logo">
    </header>

    <div class="container">
        <h1>Registrar Nueva Factura</h1>

        <form action="guardar_factura.php" method="post" enctype="multipart/form-data">
            
            <!-- FECHA -->
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="<?php echo $fecha_hoy; ?>" required>

            <!-- TIPO -->
            <label for="tipo">Tipo:</label>
            <select id="tipo" name="tipo" required>
                <option value="">--Seleccione--</option>
                <option value="Dietas">Dietas</option>
                <option value="Mantenimiento">Mantenimiento</option>
                <option value="Combustible">Combustible</option>
                <option value="Peajes">Peajes</option>
                <!-- Agrega más opciones si lo deseas -->
            </select>

            <!-- CANTIDAD -->
            <label for="cantidad">Cantidad:</label>
            <input type="number" id="cantidad" name="cantidad" step="0.01" required placeholder="Ej: 123.45">

            <!-- TREN (opcional) -->
            <!-- Solo aparecerá en el formulario, el guardado lo harás en guardar_factura.php -->
            <label for="tren_id">Tren (opcional):</label>
            <select id="tren_id" name="tren_id">
                <option value="">--Sin tren--</option>
                <?php foreach ($trenes_disponibles as $tren): ?>
                    <option value="<?php echo $tren['id']; ?>">
                        <?php echo htmlspecialchars($tren['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- FOTO -->
            <label for="foto">Adjuntar Factura (PDF o imagen, máx 20 MB):</label>
            <input type="file" id="foto" name="foto" accept=".pdf,.jpg,.jpeg,.png">

            <!-- OBSERVACIONES -->
            <label for="observaciones">Observaciones:</label>
            <textarea id="observaciones" name="observaciones" rows="4" placeholder="Comentarios adicionales"></textarea>

            <!-- usuario_id oculto -->
            <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($usuario_id); ?>">

            <!-- BOTÓN DE ENVÍO -->
            <button type="submit" class="btn-submit">Registrar Factura</button>
        </form>
    </div>
</body>
</html>
