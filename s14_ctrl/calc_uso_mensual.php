<?php
/* -------------------------------------------------
 *  /s14_ctrl/calc_uso_mensual.php
 *  Guarda el consumo del mes ANTERIOR para todas las empresas
 * ------------------------------------------------- */
require_once __DIR__.'/../conexion.php';   // igual que en los demás scripts

/* Mes a cerrar = mes anterior */
$periodo = new DateTime('first day of last month');
$anio = (int)$periodo->format('Y');
$mes  = (int)$periodo->format('n');

/* Administradores = empresas */
$emp = $conn->query("SELECT id FROM usuarios WHERE rol='administrador'");
while ($e = $emp->fetch_assoc()) {
    $id = (int)$e['id'];

    /* Usuarios activos de la empresa */
    $numUsers = (int)$conn->query("
        SELECT COUNT(*) FROM usuarios
        WHERE admin_id = $id AND estado='activo'
    ")->fetch_row()[0];

    /* Memoria usada (KB) en portes ENTREGADOS ese mes */
    $memKB = (int)$conn->query("
        SELECT COALESCE(SUM(mre.tamano),0)
        FROM   multimedia_recogida_entrega mre
        JOIN   portes   p ON p.id = mre.porte_id
        JOIN   usuarios u ON u.id = p.usuario_creador_id
        WHERE  u.admin_id = $id
          AND  YEAR(p.fecha_entrega)  = $anio
          AND  MONTH(p.fecha_entrega) = $mes
    ")->fetch_row()[0];

    /* Inserta o actualiza la “foto” mensual */
    $stmt = $conn->prepare("
        REPLACE INTO uso_mensual_empresa
            (empresa_id, anio, mes, usuarios_activos, almacenamiento_mb)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('iiiii', $id, $anio, $mes, $numUsers, $memKB);
    $stmt->execute();
}
echo "Uso mensual registrado para $mes/$anio\n";
