<?php
session_start();
include 'conexion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificamos que exista usuario_id en la sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: Falta usuario_id en la sesión.");
}
$usuario_id = $_SESSION['usuario_id'];

/******************************************************************************
 * (A) Contar “Recibidos transferidos NO completados”
 *     para mostrar en el botón azul de “Recibidos transferidos (X)”
 ******************************************************************************/
$query_count_recibidos = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT so1.porte_id
        FROM seleccionados_oferta so1
        JOIN seleccionados_oferta so2
          ON so1.porte_id = so2.porte_id
         AND so1.usuario_id = so2.ofertante_id
         AND so2.fecha_seleccion > so1.fecha_seleccion
         AND NOT EXISTS (
           SELECT 1
           FROM seleccionados_oferta so_inner
           WHERE so_inner.porte_id = so1.porte_id
             AND so_inner.fecha_seleccion > so1.fecha_seleccion
             AND so_inner.fecha_seleccion < so2.fecha_seleccion
         )
        JOIN portes p ON so1.porte_id = p.id
        WHERE so1.usuario_id = ?
          AND NOT EXISTS (
              SELECT 1
              FROM porte_tren pt
              WHERE pt.porte_id = so1.porte_id
                AND pt.inicio_tren > so1.fecha_seleccion
          )
          AND p.estado_recogida_entrega <> 'Completado'
        GROUP BY so1.porte_id
    ) AS sub
";
$stmt_count_recib = $conn->prepare($query_count_recibidos);
if (!$stmt_count_recib) {
    die("Error preparando count de Recibidos: " . $conn->error);
}
$stmt_count_recib->bind_param("i", $usuario_id);
$stmt_count_recib->execute();
$res_count_recib = $stmt_count_recib->get_result();
$countRecibidosNoCompletados = $res_count_recib->fetch_assoc()['total'] ?? 0;
$stmt_count_recib->close();

/******************************************************************************
 * (B) Contar “Creados transferidos NO completados”
 ******************************************************************************/
$query_count_creados = "
    SELECT COUNT(DISTINCT p.id) AS total
    FROM portes p
    JOIN seleccionados_oferta so1 ON p.id = so1.porte_id
    JOIN usuarios u ON so1.usuario_id = u.id
    WHERE so1.ofertante_id = ?
      AND NOT EXISTS (
        SELECT 1 
        FROM seleccionados_oferta so2
        WHERE so2.porte_id = so1.porte_id
          AND so2.usuario_id = ?
      )
      AND p.estado_recogida_entrega <> 'Completado'
";
$stmt_count_creados = $conn->prepare($query_count_creados);
if (!$stmt_count_creados) {
    die("Error preparando count de Creados: " . $conn->error);
}
$stmt_count_creados->bind_param("ii", $usuario_id, $usuario_id);
$stmt_count_creados->execute();
$res_count_creados = $stmt_count_creados->get_result();
$countCreadosNoCompletados = $res_count_creados->fetch_assoc()['total'] ?? 0;
$stmt_count_creados->close();

/******************************************************************************
 * (C) Listar portes “Creados transferidos NO completados”
 *     con ORDER BY p.id DESC
 ******************************************************************************/
$query_creados_detalle = "
    SELECT DISTINCT 
      p.id, 
      p.mercancia_descripcion, 
      p.fecha_recogida, 
      p.localizacion_recogida, 
      p.localizacion_entrega,
      u.nombre_usuario AS nombre_destinatario
    FROM portes p
    JOIN seleccionados_oferta so1 ON p.id = so1.porte_id
    JOIN usuarios u ON so1.usuario_id = u.id
    WHERE so1.ofertante_id = ?
      AND NOT EXISTS (
        SELECT 1 
        FROM seleccionados_oferta so2
        WHERE so2.porte_id = so1.porte_id
          AND so2.usuario_id = ?
      )
      AND p.estado_recogida_entrega <> 'Completado'
    ORDER BY p.id DESC
";
$stmt_creados_det = $conn->prepare($query_creados_detalle);
if (!$stmt_creados_det) {
    die("Error preparando lista de Creados: " . $conn->error);
}
$stmt_creados_det->bind_param("ii", $usuario_id, $usuario_id);
$stmt_creados_det->execute();
$res_creados_det = $stmt_creados_det->get_result();

$portesCreados = [];
while ($row = $res_creados_det->fetch_assoc()) {
    $portesCreados[] = $row;
}
$stmt_creados_det->close();
$num_creados = count($portesCreados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portes Transferidos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    body { margin:0; font-family:Arial,sans-serif; font-size:16px; }
    h1, h2, h3 { margin:16px; }

    nav ul {
      list-style:none; 
      padding:0; 
      margin:0; 
      display:flex; 
      gap:20px;
    }

    /* Collapsibles => móvil */
    .collapsible {
      background-color:#e2e2e2;
      cursor:pointer;
      padding:12px 15px;
      margin-bottom:5px;
      border:none;
      outline:none;
      width:100%;
      text-align:left;
      font-size:1em;
    }
    .collapsible.active {
      background-color:#ccc;
    }
    .content {
      display:none;
      padding:10px;
      background-color:#f9f9f9;
      margin-bottom:15px;
    }
    /* Card => móvil */
    .card {
      border:1px solid #ccc;
      border-radius:5px;
      background-color:#fff;
      padding:10px;
      margin-bottom:10px;
    }
    .card h3 {
      margin:0 0 8px;
      font-size:1em;
      font-weight:bold;
    }
    .card p {
      margin:4px 0;
      font-size:0.95em;
    }
    .actions {
      margin-top:8px;
    }
    .actions a {
      display:inline-block;
      margin-right:5px;
      text-decoration:none;
      padding:6px 10px;
      border-radius:3px;
      color:#fff;
    }

    /* Tabs => escritorio */
    .tabs {
      display:flex;
      gap:10px;
      list-style:none;
      margin:16px;
      padding:0;
    }
    .tabs li {
      background-color:#007bff; 
      color:#fff; 
      padding:10px 15px; 
      border-radius:5px; 
      cursor:pointer; 
      font-weight:bold; 
      font-size:1em;
    }
    .tabs li:hover {
      background-color:#0056b3;
    }
    .tabs li.active {
      background-color:#28a745;
    }
    .tab-content {
      display:none; 
      padding:16px;
    }
    .tab-content.active {
      display:block;
    }

    /* Responsivo */
    @media (max-width:767px) {
      .desktop-only { display:none; }
    }
    @media (min-width:768px) {
      .mobile-only { display:none; }
      .desktop-only {
        max-width:1600px;
        margin:0 auto;
        font-size:14px;
      }
      table {
        width:100%;
        border-collapse:collapse;
        margin-top:10px;
      }
      th, td {
        border:1px solid #ccc;
        padding:6px 8px;
      }
      th {
        background:#f2f2f2;
      }
      .btn-accion {
        border:none; 
        border-radius:3px; 
        padding:6px 10px; 
        cursor:pointer; 
        color:#fff;
        margin-right:5px;
        text-decoration:none;
      }
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Collapsibles => móvil
      const collapsibles = document.querySelectorAll('.collapsible');
      collapsibles.forEach(btn => {
        btn.addEventListener('click', function(){
          this.classList.toggle('active');
          const c = this.nextElementSibling;
          c.style.display = (c.style.display === "block") ? "none" : "block";
        });
      });

      // Versión escritorio => single tab
      showSection('creadosTab');
    });

    function showSection(sectionId){
      const allTabs = document.querySelectorAll('.tabs li');
      allTabs.forEach(t => t.classList.remove('active'));

      const allContent = document.querySelectorAll('.tab-content');
      allContent.forEach(c => c.classList.remove('active'));

      const selTab = document.querySelector(`.tabs li[data-section="${sectionId}"]`);
      if(selTab) selTab.classList.add('active');

      const selContent = document.getElementById(sectionId);
      if(selContent) selContent.classList.add('active');
    }
  </script>
<link rel='stylesheet' href='/header.css'>
<script src='/header.js'></script>
</head>
<body>
<?php require_once $_SERVER["DOCUMENT_ROOT"]."/header.php"; ?>

<main style="padding:16px;">
  <h1>Portes Transferidos</h1>

  <!-- Menú => Recibidos con la cuenta en azul, Creados con subrayado y su cuenta -->
  <nav style="margin-left:16px;">
    <ul style="list-style-type:none; padding:0; margin:0; display:flex; gap:20px; justify-content:left;">

      <!-- Botón Recibidos, mostrando countRecibidosNoCompletados -->
      <li>
        <a href="portes_enviados_recibidos.php"
           style="display:block; padding:15px 20px; text-align:center;
                  background-color:#007bff; color:#fff; border-radius:5px;
                  text-decoration:none; font-weight:bold;">
          Recibidos Transferidos (<?php echo $countRecibidosNoCompletados; ?>)
        </a>
      </li>

      <!-- Botón Creados, mostrando countCreadosNoCompletados -->
      <li>
        <a href="portes_enviados_propios.php"
           style="display:block; padding:15px 20px; text-align:center; text-decoration:underline;
                  background-color:#28a745; color:white; border-radius:5px;
                  font-weight:bold; font-size:1.2em;">
          Creados Transferidos (<?php echo $countCreadosNoCompletados; ?>)
        </a>
      </li>
    </ul>
  </nav>

  <!-- Versión móvil => collapsible -->
  <div class="mobile-only">

    <div class="content">
      <?php if($num_creados > 0): ?>
        <?php foreach($portesCreados as $row): ?>
          <div class="card">
            <h3>ID: #<?php echo htmlspecialchars($row['id']); ?></h3>
            <p><strong>Mercancía:</strong> 
               <?php echo htmlspecialchars($row['mercancia_descripcion']); ?></p>
            <p><strong>Origen:</strong>
               <?php echo htmlspecialchars($row['localizacion_recogida']); ?> -
               <?php echo htmlspecialchars($row['fecha_recogida']); ?>
            </p>
            <p><strong>Destino:</strong>
               <?php echo htmlspecialchars($row['localizacion_entrega']); ?></p>
            <p><strong>Transferido a:</strong> 
               <?php echo htmlspecialchars($row['nombre_destinatario']); ?></p>

            <div class="actions">
              <a href="detalle_porte.php?id=<?php echo $row['id']; ?>" 
                 style="background-color:#6c757d;">Detalles</a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=recogida"
                 style="background-color:#ffc107;">Recogida</a>
              <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=entrega"
                 style="background-color:#17a2b8;">Entrega</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay portes creados transferidos.</p>
      <?php endif; ?>
    </div>
  </div><!-- Fin .mobile-only -->

  <!-- Versión escritorio => un solo tab con tabla -->
    <div class="desktop-only">
      <h2>Creados Transferidos</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Mercancía</th>
            <th>Origen - Fecha</th>
            <th>Destino</th>
            <th>Transferido a</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if($num_creados > 0): ?>
          <?php foreach($portesCreados as $row): ?>
            <tr>
              <td>#<?php echo htmlspecialchars($row['id']); ?></td>
              <td><?php echo htmlspecialchars($row['mercancia_descripcion']); ?></td>
              <td>
                <?php echo htmlspecialchars($row['localizacion_recogida']); ?><br>
                <?php echo htmlspecialchars($row['fecha_recogida']); ?>
              </td>
              <td><?php echo htmlspecialchars($row['localizacion_entrega']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_destinatario']); ?></td>
              <td>
                <a href="detalle_porte.php?id=<?php echo $row['id']; ?>" 
                   class="btn-accion" style="background-color:#6c757d;"
                   target="_blank">
                  Detalles
                </a>
                <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=recogida"
                   class="btn-accion" style="background-color:#ffc107;"
                   target="_blank">
                  Recogida
                </a>
                <a href="recogida_entrega_vista.php?porte_id=<?php echo $row['id']; ?>&tipo_evento=entrega"
                   class="btn-accion" style="background-color:#17a2b8;"
                   target="_blank">
                  Entrega
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6">No hay portes creados transferidos.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div><!-- Fin #creadosTab -->
  </div><!-- Fin .desktop-only -->

</main>

<?php include 'footer.php'; ?>
</body>
</html>
