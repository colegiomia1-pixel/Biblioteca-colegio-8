<?php
/**
 * Colegio8 — Punto de arranque de cada página.
 *
 * Carga configuración, inicia la sesión única con namespaces por rol y
 * expone el renderizador de vistas. Todas las páginas bajo /public
 * comienzan con `require __DIR__ . '/../../app/core.php';`.
 */

declare(strict_types=1);

// --- Configuración -----------------------------------------------------
$archivoConfig = __DIR__ . '/config.php';
if (!is_file($archivoConfig)) {
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Colegio8 — Configuración</title>'
       . '<body style="font-family:sans-serif;background:#0f2e1c;color:#f0fdf4;padding:2rem">'
       . '<h1>Colegio8</h1><p>Falta el archivo de configuración.</p>'
       . '<p>Copia <code>app/config.example.php</code> como <code>app/config.php</code> y ajustá '
       . 'los datos de conexión a la base de datos. Luego importá <code>database/esquema.sql</code>.</p>'
       . '<p>Ver el <code>README.md</code>.</p>'
       . '</body>';
    exit;
}
require $archivoConfig;

date_default_timezone_set('America/Argentina/Buenos_Aires');

// --- Sesión única (namespaces por rol, ver app/auth.php) --------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('COLEGIO8_SESION');
    session_start();
}

require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/uploads.php';
require __DIR__ . '/inscripciones.php';
require __DIR__ . '/biblioteca.php';

// Reconectar sesión persistente ("Recordar mi cuenta") de roles públicos.
// Se omite en logout.php para que el propio logout pueda revocar la sesión
// recordada sin que una rotación previa deje una fila huérfana en la base.
// Solos se restabece si no hay ya una sesión activa de ese rol.
if (basename($_SERVER['SCRIPT_NAME']) !== 'logout.php') {
    recordar_restaurar();
}

/* ------------------------------------------------------------------ */
/* Renderizado de vistas                                              */
/* ------------------------------------------------------------------ */

/**
 * Carga una vista (app/vistas/<vista>.php) dentro del layout activo.
 *
 * @param string $vista Nombre de archivo, relativo a app/vistas/
 * @param array  $datos Variables disponibles en la vista
 * @param string $rolPanel 'postulante'|'lector'|'secretaria'|'bibliotecario'|'publico'
 */
function render(string $vista, array $datos = [], string $rolPanel = 'publico'): void
{
    extract($datos);
    $titulo       = $titulo ?? 'Colegio8';
    $rolPanel     = $rolPanel;
    $contenido    = __DIR__ . '/vistas/' . $vista . '.php';
    if (!is_file($contenido)) {
        http_response_code(500);
        echo "Vista no encontrada: " . e($vista);
        return;
    }
    require __DIR__ . '/vistas/layout.php';
}

/* ------------------------------------------------------------------ */
/* Razones de rechazo habituales para solicitudes (datos de apoyo)     */
/* ------------------------------------------------------------------ */

function motivos_rechazo_sugeridos(): array
{
    return [
        'Documentación incompleta o ilegible.',
        'Los datos del DNI no coinciden con los cargados.',
        'El alumno no cumple con la edad requerida para el año postulado.',
        'Falta información de contacto del tutor.',
        'La documentación adjunta no corresponde.',
    ];
}