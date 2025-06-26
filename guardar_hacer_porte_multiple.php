<?php
session_start();
include 'conexion.php'; // Ajusta si la ruta difiere

// ======================================================
// CSRF: Verificar token
// ======================================================
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) 
    || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error: Token CSRF ausente o inválido.");
}

// Mostrar errores (para depuración; quítalo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Comprobar si el usuario está en sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: usuario no autenticado.");
}
$usuario_id = $_SESSION['usuario_id'];

// Verificar que llegan porte_id[] (el array de portes)
if (!isset($_POST['porte_id']) || !is_array($_POST['porte_id'])) {
    die("No se recibieron portes para procesar (porte_id[]).");
}
$portes = $_POST['porte_id'];

// Observar si se seleccionó un tándem existente (tren+camionero ya activo) 
// o se van a crear un tren nuevo
$tandem_existente   = $_POST['tandem_existente']   ?? ''; // algo como "tren_id|camionero_id"
$vehiculo_existente = $_POST['vehiculo_existente'] ?? '';
$semi_remolque_id   = $_POST['semi_remolque_existente'] ?? '';
$remolque_id        = $_POST['remolque_existente'] ?? '';
$camionero_id       = $_POST['camionero_existente'] ?? '';

// Lógica principal:
// --------------------------------------
// 1) Si $tandem_existente NO está vacío => interpretamos "usar tren+camionero ya existente".
// 2) Si $tandem_existente está vacío => se crea un tren nuevo con los vehículos + camionero indicados.
// 3) Asignar cada porte a ese tren (o lo que tu lógica requiera).

try {
    $conn->begin_transaction();

    // (1) Verificar si se ha elegido un tándem existente
    if (!empty($tandem_existente)) {
        // parsear "tren_id|camionero_id"
        list($tren_id, $cam_id) = explode('|', $tandem_existente);
        $tren_id = (int)$tren_id;
        $cam_id  = (int)$cam_id;

        // TODO: Podrías verificar que existan en la BD, etc. (opcional)
        // Asignar cada porte a este tren (por ejemplo, en la tabla porte_tren)
        foreach($portes as $pid) {
            $p = (int)$pid;
            // Ejemplo de inserción o update, ajusta a tu diseño
            // "INSERT INTO porte_tren (porte_id, tren_id, inicio_tren) VALUES (?, ?, NOW())"
            //   O update si ya existe
            $sql_insert = "
                INSERT INTO porte_tren (porte_id, tren_id, usuario_id, inicio_tren) 
                VALUES (?, ?, ?, NOW())
            ";
            $stmt_ins = $conn->prepare($sql_insert);
            if (!$stmt_ins) {
                throw new Exception("Error en prepare porte_tren exist: ".$conn->error);
            }
            $stmt_ins->bind_param("iii", $p, $tren_id, $usuario_id);
            $stmt_ins->execute();
            $stmt_ins->close();
        }

        // Podrías dar un mensaje
        echo "<p>Se usó el tándem existente (tren_id=$tren_id) para asignar los portes.</p>";

    } else {
        // (2) Crear un tren nuevo con la info que venga en vehiculo_existente, etc.
        // Solo si $vehiculo_existente y $camionero_id no están vacíos
        if (empty($vehiculo_existente) || empty($camionero_id)) {
            throw new Exception("No se seleccionó ni tándem existente ni vehículo+camionero para tren nuevo.");
        }

        // A) Crear el registro en la tabla 'tren'
        //    Por ejemplo, tren_nombre => concat de la matricula o lo que quieras
        $sql_vehiculo = "SELECT matricula, marca, modelo FROM vehiculos WHERE id = ?";
        $stmt_veh = $conn->prepare($sql_vehiculo);
        if (!$stmt_veh) {
            throw new Exception("Error en prepare SELECT vehiculo: ".$conn->error);
        }
        $stmt_veh->bind_param("i", $vehiculo_existente);
        $stmt_veh->execute();
        $r_veh = $stmt_veh->get_result();
        if ($r_veh->num_rows === 0) {
            throw new Exception("Vehículo principal no encontrado (id=$vehiculo_existente).");
        }
        $vinfo = $r_veh->fetch_assoc();
        $stmt_veh->close();

        // Podrías concatenar la marca, matrícula y/o fecha/hora
        $tren_nombre = "Tren: ".$vinfo['marca']." ".$vinfo['matricula']." ".date("Ymd_His");
        $sql_crear_tren = "INSERT INTO tren (tren_nombre) VALUES (?)";
        $stmt_t = $conn->prepare($sql_crear_tren);
        if (!$stmt_t) {
            throw new Exception("Error en prepare insertar tren: ".$conn->error);
        }
        $stmt_t->bind_param("s", $tren_nombre);
        $stmt_t->execute();
        $nuevo_tren_id = $stmt_t->insert_id;
        $stmt_t->close();

        // B) Insertar relación tren_vehiculos (para el vehículo principal, semirremolque, remolque, etc.)
        //    Por ejemplo:
        $sql_tv = "INSERT INTO tren_vehiculos (tren_id, vehiculo_id, inicio_vehiculo_porte) VALUES (?, ?, NOW())";
        $stmt_tv = $conn->prepare($sql_tv);
        if (!$stmt_tv) {
            throw new Exception("Error en prepare tren_vehiculos: ".$conn->error);
        }
        // (i) Vehículo principal
        $stmt_tv->bind_param("ii", $nuevo_tren_id, $vehiculo_existente);
        $stmt_tv->execute();
        // (ii) semirremolque si no vacío
        if (!empty($semi_remolque_id)) {
            $semi_id = (int)$semi_remolque_id;
            $stmt_tv->bind_param("ii", $nuevo_tren_id, $semi_id);
            $stmt_tv->execute();
        }
        // (iii) remolque si no vacío
        if (!empty($remolque_id)) {
            $rem_id = (int)$remolque_id;
            $stmt_tv->bind_param("ii", $nuevo_tren_id, $rem_id);
            $stmt_tv->execute();
        }
        $stmt_tv->close();

        // C) Asignar un camionero al tren => tren_camionero
        //    fin_tren_camionero => NULL
        $sql_tc = "
          INSERT INTO tren_camionero (tren_id, camionero_id, inicio_tren_camionero) 
          VALUES (?, ?, NOW())
        ";
        $stmt_tc = $conn->prepare($sql_tc);
        if (!$stmt_tc) {
            throw new Exception("Error en prepare tren_camionero: ".$conn->error);
        }
        $cam_id = (int)$camionero_id;
        $stmt_tc->bind_param("ii", $nuevo_tren_id, $cam_id);
        $stmt_tc->execute();
        $stmt_tc->close();

        // D) Insertar cada porte en porte_tren, por ejemplo
        foreach($portes as $pid) {
            $p = (int)$pid;
            $sql_pt = "
                INSERT INTO porte_tren (porte_id, tren_id, usuario_id, inicio_tren) 
                VALUES (?, ?, ?, NOW())
            ";
            $stmt_pt = $conn->prepare($sql_pt);
            if (!$stmt_pt) {
                throw new Exception("Error en prepare porte_tren new: ".$conn->error);
            }
            $stmt_pt->bind_param("iii", $p, $nuevo_tren_id, $usuario_id);
            $stmt_pt->execute();
            $stmt_pt->close();
        }

        echo "<p>Se creó un nuevo tren (ID=$nuevo_tren_id) y se asignaron los portes.</p>";
    }

    // Confirmar transacción
    $conn->commit();

    // Mensaje final
    echo "<p>Procesamiento completado con éxito.</p>";
    echo "<a href='hacer_porte_multiple.php'>Volver</a>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<p style='color:red;'>Error al procesar porte múltiple: " . $e->getMessage() . "</p>";
}

$conn->close();
