<?php
/* ---------------------------------------------------------------
 *  /s14_ctrl/calc_facturas_saas.php
 *  Cálculo de facturas – modo “dry-run” (inserta en la tabla,
 *  pero aún sin PDF ni cobro)
 * --------------------------------------------------------------- */

/* — activa errores solo mientras pruebes — */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../conexion.php';

/* Mes a facturar = mes anterior */
$periodo = new DateTime('2025-12-01');   // ← mes que quieres facturar
$anio = (int)$periodo->format('Y');
$mes  = (int)$periodo->format('n');

/* Administradores (empresas) */
$resEmp = $conn->query("
    SELECT id, fecha_registro AS fecha_alta
    FROM   usuarios
    WHERE  rol = 'administrador'
");
while ($e = $resEmp->fetch_assoc()) {

    $empId   = (int)$e['id'];
    $mesAlta = (int)(new DateTime($e['fecha_alta']))->format('n');

    /* Uso mensual ya generado por e_calc_uso_mensual */
    $uso = $conn->query("
        SELECT usuarios_activos, almacenamiento_mb
        FROM   uso_mensual_empresa
        WHERE  empresa_id = $empId
          AND  anio = $anio
          AND  mes  = $mes
        LIMIT  1
    ")->fetch_assoc() ?: ['usuarios_activos'=>0,'almacenamiento_mb'=>0];

    $usuarios = (int)$uso['usuarios_activos'];
    $gbTotal  = round($uso['almacenamiento_mb'] / 1024, 3);   // MB→GB
    $gbExceso = max(0, $gbTotal - 2);

    /* Mes 1 y 2 gratis (cuota fija) */
    $promo = ($mes - $mesAlta < 2);
    $baseUsuarios = $promo ? 0 : $usuarios * 10;
    $baseMemoria  = $gbExceso * 2;
    $subtotal     = $baseUsuarios + $baseMemoria;
    $ivaPct       = 21.00;
    $iva          = $subtotal * $ivaPct / 100;
    $total        = $subtotal + $iva;

    /* Numeración */
    $serie = 'SAAS-'.$anio;
    $next  = $conn->query("
        SELECT IFNULL(MAX(num_factura),0)+1
        FROM   facturas_saas
        WHERE  serie = '$serie'
    ")->fetch_row()[0];

    $fechaIni = $periodo->format('Y-m-01');
    $fechaFin = $periodo->format('Y-m-t');

    $stmt = $conn->prepare("
        INSERT INTO facturas_saas
        (empresa_id, periodo_ini, periodo_fin,
         serie, num_factura, cuota_gratis,
         users_base, gb_exceso, base_usuarios, base_memoria,
         subtotal, iva_pct, iva, total, estado)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pendiente')
    ");

    /* 14 valores  →  cadena tipos 14 caracteres */
    $stmt->bind_param(
        'isssiiiddddddd',
        $empId,       // i
        $fechaIni,    // s
        $fechaFin,    // s
        $serie,       // s
        $next,        // i
        $promo,       // i
        $usuarios,    // i
        $gbExceso,    // d
        $baseUsuarios,// d
        $baseMemoria, // d
        $subtotal,    // d
        $ivaPct,      // d
        $iva,         // d
        $total        // d
    );
    $stmt->execute();
}

echo "Facturas dry-run generadas para $mes/$anio\n";
