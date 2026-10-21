<?php
/**
 * Colegio8 — Funciones auxiliares (escape, URL, flash, CSRF, CSV, fechas).
 */

declare(strict_types=1);

/** Escapa texto para HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Devuelve la URL base de la aplicación (dominio + /public). */
function url_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (defined('BASE_URL') && BASE_URL !== '') {
        $base = rtrim(BASE_URL, '/');
        return $base;
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $pos    = strpos($script, '/public/');
    $base   = ($pos !== false) ? substr($script, 0, $pos + 7) : '/';
    $base   = rtrim($base, '/');
    return $base;
}

/** URL absoluta a una ruta bajo /public, p. ej. url('secretaria/inicio.php'). */
function url(string $ruta = ''): string
{
    return url_base() . '/' . ltrim($ruta, '/');
}

/**
 * URL del logo/escudo institucional.
 *
 * El personal puede reemplazar la imagen del colegio copiando su archivo
 * como `public/assets/img/logo.png` (ver README); aquí se usa esa si existe
 * y se vuelve al escudo por defecto (SVG) en caso contrario. Así el cambio
 * se refleja en todas las pantallas sin tocar código.
 */
function url_logo(): string
{
    $alternativo = __DIR__ . '/../public/assets/img/logo.png';
    return url(is_file($alternativo) ? 'assets/img/logo.png' : 'assets/img/escudo.svg');
}

/** Sitio oficial del colegio (configuración del sistema, editable). */
function sitio_oficial_url(): string
{
    return trim((string) (valor_config('sitio_oficial') ?: ''));
}

/** Redirige (PRG: Post/Redirect/Get) y corta la ejecución. */
function redirect(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

/* ------------------------------------------------------------------ */
/* Mensajes flash (una sola muestra)                                  */
/* ------------------------------------------------------------------ */

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'texto' => $mensaje];
}

function tomar_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ------------------------------------------------------------------ */
/* CSRF                                                               */
/* ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Devuelve false y responde 400 si el token no coincide. */
function verificar_csrf(): bool
{
    $recibido = $_POST['csrf'] ?? '';
    $valido   = is_string($recibido) && is_string($_SESSION['csrf'] ?? null)
        && hash_equals($_SESSION['csrf'], $recibido);
    if (!$valido) {
        http_response_code(400);
    }
    return $valido;
}

/* ------------------------------------------------------------------ */
/* Fechas                                                             */
/* ------------------------------------------------------------------ */

function fecha_es(?string $fecha, bool $conHora = false): string
{
    if (!$fecha) {
        return '—';
    }
    $ts = strtotime($fecha);
    $formato = $conHora ? 'd/m/Y H:i' : 'd/m/Y';
    return $ts ? date($formato, $ts) : '—';
}

function hoy_iso(): string
{
    return date('Y-m-d');
}

/* ------------------------------------------------------------------ */
/* Valores de configuración del sistema (tabla configuracion)         */
/* ------------------------------------------------------------------ */

function valor_config(string $clave, ?string $defecto = null): ?string
{
    static $cache = [];
    if (!array_key_exists($clave, $cache)) {
        try {
            $st = db()->prepare('SELECT valor FROM configuracion WHERE clave = ?');
            $st->execute([$clave]);
            $cache[$clave] = $st->fetchColumn() ?: $defecto;
        } catch (Throwable) {
            $cache[$clave] = $defecto; // esquema aún no cargado
        }
    }
    return $cache[$clave];
}

/** Límite de tamaño de archivos subidos, en bytes, desde configuración. */
function limite_archivo_bytes(): int
{
    $mb = (int) (valor_config('limite_archivo_mb') ?: (string) LIMITE_ARCHIVO_MB);
    return $mb * 1024 * 1024;
}

/* ------------------------------------------------------------------ */
/* Documentación de inscripción (los 4 archivos de DNI)               */
/* ------------------------------------------------------------------ */

/** Los 4 tipos de documento de DNI exigidos en la solicitud. */
function tipos_dni_documentos(): array
{
    return ['dni_alumno_frente', 'dni_alumno_dorso', 'dni_tutor_frente', 'dni_tutor_dorso'];
}

/** Etiqueta legible de un tipo de documento. */
function doc_etiqueta_tipo(string $tipo): string
{
    $mapa = [
        'dni_alumno_frente' => 'DNI del alumno — frente',
        'dni_alumno_dorso'  => 'DNI del alumno — dorso',
        'dni_tutor_frente'  => 'DNI del tutor — frente',
        'dni_tutor_dorso'   => 'DNI del tutor — dorso',
        'otro'              => 'Otro documento',
    ];
    return $mapa[$tipo] ?? $tipo;
}

/** Indica si un documento (por su nombre) es una imagen que conviene
 *  presentar como <img> (contenido completo, sin zoom manual). */
function es_img_documento(string $nombreOriginal): bool
{
    return in_array(strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true);
}

/* ------------------------------------------------------------------ */
/* Portadas de libros (contenido completo, sin recortes)              */
/* ------------------------------------------------------------------ */

/**
 * Contenido interno de una portada: la <img> si el libro tiene portada,
 * o un ícono de libro genérico (paleta verde/blanco) si no la tiene.
 * El contenedor (.libro-portada) impone la proporción; la imagen se
 * muestra completa con object-fit: contain sobre fondo neutro.
 */
function portada_libro_contenido(array $libro): string
{
    $id  = (int) ($libro['id'] ?? 0);
    $tit = (string) ($libro['titulo'] ?? '');
    if (!empty($libro['portada']) && $id > 0) {
        return '<img src="' . e(url('libro-portada.php?id=' . $id)) . '" alt="Portada de ' . e($tit) . '">';
    }
    return '<svg class="portada-vacia" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
}

/* ------------------------------------------------------------------ */
/* CSV exportable (abre en Excel/Sheets con BOM UTF-8, separador ;)   */
/* ------------------------------------------------------------------ */

function descargar_csv(string $nombreArchivo, array $columnas, array $filas): never
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nombreArchivo) . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
    fputcsv($out, array_keys($columnas), ';');
    foreach ($filas as $fila) {
        $vals = [];
        foreach ($columnas as $campo) {
            $vals[] = $fila[$campo] ?? '';
        }
        fputcsv($out, $vals, ';');
    }
    fclose($out);
    exit;
}

/* ------------------------------------------------------------------ */
/* Cabecera de impresión (reportes / documentación de secretaría)     */
/* ------------------------------------------------------------------ */

function render_cabecera_impresion(string $tipo, array $tiposInfo, string $modulo = 'Inscripciones'): void
{
    $titulo = $tiposInfo[$tipo][0] ?? 'Reporte';
    echo '<div class="cabecera-impresion">';
    echo '<img class="escudo" src="' . e(url_logo()) . '" alt="Escudo de Colegio8" width="44" height="44">';
    echo '<div><strong>Colegio8</strong><br><span>' . e($modulo) . ' · ' . e($titulo) . '</span></div>';
    echo '</div>';
}

/* ------------------------------------------------------------------ */
/* Paginación simple                                                  */
/* ------------------------------------------------------------------ */

function html_paginador(string $ruta, int $pag, int $totalPaginas): string
{
    if ($totalPaginas <= 1) {
        return '';
    }
    $sep = strpos($ruta, '?') !== false ? '&' : '?';
    $h   = '<nav class="paginador" aria-label="Paginación">';
    $h  .= '<a class="btn btn-fantasma" ' . ($pag > 1 ? 'href="' . e(url($ruta . $sep . 'pagina=' . ($pag - 1))) . '"' : 'aria-disabled="true" tabindex="-1"') . '>‹ Anterior</a>';
    $h  .= '<span class="paginador-info">Página ' . $pag . ' de ' . $totalPaginas . '</span>';
    $h  .= '<a class="btn btn-fantasma" ' . ($pag < $totalPaginas ? 'href="' . e(url($ruta . $sep . 'pagina=' . ($pag + 1))) . '"' : 'aria-disabled="true" tabindex="-1"') . '>Siguiente ›</a>';
    $h  .= '</nav>';
    return $h;
}