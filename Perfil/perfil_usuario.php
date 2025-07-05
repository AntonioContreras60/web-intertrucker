<?php
/**
 * perfil_usuario.php
 * Diseño tarjeta + accesibilidad + respuesta móvil + BOTONES MÁS GRANDES (sin afectar al header)
 */

declare(strict_types=1);

/*--- DEPURACIÓN (quítalo en producción) ---*/
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/*--- SESIÓN ---*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    $destino = '/Perfil/iniciar_sesion.php?next=' . urlencode($_SERVER['REQUEST_URI']);
    header("Location: $destino");
    exit;
}

/*--- CONEXIÓN A BBDD ---*/
require_once dirname(__DIR__) . '/conexion.php';

/*--- CONSULTA DE DATOS DEL USUARIO ---*/
$usuario_id = (int) $_SESSION['usuario_id'];

$sql = 'SELECT nombre_usuario, email, telefono, cif
        FROM usuarios
        WHERE id = ?
        LIMIT 1';

$stmt = $conn->prepare($sql) or die('Error al preparar la consulta: '.$conn->error);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result  = $stmt->get_result();
$usuario = $result ? $result->fetch_assoc() : null;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>InterTrucker – Perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/styles.css"><!-- hoja global -->

    <style>
        :root{
            --it-primary:#0066cc;
            --it-primary-dark:#0052a8;
            --it-bg-light:#f5f7fa;
        }
        body{
            background:var(--it-bg-light);
            display:flex;
            flex-direction:column;
            min-height:100vh;
        }
        main{
            flex:1;
            padding:2rem 1rem 3rem;
            max-width:960px;
            margin:auto;
        }
        .card{
            background:#fff;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,.06);
            padding:2rem;
        }

        /* ---------- DATOS DEL USUARIO ---------- */
        .datos-grid{
            display:grid;
            grid-template-columns:140px 1fr;
            gap:.5rem 1rem;
            margin-bottom:1.8rem;
            font-size:1.05rem;
        }
        .datos-grid strong{
            color:#555;
            text-align:right;
        }
        .datos-grid span{
            overflow-wrap:anywhere; /* parte mails largos */
        }

        /* ---------- BOTONES DE GESTIÓN ---------- */
        .botones-gestion ul{
            list-style:none;
            margin:0;
            padding:0;
            display:grid;
            gap:1rem;
        }
        .botones-gestion a,
        .botones-gestion a:visited{
            display:flex;
            align-items:center;
            justify-content:center;
            padding:1.1rem 1.25rem;
            min-height:56px;
            background:var(--it-primary);
            color:#fff !important;
            font-size:1.1rem;
            font-weight:600;
            border-radius:8px;
            text-decoration:none;
            transition:background .2s;
        }
        .botones-gestion a:hover,
        .botones-gestion a:focus{
            background:var(--it-primary-dark);
        }

        /* ---------- RESPONSIVE ---------- */
        @media (min-width:600px){
            .botones-gestion ul{ grid-template-columns:repeat(2,1fr); }
        }
        @media (max-width:479px){
            .datos-grid{
                grid-template-columns:1fr;
            }
            .datos-grid strong{
                text-align:left;
            }
        }
    </style>
<link rel='stylesheet' href='/header.css'>
<script src='/header.js'></script>
</head>
<body>
<?php require_once $_SERVER["DOCUMENT_ROOT"]."/header.php"; ?>


<main>
    <div class="card">
        <?php if ($usuario): ?>
            <h1 style="margin-top:0;margin-bottom:1.3rem;">Perfil de Usuario</h1>

            <div class="datos-grid">
                <strong>Nombre:</strong>   <span><?= htmlspecialchars($usuario['nombre_usuario']); ?></span>
                <strong>Email:</strong>    <span><?= htmlspecialchars($usuario['email']); ?></span>
                <strong>Teléfono:</strong> <span><?= htmlspecialchars($usuario['telefono']); ?></span>
                <strong>CIF:</strong>      <span><?= htmlspecialchars($usuario['cif']); ?></span>
            </div>

            <h2 style="margin:0 0 1rem;font-size:1.25rem;">Gestión</h2>
            <nav class="botones-gestion">
                <ul>
                    <li><a href="editar_perfil.php">Modificar perfil</a></li>
                    <li><a href="cambiar_contrasena.php">Cambiar contraseña</a></li>
                    <li><a href="gestionar_direcciones_usuario.php" target="_blank">Gestionar direcciones empresa</a></li>
                    <li><a href="portes_consumo_mensual.php" target="_blank">Consultar consumo mensual</a></li>
                </ul>
            </nav>
        <?php else: ?>
            <p>No se encontró la información del usuario en la base de datos.</p>
        <?php endif; ?>
    </div>
</main>

<?php
/*--- CIERRE DE CONEXIÓN ---*/
$conn->close();
?>
</body>
</html>
