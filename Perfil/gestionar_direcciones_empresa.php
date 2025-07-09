<?php
/**
 * Gestionar Direcciones de la Empresa
 * Muestra las direcciones asociadas al administrador del usuario en sesión
 * y permite añadir / editar / borrar.
 */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo "No se encontró la sesión de usuario. Por favor, inicia sesión.";
    exit();
}

require_once '../conexion.php';

/* --- DEPURACIÓN (quítalo en producción) --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ---------- OBTENER admin_id ---------- */
$usuario_id = (int)$_SESSION['usuario_id'];

$sql_admin = "SELECT admin_id, rol FROM usuarios WHERE id = ? LIMIT 1";
$stmt_admin = $conn->prepare($sql_admin) or die("Error al preparar admin_id: " . $conn->error);
$stmt_admin->bind_param("i", $usuario_id);
$stmt_admin->execute();
$datosUser = $stmt_admin->get_result()->fetch_assoc();
$stmt_admin->close();

$admin_id = (int)$datosUser['admin_id'];
$es_admin = ($datosUser['rol'] === 'administrador');
if ($es_admin || $admin_id === 0) {          // si el propio usuario es el admin
    $admin_id = $usuario_id;
}

/* ---------- DIRECCIONES DEL ADMIN ---------- */
$sql_dir = "
    SELECT id, nombre_via, numero, complemento, ciudad, estado_provincia,
           codigo_postal, pais, tipo_direccion
    FROM direcciones
    WHERE usuario_id = ?
";
$stmt_dir = $conn->prepare($sql_dir) or die("Error al preparar direcciones: " . $conn->error);
$stmt_dir->bind_param("i", $admin_id);
$stmt_dir->execute();
$dirs = $stmt_dir->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Direcciones de la Empresa</title>
    <link rel="stylesheet" href="/styles.css">
    <style>
        /* Botón principal: usa la misma paleta que el global */
        .btn-primary{
            display:inline-block;padding:0.75rem 1.25rem;
            background:var(--it-primary,#0066cc);color:#fff;font-weight:600;
            border-radius:6px;text-decoration:none;
            transition:background .2s;
        }
        .btn-primary:hover{background:var(--it-primary-dark,#0052a8);}
        ul{list-style:none;padding:0;}
        li{margin-bottom:1.5rem;}
        hr{border:0;border-top:1px solid #ddd;}
    </style>
</head>
<body>

<?php require_once dirname(__DIR__) . '/header.php'; ?>

<main style="max-width:960px;margin:auto;padding:2rem 1rem">
    <h1 style="margin-top:0">Direcciones de la Empresa</h1>

    <?php if ($dirs->num_rows > 0): ?>
        <ul>
            <?php while ($d = $dirs->fetch_assoc()): ?>
                <li>
                    <strong>Calle:</strong>
                    <?= htmlspecialchars($d['nombre_via']) . ", " . htmlspecialchars($d['numero']) ?><br>
                    <?php if (!empty($d['complemento'])): ?>
                        <strong>Complemento:</strong> <?= htmlspecialchars($d['complemento']) ?><br>
                    <?php endif; ?>
                    <strong>Ciudad:</strong> <?= htmlspecialchars($d['ciudad']) ?><br>
                    <strong>Estado/Provincia:</strong> <?= htmlspecialchars($d['estado_provincia']) ?><br>
                    <strong>Código Postal:</strong> <?= htmlspecialchars($d['codigo_postal']) ?><br>
                    <strong>País:</strong> <?= htmlspecialchars($d['pais']) ?><br>
                    <strong>Tipo:</strong>
                    <?= $d['tipo_direccion'] === 'fiscal' ? 'Fiscal' : 'Recogida/Entrega' ?><br>

                    <a href="modificar_direccion.php?id=<?= $d['id'] ?>&usuario_id=<?= $admin_id ?>">Modificar</a> |
                    <a href="eliminar_direccion.php?id=<?= $d['id'] ?>&usuario_id=<?= $admin_id ?>"
                       onclick="return confirm('¿Seguro que quieres eliminar esta dirección?');">
                       Eliminar
                    </a>
                </li>
                <hr>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No hay direcciones registradas para la empresa.</p>
    <?php endif; ?>

    <!-- Enlace para añadir nueva dirección -->
    <a class="btn-primary" href="añadir_direccion_usuario.php?usuario_id=<?= $admin_id ?>">
        Añadir Nueva Dirección
    </a>
</main>

<?php
$stmt_dir->close();
$conn->close();
?>
</body>
</html>
