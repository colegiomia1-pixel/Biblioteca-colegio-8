<?php
/**
 * Colegio8 — Autenticación y sesiones separadas por rol.
 *
 * Todos los roles viven en UNA misma sesión PHP pero en espacios
 * (namespaces) independientes: 'postulante', 'lector', 'secretaria' y
 * 'bibliotecario'. Así, si una persona está logueada como postulante en
 * una pestaña y como bibliotecario en otra, las sesiones no se pisan:
 * cada módulo lee/escribe/cierra únicamente su propio namespace.
 *
 * Además, un mismo usuario público (tabla `usuarios`) puede estar
 * logueado a la vez como "postulante" (seguimiento de inscripción) y
 * como "lector" (biblioteca) sin conflictos.
 */

declare(strict_types=1);

const ROLES = ['postulante', 'lector', 'secretaria', 'bibliotecario'];

function sess(?string $rol = null): ?array
{
    $sistema = $_SESSION['sistema'] ?? [];
    if ($rol === null) {
        return $sistema;
    }
    return $sistema[$rol] ?? null;
}

function iniciar_sesion_rol(string $rol, array $datos): void
{
    if (!in_array($rol, ROLES, true)) {
        throw new LogicException('Rol inválido: ' . $rol);
    }
    $_SESSION['sistema'][$rol] = $datos;
}

function cerrar_sesion_rol(string $rol): void
{
    unset($_SESSION['sistema'][$rol]);
    // Si no queda ningún rol activo, regenerar el id para evitar fijación.
    if (empty($_SESSION['sistema'])) {
        session_regenerate_id(true);
    }
    // El flag del código institucional de secretaría no debe sobrevivir.
    if ($rol === 'secretaria') {
        desmarcar_codigo_ok();
    }
}

function tiene_sesion(string $rol): bool
{
    return isset($_SESSION['sistema'][$rol]);
}

/** Redirige a la pantalla de ingreso correspondiente si no hay sesión. */
function requerir_sesion(string $rol): void
{
    if (!tiene_sesion($rol)) {
        $destinos = [
            'postulante'    => 'login.php?modulo=postulante',
            'lector'        => 'login.php?modulo=lector',
            'secretaria'    => 'login.php?modulo=secretaria',
            'bibliotecario' => 'login.php?modulo=bibliotecario',
        ];
        redirect($destinos[$rol]);
    }
}

/* ------------------------------------------------------------------ */
/* Código institucional de secretaría (paso previo al login)          */
/*                                                                     */
/* El flag vive FUERA del namespace de la sesión de secretaría. Si se  */
/* guardara dentro, `tiene_sesion('secretaria')` devolvería true con   */
/* solo el código, lo que saltaría el paso de credenciales (login.php) */
/* y dejaría entrar a las pantallas sin cuenta real.                   */
/* ------------------------------------------------------------------ */

function codigo_secretaria_ok(): bool
{
    return !empty($_SESSION['codigo_secretaria_ok']);
}

function marcar_codigo_ok(): void
{
    $_SESSION['codigo_secretaria_ok'] = true;
}

function desmarcar_codigo_ok(): void
{
    unset($_SESSION['codigo_secretaria_ok']);
}

/* ------------------------------------------------------------------ */
/* Recordar sesión ("Recordar mi cuenta")                              */
/*                                                                     */
/* Solo para los roles públicos (postulante y lector). Personal        */
/* (secretaria/bibliotecario) queda excluido por seguridad.            */
/* Credencial: cookie con "selector:token"; en la BD se guarda solo    */
/* sha256(token). Al usarse el token se rota.                          */
/* ------------------------------------------------------------------ */

const RECORDAR_COOKIE      = 'col8_recordar';
const RECORDAR_DIAS        = 30;
const RECORDAR_ROLES       = ['postulante', 'lector'];

/** Cookie con credencial persistente (selector : token real). */
function recordar_set_cookie(string $selector, string $token): void
{
    setcookie(
        RECORDAR_COOKIE,
        $selector . ':' . $token,
        [
            'expires'  => time() + RECORDAR_DIAS * 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]
    );
}

function recordar_borrar_cookie(): void
{
    setcookie(RECORDAR_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

/**
 * Registra una sesión persistente para un rol (postulante/lector).
 * Se hace en dos partes (INTRODUCIR con selector + un SELECT con el id)
 * para poder rotar/borrar después por selector y por rol/rol_id.
 */
function recordar_registrar(string $rol, int $rolId): void
{
    if (!in_array($rol, RECORDAR_ROLES, true)) {
        return;
    }
    $selector = bin2hex(random_bytes(12));          // 24 chars
    $token    = bin2hex(random_bytes(32));
    db()->prepare(
        'INSERT INTO recordar_sesiones (rol, rol_id, selector, token_hash) VALUES (?, ?, ?, ?)'
    )->execute([$rol, $rolId, $selector, hash('sha256', $token)]);
    recordar_set_cookie($selector, $token);
}

/**
 * Intenta restaurar una sesión desde la cookie "Recordar mi cuenta".
 * Rota el token tras el uso. Devuelve true si se restableció el rol.
 */
function recordar_restaurar(): bool
{
    if (empty($_COOKIE[RECORDAR_COOKIE])) {
        return false;
    }
    $partes = explode(':', (string) $_COOKIE[RECORDAR_COOKIE], 2);
    if (count($partes) !== 2) {
        recordar_borrar_cookie();
        return false;
    }
    [$selector, $token] = $partes;

    $st = db()->prepare('SELECT id, rol, rol_id, token_hash FROM recordar_sesiones WHERE selector = ?');
    $st->execute([$selector]);
    $fila = $st->fetch();
    if (!$fila) {
        recordar_borrar_cookie();
        return false;
    }

    // Rotar el token tras el primer uso útil.
    if (!hash_equals($fila['token_hash'], hash('sha256', $token))) {
        db()->prepare('DELETE FROM recordar_sesiones WHERE id = ?')->execute([(int) $fila['id']]);
        recordar_borrar_cookie();
        return false;
    }

    return recordar_rotar_y_restaurar((int) $fila['id'], $fila['rol'], (int) $fila['rol_id']);
}

/** Rota el token en la BD + cookie y restaura datos del rol. */
function recordar_rotar_y_restaurar(int $filaId, string $rol, int $rolId): bool
{
    if (!in_array($rol, RECORDAR_ROLES, true)) {
        db()->prepare('DELETE FROM recordar_sesiones WHERE id = ?')->execute([$filaId]);
        recordar_borrar_cookie();
        return false;
    }

    $nuevoSelector = bin2hex(random_bytes(12));
    $nuevoToken    = bin2hex(random_bytes(32));
    db()->prepare("UPDATE recordar_sesiones SET selector = ?, token_hash = ? WHERE id = ?")
        ->execute([$nuevoSelector, hash('sha256', $nuevoToken), $filaId]);
    recordar_set_cookie($nuevoSelector, $nuevoToken);

    if ($rol === 'postulante') {
        $st = db()->prepare('SELECT id, usuario, email FROM usuarios WHERE id = ?');
        $st->execute([$rolId]);
        $u = $st->fetch();
        if (!$u) {
            db()->prepare('DELETE FROM recordar_sesiones WHERE id = ?')->execute([$filaId]);
            return false;
        }
        iniciar_sesion_rol('postulante', [
            'id'      => (int) $u['id'],
            'usuario' => $u['usuario'],
            'email'   => $u['email'],
            'nombre'  => $u['usuario'],
        ]);
        return true;
    }

    // lector: rol_id puede referir a usuarios (alumno) o biblioteca_profesores (profesor).
    $stU = db()->prepare('SELECT id, usuario, email FROM usuarios WHERE id = ?');
    $stU->execute([$rolId]);
    $u = $stU->fetch();
    if ($u) {
        $stA = db()->prepare('SELECT id, nombre, apellido FROM alumnos WHERE usuario_id = ?');
        $stA->execute([(int) $u['id']]);
        $alu = $stA->fetch();
        if (!$alu) {
            db()->prepare('DELETE FROM recordar_sesiones WHERE id = ?')->execute([$filaId]);
            return false;
        }
        iniciar_sesion_rol('lector', [
            'id'         => (int) $u['id'],
            'tipo'       => 'alumno',
            'persona_id' => (int) $alu['id'],
            'usuario'    => $u['usuario'],
            'nombre'     => $alu['nombre'] . ' ' . $alu['apellido'],
        ]);
        return true;
    }

    $stP = db()->prepare('SELECT id, nombre, apellido, usuario FROM biblioteca_profesores WHERE id = ? AND activo = 1');
    $stP->execute([$rolId]);
    $p = $stP->fetch();
    if ($p) {
        iniciar_sesion_rol('lector', [
            'id'         => (int) $p['id'],
            'tipo'       => 'profesor',
            'persona_id' => (int) $p['id'],
            'usuario'    => $p['usuario'],
            'nombre'     => $p['nombre'] . ' ' . $p['apellido'],
        ]);
        return true;
    }

    db()->prepare('DELETE FROM recordar_sesiones WHERE id = ?')->execute([$filaId]);
    return false;
}

/** Revoca la sesión persistente (logout) por selector y rol/rol_id. */
function recordar_olvidar(?int $rolId = null, ?string $rol = null): void
{
    $cookie = (string) ($_COOKIE[RECORDAR_COOKIE] ?? '');
    if ($cookie !== '') {
        $selector = explode(':', $cookie, 2)[0];
        db()->prepare('DELETE FROM recordar_sesiones WHERE selector = ?')->execute([$selector]);
    } elseif ($rolId !== null && $rol !== null) {
        db()->prepare('DELETE FROM recordar_sesiones WHERE rol = ? AND rol_id = ?')->execute([$rol, $rolId]);
    }
    recordar_borrar_cookie();
}