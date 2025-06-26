<?php
session_start();
include 'conexion.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$porte_id   = $_POST['porte_id'] ?? null;

if (empty($porte_id)) {
    echo "<p style='color:red;'>Error: No se recibió el ID del porte.</p>";
    exit;
}

// -------------------------------------------------------------------------
// (Igual que antes) obtenerEmailContacto, obtenerDatosCamioneroCamion, etc.
// -------------------------------------------------------------------------
function obtenerEmailContacto($usuario_id, $entidad_id, $conn) {
    if (!empty($usuario_id)) {
        $sql_u = "SELECT email, estado, nombre_usuario, apellidos
                  FROM usuarios
                  WHERE id=? LIMIT 1";
        $stmt_u = $conn->prepare($sql_u);
        if (!$stmt_u) {
            echo "<p style='color:red;'>Error prepare usuarios: " . $conn->error . "</p>";
            return ['email'=>'','registrado'=>false,'nombre'=>''];
        }
        $stmt_u->bind_param("i", $usuario_id);
        $stmt_u->execute();
        $res_u = $stmt_u->get_result();
        if ($row_u = $res_u->fetch_assoc()) {
            $estaActivo = ($row_u['estado'] === 'activo');
            $nombre     = trim($row_u['nombre_usuario'].' '.$row_u['apellidos']);
            return [
                'email'      => $row_u['email'],
                'registrado' => $estaActivo,
                'nombre'     => $nombre
            ];
        }
        return ['email'=>'','registrado'=>false,'nombre'=>''];
    }

    if (!empty($entidad_id)) {
        $sql_e = "SELECT email, registrado, nombre, usuario_id
                  FROM entidades
                  WHERE id=? LIMIT 1";
        $stmt_e = $conn->prepare($sql_e);
        if (!$stmt_e) {
            echo "<p style='color:red;'>Error prepare entidades: " . $conn->error . "</p>";
            return ['email'=>'','registrado'=>false,'nombre'=>''];
        }
        $stmt_e->bind_param("i", $entidad_id);
        $stmt_e->execute();
        $res_e = $stmt_e->get_result();
        if ($row_e = $res_e->fetch_assoc()) {
            $yaRegistrado = ($row_e['registrado'] == 1);
            $nombreEnt    = $row_e['nombre'];

            // Comprobar si la entidad tiene usuario asociado
            if (!empty($row_e['usuario_id'])) {
                $sql_usuEnt = "SELECT estado FROM usuarios WHERE id=? LIMIT 1";
                $stmt_usuEnt= $conn->prepare($sql_usuEnt);
                if ($stmt_usuEnt) {
                    $stmt_usuEnt->bind_param("i", $row_e['usuario_id']);
                    $stmt_usuEnt->execute();
                    $r_usuEnt = $stmt_usuEnt->get_result();
                    if ($ru = $r_usuEnt->fetch_assoc()) {
                        if ($ru['estado'] === 'activo') {
                            $yaRegistrado = true;
                        }
                    }
                    $stmt_usuEnt->close();
                }
            }
            return [
                'email'      => $row_e['email'],
                'registrado' => $yaRegistrado,
                'nombre'     => $nombreEnt
            ];
        }
        return ['email'=>'','registrado'=>false,'nombre'=>''];
    }

    return ['email'=>'','registrado'=>false,'nombre'=>''];
}

function obtenerDatosCamioneroCamion($tren_id, $conn) {
    $sql = "
        SELECT
            u.nombre_usuario,
            u.apellidos,
            u.cif,
            GROUP_CONCAT(CONCAT(v.marca, ' ', v.matricula) SEPARATOR ' + ') AS nombre_camion
        FROM tren_camionero tc
        JOIN camioneros c ON tc.camionero_id = c.id
        JOIN usuarios u   ON c.usuario_id    = u.id
        JOIN tren_vehiculos tv ON tc.tren_id = tv.tren_id
        JOIN vehiculos v      ON tv.vehiculo_id = v.id
        WHERE tc.tren_id = ?
        GROUP BY tc.tren_id
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<p style='color:red;'>Error al preparar en obtenerDatosCamioneroCamion: " . $conn->error . "</p>";
        return ['nombre_usuario'=>'','apellidos'=>'','cif'=>'','nombre_camion'=>''];
    }
    $stmt->bind_param("i", $tren_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($fila = $res->fetch_assoc()) {
        return [
            'nombre_usuario' => $fila['nombre_usuario'],
            'apellidos'      => $fila['apellidos'],
            'cif'            => $fila['cif'],
            'nombre_camion'  => $fila['nombre_camion']
        ];
    }
    return ['nombre_usuario'=>'','apellidos'=>'','cif'=>'','nombre_camion'=>''];
}

function verificarOcrearTren($vehiculos, $conn) {
    if (empty($vehiculos)) return false;
    $vehiculos_str   = implode(',', array_map('intval', $vehiculos));
    $count_vehiculos = count($vehiculos);

    $sql_check = "
        SELECT tren_id
        FROM tren_vehiculos
        WHERE vehiculo_id IN ($vehiculos_str)
        GROUP BY tren_id
        HAVING COUNT(DISTINCT vehiculo_id) = ?
    ";
    $stmt = $conn->prepare($sql_check);
    if (!$stmt) {
        echo "<p style='color:red;'>Error al preparar sql_check: " . $conn->error . "</p>";
        return false;
    }
    $stmt->bind_param("i", $count_vehiculos);
    $stmt->execute();
    $res_check = $stmt->get_result();

    if ($res_check->num_rows > 0) {
        $row = $res_check->fetch_assoc();
        return $row['tren_id'];
    } else {
        // Crear tren nuevo
        $tren_nombre = generarNombreTren($vehiculos, $conn);
        $sql_ins_t   = "INSERT INTO tren (tren_nombre) VALUES (?)";
        $stmt_ins_t  = $conn->prepare($sql_ins_t);
        if (!$stmt_ins_t) {
            echo "<p style='color:red;'>Error al preparar inserción tren: " . $conn->error . "</p>";
            return false;
        }
        $stmt_ins_t->bind_param("s", $tren_nombre);
        if ($stmt_ins_t->execute()) {
            $nuevo_tren_id = $conn->insert_id;
            foreach ($vehiculos as $vid) {
                $sql_i = "INSERT INTO tren_vehiculos (tren_id, vehiculo_id) VALUES (?, ?)";
                $stmt_i = $conn->prepare($sql_i);
                if (!$stmt_i) {
                    echo "<p style='color:red;'>Error al preparar tren_vehiculos: " . $conn->error . "</p>";
                    return false;
                }
                $stmt_i->bind_param("ii", $nuevo_tren_id, $vid);
                if (!$stmt_i->execute()) {
                    echo "<p style='color:red;'>Error al insertar vehiculo_id=$vid: " . $stmt_i->error . "</p>";
                    return false;
                }
            }
            return $nuevo_tren_id;
        } else {
            echo "<p style='color:red;'>Error al ejecutar inserción tren: " . $stmt_ins_t->error . "</p>";
            return false;
        }
    }
}

function generarNombreTren($vehiculos, $conn) {
    $nombres = [];
    foreach ($vehiculos as $vid) {
        $sql = "SELECT matricula, marca FROM vehiculos WHERE id=? LIMIT 1";
        $stmt= $conn->prepare($sql);
        if (!$stmt) {
            echo "<p style='color:red;'>Error al preparar SELECT en generarNombreTren: " . $conn->error . "</p>";
            continue;
        }
        $stmt->bind_param("i", $vid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $nombres[] = $row['marca']." ".$row['matricula'];
        }
    }
    return implode(' - ', $nombres);
}

// -------------------------------------------------------------------------
// PROCESO PRINCIPAL
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tandem_existente = $_POST['tandem_existente'] ?? '';
    $vehiculo_id      = !empty($_POST['vehiculo_existente'])     ? (int) $_POST['vehiculo_existente']     : null;
    $semi_remolque_id = !empty($_POST['semi_remolque_existente']) ? (int) $_POST['semi_remolque_existente']: null;
    $remolques        = !empty($_POST['remolque_existente'])      ? $_POST['remolque_existente']           : [];
    $camionero_id     = !empty($_POST['camionero_existente'])     ? (int) $_POST['camionero_existente']    : null;
    $inicio_tren      = date("Y-m-d H:i:s");

    $final_tren_id = null;

    // CASO 1: TÁNDEM EXISTENTE
    if ($tandem_existente !== '') {
        list($tren_id, $cam_id) = explode('|', $tandem_existente);
        $tren_id = (int) $tren_id;
        $cam_id  = (int) $cam_id;

        $sql_fin_pt = "UPDATE porte_tren
                       SET fin_tren = ?
                       WHERE porte_id = ?
                         AND fin_tren IS NULL";
        $stmt_fin_pt = $conn->prepare($sql_fin_pt);
        if (!$stmt_fin_pt) {
            echo "<p style='color:red;'>Error al preparar la consulta fin_pt: " . $conn->error . "</p>";
            exit;
        }
        $stmt_fin_pt->bind_param("si", $inicio_tren, $porte_id);
        $stmt_fin_pt->execute();

        $sql_ins_pt = "INSERT INTO porte_tren (porte_id, tren_id, inicio_tren, usuario_id)
                       VALUES (?, ?, ?, ?)";
        $stmt_ins_pt = $conn->prepare($sql_ins_pt);
        if (!$stmt_ins_pt) {
            echo "<p style='color:red;'>Error al preparar inserción porte_tren: " . $conn->error . "</p>";
            exit;
        }
        $stmt_ins_pt->bind_param("iisi", $porte_id, $tren_id, $inicio_tren, $usuario_id);
        $stmt_ins_pt->execute();

        $final_tren_id = $tren_id;

    // CASO 2: CREAR TREN
    } else {
        if (!$vehiculo_id || !$camionero_id) {
            echo "<p style='color:red;'>Error: faltan datos (vehiculo_id o camionero_id)</p>";
            header("Location: hacer_porte.php?porte_id=$porte_id&error=datos_incompletos");
            exit();
        }
        $vehiculos_combinados = array_merge(
            [$vehiculo_id],
            [$semi_remolque_id],
            $remolques
        );
        $vehiculos_combinados = array_filter($vehiculos_combinados);
        sort($vehiculos_combinados);

        $tren_id = verificarOcrearTren($vehiculos_combinados, $conn);
        if ($tren_id) {
            $sql_camionero = "
                INSERT INTO tren_camionero (tren_id, camionero_id, inicio_tren_camionero)
                VALUES (?, ?, NOW())
            ";
            $stmt_camionero = $conn->prepare($sql_camionero);
            if (!$stmt_camionero) {
                echo "<p style='color:red;'>Error al preparar tren_camionero: " . $conn->error . "</p>";
                exit();
            }
            $stmt_camionero->bind_param("ii", $tren_id, $camionero_id);
            $stmt_camionero->execute();

            $sql_fin_pt = "UPDATE porte_tren
                           SET fin_tren = ?
                           WHERE porte_id = ?
                             AND fin_tren IS NULL";
            $stmt_fin_pt = $conn->prepare($sql_fin_pt);
            if (!$stmt_fin_pt) {
                echo "<p style='color:red;'>Error al preparar fin_pt: " . $conn->error . "</p>";
                exit();
            }
            $stmt_fin_pt->bind_param("si", $inicio_tren, $porte_id);
            $stmt_fin_pt->execute();

            $sql_ins_pt = "INSERT INTO porte_tren (porte_id, tren_id, inicio_tren, usuario_id)
                           VALUES (?, ?, ?, ?)";
            $stmt_ins_pt = $conn->prepare($sql_ins_pt);
            if (!$stmt_ins_pt) {
                echo "<p style='color:red;'>Error al preparar inserción porte_tren: " . $conn->error . "</p>";
                exit();
            }
            $stmt_ins_pt->bind_param("iisi", $porte_id, $tren_id, $inicio_tren, $usuario_id);
            $stmt_ins_pt->execute();

            $final_tren_id = $tren_id;
        } else {
            echo "<p style='color:red;'>Error al crear/verificar el tren (retornó false).</p>";
            exit();
        }
    }

    // -------------------------------------------------------------------------
    // ENVÍO DE EMAIL AL EXPEDIDOR (SI ES ENTIDAD) USANDO mail()
    // -------------------------------------------------------------------------
    $sql_porte = "
      SELECT
        p.id,
        p.mercancia_descripcion,
        p.fecha_recogida,
        p.fecha_entrega,
        p.localizacion_recogida,
        p.localizacion_entrega,
        p.expedidor_usuario_id,
        p.expedidor_entidad_id
      FROM portes p
      WHERE p.id = ?
      LIMIT 1
    ";
    $stmt_p = $conn->prepare($sql_porte);
    if (!$stmt_p) {
        echo "<p style='color:red;'>Error al preparar SELECT portes: " . $conn->error . "</p>";
        exit();
    }
    $stmt_p->bind_param("i", $porte_id);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result();
    if ($row_p = $res_p->fetch_assoc()) {
        $infoExp = obtenerEmailContacto(
            $row_p['expedidor_usuario_id'],
            $row_p['expedidor_entidad_id'],
            $conn
        );
        $expedidor_email  = $infoExp['email'];
        $expedidor_nombre = $infoExp['nombre'];

        // Solo si es ENTIDAD y hay email
        if (!empty($row_p['expedidor_entidad_id']) && !empty($expedidor_email)) {
            // Obtenemos datos del camionero y camión
            $camData = ['nombre_usuario'=>'','apellidos'=>'','cif'=>'','nombre_camion'=>''];
            if (!empty($final_tren_id)) {
                $camData = obtenerDatosCamioneroCamion($final_tren_id, $conn);
            }

            $camNombreCompleto = trim($camData['nombre_usuario'].' '.$camData['apellidos']);
            $camCif            = $camData['cif'];
            $camNombreCamion   = $camData['nombre_camion'];

            // Preparamos asunto, mensaje y cabeceras para mail()
            $subject = "Porte #$porte_id: Camionero asignado";
            $desc_mercancia = $row_p['mercancia_descripcion'];
            $f_recogida     = $row_p['fecha_recogida'];
            $f_entrega      = $row_p['fecha_entrega'];
            $loc_recogida   = $row_p['localizacion_recogida'];
            $loc_entrega    = $row_p['localizacion_entrega'];

            // Mensaje en HTML
            $mensaje_html  = "<html><body>";
            $mensaje_html .= "<h2>Información del Porte #$porte_id</h2>";
            $mensaje_html .= "<p><strong>Mercancía:</strong> $desc_mercancia</p>";
            $mensaje_html .= "<p><strong>Recogida:</strong> $loc_recogida el día $f_recogida</p>";
            $mensaje_html .= "<p><strong>Entrega:</strong> $loc_entrega el día $f_entrega</p>";
            $mensaje_html .= "<p><strong>Camionero:</strong> $camNombreCompleto (CIF: $camCif)</p>";
            $mensaje_html .= "<p><strong>Camión:</strong> $camNombreCamion</p>";
            $mensaje_html .= "<p>Regístrate para ver tus recogidas y entregas en ";
            $mensaje_html .= "<a href='https://www.intertrucker.net/Perfil/registro.php' target='_blank'>InterTrucker</a>.</p>";
            $mensaje_html .= "</body></html>";
            
            // Cabeceras para enviar HTML
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            // Asegúrate de usar un dominio válido en tu 'From'
            $headers .= "From: info@intertrucker.net\r\n"; 

            // Envío con mail()
            if (mail($expedidor_email, $subject, $mensaje_html, $headers)) {
                echo "<p style='color:blue;'>Correo enviado a la entidad $expedidor_email con datos del camionero y camión.</p>";
            } else {
                echo "<p style='color:red;'>No se pudo enviar el correo con mail().</p>";
            }
        } else {
            echo "<p>DEBUG: No se envía correo (no es entidad o no tiene email).</p>";
        }
    } else {
        echo "<p style='color:red;'>No se encontró el porte ID=$porte_id en la BD.</p>";
    }
    $stmt_p->close();

    // Mensaje final de éxito
    echo "<div style='text-align:center; margin-top:20px;'>
            <h2 style='color:green;'>La asociación del tren al porte se guardó con éxito.</h2>
            <a href='portes_nuevos_recibidos.php'
               style='display:inline-block; margin-top:10px; padding:10px 20px; background-color:#4CAF50; color:#fff; text-decoration:none; border-radius:5px; font-size:16px;'>
               Volver a Inicio
            </a>
          </div>";

} else {
    // Si no se recibe por POST
    header("Location: hacer_porte.php?porte_id=$porte_id");
    exit();
}
?>
