<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener el ID del usuario
$usuario_id = $_SESSION['usuario_id'];

// Función para contar registros
function contarPortes($conn, $sql, ...$params) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la consulta SQL: " . htmlspecialchars($conn->error) . "\nConsulta: " . htmlspecialchars($sql));
    }
    
    // Verificar si hay parámetros adicionales y hacer bind automáticamente
    if (!empty($params)) {
        $types = str_repeat('i', count($params)); // Usar 'i' para cada parámetro entero (puedes ajustar según el tipo de datos)
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

// Código para contar las ofertas y portes en las distintas secciones:

// 1. Código para contar Nuevas Ofertas Recibidas (sin caducar)
$sql_count_recibidos = "
    SELECT COUNT(*) AS total
    FROM portes p
    JOIN ofertas_varios o ON p.id = o.porte_id
    WHERE o.usuario_id = ? 
      AND o.estado_oferta = 'pendiente'
      AND (o.deadline IS NULL OR o.deadline >= NOW())
      AND NOT EXISTS (
          SELECT 1 
          FROM seleccionados_oferta so 
          WHERE so.porte_id = p.id 
            AND so.fecha_seleccion > o.fecha_oferta
      )";
$count_recibidos = contarPortes($conn, $sql_count_recibidos, $usuario_id);

// 2. Código para contar Portes Asignados a Mí (sin tren)
$sql_count_asignados = "
    SELECT COUNT(*) AS total
    FROM portes p
    JOIN seleccionados_oferta so ON p.id = so.porte_id
    WHERE so.usuario_id = ? 
        AND NOT EXISTS(
            SELECT 1
            FROM seleccionados_oferta so_inner
            WHERE so_inner.porte_id = p.id 
                AND so_inner.fecha_seleccion > so.fecha_seleccion
        ) AND NOT EXISTS (
          SELECT 1 
          FROM porte_tren pt 
          WHERE pt.porte_id = p.id
      )
";
$count_asignados = contarPortes($conn, $sql_count_asignados, $usuario_id);
//echo "<br>";
// 3. Código para contar Reofrecidos Sin Asignar
$sql_count_reofrecidos = "
    SELECT COUNT(*) AS total
    FROM portes p
    JOIN seleccionados_oferta so ON p.id = so.porte_id
    JOIN ofertas_varios o ON p.id = o.porte_id
    WHERE so.usuario_id = ? 
      AND o.ofertante_id = ?
      AND NOT EXISTS (
          SELECT 1 
          FROM seleccionados_oferta so_inner 
          WHERE so_inner.porte_id = p.id 
            AND so_inner.fecha_seleccion > so.fecha_seleccion
      )
      AND NOT EXISTS (
          SELECT 1 
          FROM porte_tren pt 
          WHERE pt.porte_id = p.id
      )
      AND o.estado_oferta = 'pendiente'
      AND (o.deadline IS NULL OR o.deadline >= NOW())
";
//echo "<br>$usuario_id, $usuario_id<br>";
$count_reofrecidos = contarPortes($conn, $sql_count_reofrecidos, $usuario_id, $usuario_id);

// 4. Código para contar Portes Reasignados o Enviados a Camiones (Últimos 15 días)
$sql_count_ultimos_portes = "
    SELECT COUNT(DISTINCT p.id) AS total
    FROM portes p
    LEFT JOIN (
        SELECT 
            so.porte_id,
            MAX(so.fecha_seleccion) AS ultima_fecha_seleccion
        FROM seleccionados_oferta so
        GROUP BY so.porte_id
    ) AS so_max ON p.id = so_max.porte_id
    LEFT JOIN porte_tren pt ON p.id = pt.porte_id
    WHERE p.usuario_creador_id NOT IN (
            SELECT id FROM usuarios
            WHERE admin_id = (
                SELECT admin_id FROM usuarios WHERE id = ?
            )
        )
        AND (
            (so_max.ultima_fecha_seleccion >= NOW() - INTERVAL 15 DAY)
            OR (pt.inicio_tren >= NOW() - INTERVAL 15 DAY)
        )
";

// Ejecutar la consulta
$count_ultimos_portes = contarPortes($conn, $sql_count_ultimos_portes, $usuario_id);


?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InterTrucker - Inter</title>
    <link rel="stylesheet" href="styles.css">
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const collapsibles = document.querySelectorAll('.collapsible');
            collapsibles.forEach(button => {
                button.addEventListener('click', function() {
                    // Cerrar todas las secciones abiertas excepto la actual
                    collapsibles.forEach(btn => {
                        if (btn !== this) {
                            btn.classList.remove('active');
                            btn.nextElementSibling.style.display = 'none';
                        }
                    });
                    
                    // Alternar el estado de la sección actual
                    this.classList.toggle('active');
                    const content = this.nextElementSibling;
                    if (content.style.display === "block") {
                        content.style.display = "none";
                    } else {
                        content.style.display = "block";
                    }
                });
            });
        });
    </script>
<link rel='stylesheet' href='/header.css'>
<script src='/header.js'></script>
</head>
<body>
<?php require_once $_SERVER["DOCUMENT_ROOT"]."/header.php"; ?>
    <!-- Incluir el menú de navegación -->

    <main>
        <h1>PORTES NUEVOS</h1>
                <nav>
            <ul style="list-style-type: none; padding: 0; margin: 0; display: flex; gap: 20px; justify-content: center;">
                <li>
                    <a href="portes_nuevos_recibidos.php" style="display: block; padding: 15px 20px; text-align: center; text-decoration: none; background-color: #28a745; color: white; border-radius: 5px; font-weight: bold; font-size: 1.2em;">Recibidos</a>
                </li>
                <li>
                    <a href="portes_nuevos_propios.php" style="display: block; padding: 15px 20px; text-align: center; text-decoration: none; background-color: #007bff; color: white; border-radius: 5px; font-weight: bold; font-size: 1.2em;">Creados</a>
                </li>
            </ul>
        </nav>
        <h1>PORTES NUEVOS RECIBIDOS</h1>
         <nav>
            <ul style="list-style-type: none; padding: 0; margin: 0; display: flex; gap: 20px; justify-content: center;">
                <li>
                    <a href="portes_nuevos_recibidos.php" style="display: block; padding: 15px 20px; text-align: center; text-decoration: none; background-color: #28a745; color: white; border-radius: 5px; font-weight: bold; font-size: 1.2em;">Mios</a>
                </li>
                <li>
                    <a href="portes_nuevos_recibidos_todos.php" style="display: block; padding: 15px 20px; text-align: center; text-decoration: none; background-color: #007bff; color: white; border-radius: 5px; font-weight: bold; font-size: 1.2em;">Todos</a>
                </li>
            </ul>
        </nav> 
<button class="collapsible">Nuevas Ofertas Recibidas (<?php echo htmlspecialchars($count_recibidos); ?>)</button>
<div class="content">
    <ul>
        <?php
        // Consultar ofertas recibidas que no han sido seleccionadas por otros (pendientes y no caducadas)
        $sql_recibidos = "
            SELECT p.id AS porte_id, o.id AS oferta_id, p.mercancia_descripcion, p.fecha_recogida, 
                   p.localizacion_recogida, p.localizacion_entrega, o.estado_oferta, u.nombre_usuario AS nombre_ofertante
            FROM portes p
            JOIN ofertas_varios o ON p.id = o.porte_id
            JOIN usuarios u ON o.ofertante_id = u.id
            WHERE o.usuario_id = ? 
              AND o.estado_oferta = 'pendiente'
              AND (o.deadline IS NULL OR o.deadline >= NOW())
              AND NOT EXISTS (
                  SELECT 1 
                  FROM seleccionados_oferta so 
                  WHERE so.porte_id = p.id 
                    AND so.fecha_seleccion > o.fecha_oferta
              )";

        // Preparar la consulta
        if (!$stmt_recibidos = $conn->prepare($sql_recibidos)) {
            die("Error en la consulta SQL: " . htmlspecialchars($conn->error));
        }

        // Asignar parámetro y ejecutar
        $stmt_recibidos->bind_param("i", $usuario_id);
        $stmt_recibidos->execute();
        $result_recibidos = $stmt_recibidos->get_result();

        if ($result_recibidos->num_rows > 0) {
            while ($row = $result_recibidos->fetch_assoc()) {
                echo "<li>
                        Mercancía: " . htmlspecialchars($row['mercancia_descripcion']) . "<br>
                        Fecha de recogida: " . htmlspecialchars($row['fecha_recogida']) . "<br>
                        Lugar de recogida: " . htmlspecialchars($row['localizacion_recogida']) . "<br>
                        Lugar de entrega: " . htmlspecialchars($row['localizacion_entrega']) . "<br>
                        <strong>Ofrecido por:</strong> " . htmlspecialchars($row['nombre_ofertante']) . "<br>
                        Estado de la oferta: " . htmlspecialchars($row['estado_oferta']) . "<br>
                        <a href='detalle_porte.php?id=" . urlencode($row['porte_id']) . "'>Ver Detalles</a>
                        <form method='post' action='aceptar_oferta.php' style='display:inline;'>
                            <input type='hidden' name='oferta_id' value='" . htmlspecialchars($row['oferta_id']) . "'>
                            <input type='hidden' name='porte_id' value='" . htmlspecialchars($row['porte_id']) . "'>
                            <label for='duracion_validez_" . htmlspecialchars($row['oferta_id']) . "'>Duración de validez (horas):</label>
                            <input type='number' name='duracion_validez' id='duracion_validez_" . htmlspecialchars($row['oferta_id']) . "' min='1' required>
                            <button type='submit' name='accion' value='aceptar'>Aceptar</button>
                            <button type='submit' name='accion' value='rechazar' onclick='return confirm(\"¿Estás seguro de que deseas rechazar esta oferta?\");'>Rechazar</button>
                        </form>
                      </li>";
            }
        } else {
            echo "<li>No hay portes recibidos.</li>";
        }

        // Cerrar la consulta
        $stmt_recibidos->close();
        ?>
    </ul>
</div>


<button class="collapsible">Portes Asignados a Mí (<?php echo htmlspecialchars($count_asignados); ?>)</button>
<div class="content">
    <ul>
        <?php
        // Consultar portes asignados al usuario de sesión que aún no tienen tren asignado
        $sql_asignados = "
            SELECT 
                p.id AS porte_id, 
                p.mercancia_descripcion, 
                p.fecha_recogida, 
                p.localizacion_recogida, 
                p.localizacion_entrega
            FROM 
                seleccionados_oferta so
            JOIN 
                portes p ON so.porte_id = p.id
            WHERE 
                so.usuario_id = ?                              -- El usuario de sesión tiene el porte asignado
                AND NOT EXISTS(                             -- El usuario no puede ser ofertante y haberla creado despues   
                    SELECT 1
                    FROM seleccionados_oferta so_inner
                    WHERE so_inner.porte_id = p.id 
                        AND so_inner.fecha_seleccion > so.fecha_seleccion
                )
                AND NOT EXISTS (                                -- El porte no tiene tren asignado
                    SELECT 1 
                    FROM porte_tren pt 
                    WHERE pt.porte_id = p.id
                )
        ";

        // Preparar y ejecutar la consulta
        if ($stmt_asignados = $conn->prepare($sql_asignados)) {
            // Asignar el parámetro (usuario actual)
            $stmt_asignados->bind_param("i", $usuario_id);
            $stmt_asignados->execute();
            $result_asignados = $stmt_asignados->get_result();

            // Mostrar resultados si los hay
            if ($result_asignados->num_rows > 0) {
                while ($row = $result_asignados->fetch_assoc()) {
                    echo "<li>
                            <strong>Mercancía:</strong> " . htmlspecialchars($row['mercancia_descripcion']) . "<br>
                            <strong>Fecha de recogida:</strong> " . htmlspecialchars($row['fecha_recogida']) . "<br>
                            <strong>Lugar de recogida:</strong> " . htmlspecialchars($row['localizacion_recogida']) . "<br>
                            <strong>Lugar de entrega:</strong> " . htmlspecialchars($row['localizacion_entrega']) . "<br>
                            <a href='detalle_porte.php?id=" . urlencode($row['porte_id']) . "'>Ver Detalles</a>
                            
                            <!-- Formulario para ofrecer el porte -->
                            <form method='post' action='hacer_oferta.php' style='display:inline;'>
                                <input type='hidden' name='porte_id' value='" . htmlspecialchars($row['porte_id']) . "'>
                                <button type='submit'>Ofrecer</button>
                            </form>

                            <!-- Formulario para hacer el porte -->
                            <form method='get' action='hacer_porte.php' style='display:inline;'>
                                <input type='hidden' name='porte_id' value='" . htmlspecialchars($row['porte_id']) . "'>
                                <button type='submit'>Hacer</button>
                            </form>
                          </li>";
                }
            } else {
                echo "<li>No tienes portes asignados sin ofrecer.</li>";
            }

            // Cerrar la consulta
            $stmt_asignados->close();
        } else {
            // Mostrar error en caso de que la consulta no se prepare correctamente
            echo "Error en la consulta SQL: " . htmlspecialchars($conn->error);
        }
        ?>
    </ul>
</div>


<button class="collapsible">Reofrecidos Sin Asignar (<?php echo htmlspecialchars($count_reofrecidos); ?>)</button>
<div class="content">
    <ul>
        <?php
        // Consulta de portes asignados al usuario que ha reofrecido sin un nuevo responsable seleccionado
        $sql_reofrecidos = "
            SELECT 
                p.id AS porte_id, 
                p.mercancia_descripcion, 
                p.fecha_recogida, 
                p.localizacion_recogida, 
                p.localizacion_entrega, 
                u.nombre_usuario AS nombre_ofertante
            FROM 
                portes p
            JOIN 
                seleccionados_oferta so ON p.id = so.porte_id
            JOIN 
                ofertas_varios o ON p.id = o.porte_id
            JOIN 
                usuarios u ON o.ofertante_id = u.id
            WHERE 
                so.usuario_id = ?                      -- El usuario actual es el último seleccionado
                AND o.ofertante_id = ?                  -- El usuario actual es el ofertante
                AND NOT EXISTS (                        -- No debe haber otro seleccionados_oferta más reciente
                    SELECT 1 
                    FROM seleccionados_oferta so_inner 
                    WHERE so_inner.porte_id = p.id 
                      AND so_inner.fecha_seleccion > so.fecha_seleccion
                )
                AND NOT EXISTS (                        -- No debe haber un tren asignado
                    SELECT 1 
                    FROM porte_tren pt 
                    WHERE pt.porte_id = p.id
                )
                AND o.estado_oferta = 'pendiente'       -- La oferta está en estado pendiente
                AND (o.deadline IS NULL OR o.deadline > NOW()); -- La oferta aún no ha caducado
        ";

        // Preparar y ejecutar la consulta
        if ($stmt_reofrecidos = $conn->prepare($sql_reofrecidos)) {
            // Asignar los parámetros (usuario actual en dos lugares: último seleccionado y ofertante)
            $stmt_reofrecidos->bind_param("ii", $usuario_id, $usuario_id);
            $stmt_reofrecidos->execute();
            $result_reofrecidos = $stmt_reofrecidos->get_result();

            // Mostrar resultados si los hay
            if ($result_reofrecidos->num_rows > 0) {
                while ($row = $result_reofrecidos->fetch_assoc()) {
                    echo "<li>
                            <strong>Mercancía:</strong> " . htmlspecialchars($row['mercancia_descripcion']) . "<br>
                            <strong>Fecha de recogida:</strong> " . htmlspecialchars($row['fecha_recogida']) . "<br>
                            <strong>Lugar de recogida:</strong> " . htmlspecialchars($row['localizacion_recogida']) . "<br>
                            <strong>Lugar de entrega:</strong> " . htmlspecialchars($row['localizacion_entrega']) . "<br>
                            <strong>Ofrecido por:</strong> " . htmlspecialchars($row['nombre_ofertante']) . "<br>
                            <a href='detalle_porte.php?id=" . urlencode($row['porte_id']) . "'>Ver Detalles</a>
                            
                            <!-- Botón para ofrecer el porte nuevamente -->
                            <form method='post' action='hacer_oferta.php' style='display:inline;'>
                                <input type='hidden' name='porte_id' value='" . htmlspecialchars($row['porte_id']) . "'>
                                <button type='submit'>Ofrecer</button>
                            </form>

                            <!-- Botón para realizar el porte -->
                            <form method='get' action='hacer_porte.php' style='display:inline;'>
                                <input type='hidden' name='porte_id' value='" . htmlspecialchars($row['porte_id']) . "'>
                                <button type='submit'>Hacer</button>
                            </form>
                          </li>";
                }
            } else {
                echo "<li>No tienes portes reofrecidos sin un nuevo responsable.</li>";
            }

            // Cerrar la consulta
            $stmt_reofrecidos->close();
        } else {
            // Mostrar error en caso de que la consulta no se prepare correctamente
            echo "Error en la consulta SQL: " . htmlspecialchars($conn->error);
        }
        ?>
    </ul>
</div>
<button class="collapsible">Portes Reasignados o Enviados a Camiones (Últimos 15 Días) (<?php echo htmlspecialchars($count_ultimos_portes); ?>)</button>
<div class="content">
    <ul>
        <?php
// Consultar portes reasignados o enviados a camiones en los últimos 15 días
$sql_ultimos_portes = "
    SELECT 
        p.id AS porte_id,
        p.mercancia_descripcion,
        p.fecha_recogida,
        p.localizacion_recogida,
        p.localizacion_entrega,
        so.fecha_seleccion,
        pt.inicio_tren
    FROM 
        portes p
    LEFT JOIN 
        seleccionados_oferta so ON p.id = so.porte_id
    LEFT JOIN 
        porte_tren pt ON p.id = pt.porte_id
    WHERE 
        p.usuario_creador_id NOT IN (
            SELECT id FROM usuarios
            WHERE admin_id = (
                SELECT admin_id FROM usuarios WHERE id = ?
            )
        )
        AND (
            (so.usuario_id = ? AND so.ofertante_id = ?)
            OR
            (so.usuario_id = ? AND pt.tren_id IS NOT NULL)
        )
        AND (so.fecha_seleccion >= NOW() - INTERVAL 15 DAY OR pt.inicio_tren >= NOW() - INTERVAL 15 DAY)
    GROUP BY 
        p.id
";

// Preparar y ejecutar la consulta
if ($stmt_ultimos_portes = $conn->prepare($sql_ultimos_portes)) {
    // Asignar parámetros
    $stmt_ultimos_portes->bind_param('iiii', $usuario_sesion, $usuario_sesion, $usuario_sesion, $usuario_sesion);

    // Ejecutar la consulta
    $stmt_ultimos_portes->execute();
    $result_ultimos_portes = $stmt_ultimos_portes->get_result();

    // Mostrar resultados si los hay
    if ($result_ultimos_portes->num_rows > 0) {
        while ($row = $result_ultimos_portes->fetch_assoc()) {
            echo "<li>
                    <strong>Mercancía:</strong> " . htmlspecialchars($row['mercancia_descripcion']) . "<br>
                    <strong>Fecha de recogida:</strong> " . htmlspecialchars($row['fecha_recogida']) . "<br>
                    <strong>Lugar de recogida:</strong> " . htmlspecialchars($row['localizacion_recogida']) . "<br>
                    <strong>Lugar de entrega:</strong> " . htmlspecialchars($row['localizacion_entrega']) . "<br>";

            // Mostrar la fecha de selección si existe
            if (!is_null($row['fecha_seleccion'])) {
                echo "<strong>Fecha de Reasignación:</strong> " . htmlspecialchars($row['fecha_seleccion']) . "<br>";
            }

            // Mostrar la fecha de asignación a un tren si existe
            if (!is_null($row['inicio_tren'])) {
                echo "<strong>Asignado a Tren desde:</strong> " . htmlspecialchars($row['inicio_tren']) . "<br>";
            }

            echo "<a href='detalle_porte.php?id=" . urlencode($row['porte_id']) . "'>Ver Detalles</a>
                  </li>";
        }
    } else {
        echo "<li>No hay portes reasignados o enviados a camiones en los últimos 15 días.</li>";
    }

    // Cerrar la consulta
    $stmt_ultimos_portes->close();
} else {
    // Mostrar error en caso de que la consulta no se prepare correctamente
    echo "Error en la consulta SQL: " . htmlspecialchars($conn->error);
}
?>

    </ul>
</div>

    </main>

    <!-- Incluir el pie de página -->
    <?php include 'footer.php'; ?>
</body>
</html>
