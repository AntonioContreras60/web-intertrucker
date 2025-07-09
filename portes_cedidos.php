<?php
session_start();
include 'conexion.php';

/* --- DEPURACIÓN (quítalo en producción) --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* --- SESIÓN --- */
if (!isset($_SESSION['usuario_id'])) {
    die("Error: Falta usuario_id en la sesión.");
}
$usuarioSesion = (int)$_SESSION['usuario_id'];

/* --- EMPRESAS CON PORTES SIN FINALIZAR --- */
$sql = "
    SELECT 
      so.usuario_id           AS empresa_id,
      u.nombre_empresa        AS empresa_nombre,
      COUNT(*)                AS total_portes
    FROM seleccionados_oferta so
    JOIN portes     p ON so.porte_id  = p.id
    JOIN usuarios   u ON so.usuario_id = u.id
    WHERE so.ofertante_id = ?
      AND p.fecha_entrega >= CURDATE()
    GROUP BY so.usuario_id
    ORDER BY u.nombre_empresa ASC
";
$stmt = $conn->prepare($sql) or die('Error preparando la consulta: '.$conn->error);
$stmt->bind_param("i", $usuarioSesion);
$stmt->execute();
$res = $stmt->get_result();

$empresas = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portes cedidos a empresas</title>
    <link rel="stylesheet" href="/styles.css">
    <style>
        /* ---------- Responsive table ---------- */
        .tabla-responsive{overflow-x:auto}
        table{border-collapse:collapse;width:100%}
        th,td{border:1px solid #ccc;padding:.6rem}
        th{background:#f5f7fa}

        @media (max-width:600px){
            table,thead,tbody,th,td,tr{display:block}
            thead{display:none}
            tr{margin-bottom:1rem;border:1px solid #ccc;border-radius:6px;padding:.6rem}
            td{border:none;border-bottom:1px solid #eee;position:relative;padding-left:50%}
            td::before{
                content:attr(data-label);
                position:absolute;left:.6rem;top:.6rem;font-weight:600
            }
            td:last-child{border-bottom:none}
        }

        /* ---------- Botón primario ---------- */
        .btn{
            display:inline-block;padding:.5rem 1rem;
            background:var(--it-primary,#0066cc);color:#fff;
            border-radius:6px;font-weight:600;text-decoration:none;
            transition:background .2s
        }
        .btn:hover{background:var(--it-primary-dark,#0052a8)}
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main style="max-width:960px;margin:auto;padding:2rem 1rem">
    <h1 style="margin-top:0">Portes transferidos a empresas</h1>

    <?php if ($empresas): ?>
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa / Transportista</th>
                        <th>Nº Portes sin finalizar</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($empresas as $e): ?>
                    <tr>
                        <td data-label="ID"><?= htmlspecialchars($e['empresa_id']) ?></td>
                        <td data-label="Empresa"><?= htmlspecialchars($e['empresa_nombre']) ?></td>
                        <td data-label="Nº Portes"><?= htmlspecialchars($e['total_portes']) ?></td>
                        <td data-label="Acción">
                            <a class="btn"
                               href="portes_cedidos_empresa.php?usuario_id=<?= $e['empresa_id'] ?>">
                                Ver portes
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No hay empresas con portes asignados que tengan fecha de entrega pendiente (≥ hoy).</p>
    <?php endif; ?>
</main>

<?php $conn->close(); ?>
</body>
</html>
