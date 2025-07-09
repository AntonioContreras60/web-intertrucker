<?php
/*  Guarda datos del perfil + dirección fiscal
 *  Inserta o actualiza la fila en direcciones según exista fiscal_id.
 */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: iniciar_sesion.php');
    exit();
}

include '../conexion.php';

$usuario_id = (int) $_SESSION['usuario_id'];

/* ---------- DATOS DEL PERFIL ---------- */
$nombre_usuario = trim($_POST['nombre_usuario']);
$apellidos      = trim($_POST['apellidos']);
$email          = trim($_POST['email']);
$telefono       = trim($_POST['telefono']);
$cif            = trim($_POST['cif']);

$stmt = $conn->prepare(
    "UPDATE usuarios
     SET nombre_usuario=?, apellidos=?, email=?, telefono=?, cif=?
     WHERE id=?"
);
$stmt->bind_param("sssssi",
    $nombre_usuario, $apellidos, $email, $telefono, $cif, $usuario_id
);
$stmt->execute();
$stmt->close();

/* ---------- DIRECCIÓN FISCAL ---------- */
$fiscal_id          = (int) $_POST['fiscal_id'];          // 0 si no había fila
$fiscal_nombre_via  = trim($_POST['fiscal_nombre_via']);
$fiscal_numero      = trim($_POST['fiscal_numero']);
$fiscal_complemento = trim($_POST['fiscal_complemento']);
$fiscal_cp          = trim($_POST['fiscal_codigo_postal']);
$fiscal_ciudad      = trim($_POST['fiscal_ciudad']);
$fiscal_pais        = trim($_POST['fiscal_pais']);

if ($fiscal_id > 0) {
    /* --- UPDATE --- */
    $stmt = $conn->prepare(
        "UPDATE direcciones
         SET nombre_via=?, numero=?, complemento=?, codigo_postal=?,
             ciudad=?, pais=?
         WHERE id=? AND usuario_id=?"
    );
    $stmt->bind_param("ssssssii",
        $fiscal_nombre_via, $fiscal_numero, $fiscal_complemento,
        $fiscal_cp, $fiscal_ciudad, $fiscal_pais,
        $fiscal_id, $usuario_id
    );
} else {
    /* --- INSERT --- */
    $stmt = $conn->prepare(
        "INSERT INTO direcciones
         (usuario_id, nombre_via, numero, complemento, codigo_postal,
          ciudad, pais, tipo_direccion)
         VALUES (?,?,?,?,?,?,?,'fiscal')"
    );
    $stmt->bind_param("issssss",
        $usuario_id, $fiscal_nombre_via, $fiscal_numero, $fiscal_complemento,
        $fiscal_cp, $fiscal_ciudad, $fiscal_pais
    );
}
$stmt->execute();
$stmt->close();

$conn->close();

/* ---------- REDIRECCIÓN ---------- */
header('Location: perfil_usuario.php');
exit();
