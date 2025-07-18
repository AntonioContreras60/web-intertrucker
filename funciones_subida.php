<?php
function subir_archivo($fileEntry, $carpetaObjetivo, $tipoDocumento = '') {
    $maxSize = 20 * 1024 * 1024; // 20 MB
    $permitidas = ['pdf', 'jpg', 'jpeg', 'png'];
    $mimesPermitidos = [
        'application/pdf',
        'image/jpeg',
        'image/png'
    ];

    if (!isset($fileEntry['error']) || $fileEntry['error'] !== UPLOAD_ERR_OK) {
        return "Error al subir el archivo.";
    }

    if ($fileEntry['size'] > $maxSize) {
        return "El archivo supera el tamaño máximo de 20 MB.";
    }

    $extension = strtolower(pathinfo($fileEntry['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $permitidas)) {
        return "Extensión de archivo no permitida.";
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($fileEntry['tmp_name']);
    if (!in_array($mime, $mimesPermitidos)) {
        return "Tipo MIME no permitido.";
    }

    $carpetaObjetivo = trim($carpetaObjetivo, '/');
    $destDir = __DIR__ . '/' . $carpetaObjetivo;
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0777, true)) {
            return "No se pudo crear la carpeta de destino.";
        }
    }

    $nombreUnico = uniqid() . '.' . $extension;
    $destPath = $destDir . '/' . $nombreUnico;

    if (!move_uploaded_file($fileEntry['tmp_name'], $destPath)) {
        return "Error al mover el archivo.";
    }

    return $carpetaObjetivo . '/' . $nombreUnico;
}
?>
