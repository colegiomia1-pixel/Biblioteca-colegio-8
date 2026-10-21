<?php
/**
 * Colegio8 — Subida de archivos (documentación de inscripciones,
 * portadas de libros).
 *
 * Reglas aplicadas en el servidor:
 *  - Tipos permitidos: imágenes (jpeg/png) y PDF.
 *  - Tamaño máximo configurable (por defecto 5 MB).
 *  - Se guardan en el servidor con nombre único (no se confía en el
 *    nombre original) para evitar colisiones o sobrescritura.
 *  - El nombre original se conserva en la base de datos.
 */

declare(strict_types=1);

const TIPOS_PERMITIDOS = [
    'extension' => ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'],
    'mime'      => ['image/jpeg', 'image/png', 'application/pdf'],
];

/**
 * Valida y guarda $_FILES[$campo].
 *
 * @param string        $campo   Nombre del campo en $_FILES.
 * @param string        $subdir  Subcarpeta bajo /public/uploads (p. ej. 'docs').
 * @param array         $errores Array al que se agregan errores (por referencia).
 * @param int|null      $maxBytes Tamaño máximo (default: configurado).
 * @return string|null  Ruta relativa guardada (uploads/...), o null si falló.
 */
function subir_archivo(string $campo, string $subdir, array &$errores, ?int $maxBytes = null, ?array $soloExtensiones = null): ?string
{
    if (empty($_FILES[$campo]['name'])) {
        $errores[] = 'Debe adjuntar un archivo.';
        return null;
    }
    $archivo = $_FILES[$campo];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Error al subir el archivo (código ' . $archivo['error'] . ').';
        return null;
    }

    $maxBytes = $maxBytes ?? limite_archivo_bytes();
    if ($archivo['size'] > $maxBytes) {
        $errores[] = 'El archivo supera el tamaño máximo de ' . intdiv($maxBytes, 1024 * 1024) . ' MB.';
        return null;
    }
    if ($archivo['size'] <= 0) {
        $errores[] = 'El archivo está vacío.';
        return null;
    }

    $nombreOrig = (string) $archivo['name'];
    $ext        = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));

    // Validación por extensión y por contenido real (mime).
    if (!array_key_exists($ext, TIPOS_PERMITIDOS['extension'])) {
        $errores[] = 'Tipo de archivo no permitido. Solamente JPG, PNG o PDF.';
        return null;
    }
    if ($soloExtensiones !== null && !in_array($ext, $soloExtensiones, true)) {
        $errores[] = 'Para este campo solo se permiten imágenes (JPG o PNG).';
        return null;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, (string) $archivo['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if ($mime !== TIPOS_PERMITIDOS['extension'][$ext] && !in_array($mime, TIPOS_PERMITIDOS['mime'], true)) {
        $errores[] = 'El contenido del archivo no corresponde a una imagen o PDF válido.';
        return null;
    }

    // Nombre único en el servidor.
    $dirBase = dirname(__DIR__) . '/public/uploads/' . trim($subdir, '/');
    if (!is_dir($dirBase)) {
        @mkdir($dirBase, 0755, true);
    }
    $nombreUnico = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destino     = $dirBase . '/' . $nombreUnico;

    if (!move_uploaded_file((string) $archivo['tmp_name'], $destino)) {
        $errores[] = 'No se pudo guardar el archivo en el servidor.';
        return null;
    }

    return 'uploads/' . trim($subdir, '/') . '/' . $nombreUnico;
}

/** Elimina un archivo del servidor si existe y está dentro de uploads/. */
function borrar_archivo(?string $ruta): void
{
    if (!$ruta) {
        return;
    }
    $ruta = str_replace('\\', '/', $ruta);
    if (strpos($ruta, 'uploads/') !== 0) {
        return; // nunca salir de uploads/
    }
    $abs = dirname(__DIR__) . '/public/' . $ruta;
    if (is_file($abs)) {
        @unlink($abs);
    }
}