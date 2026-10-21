<?php
/**
 * Colegio8 — Alta inicial (única) de la primera cuenta de bibliotecario.
 * Solo disponible mientras no exista ninguna cuenta de bibliotecario.
 */
require __DIR__ . '/../app/core.php';

$habilitado = (int) (valor_config('alta_inicial_bibliotecario', '1')) === 1;
$existentes = (int) db()->query('SELECT COUNT(*) FROM bibliotecarios')->fetchColumn();
if ($existentes > 0 || !$habilitado) {
    redirect('login.php?modulo=bibliotecario');
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró o es inválida.';
    } else {
        $nombre = trim((string) ($_POST['nombre_completo'] ?? ''));
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password2'] ?? '');

        if (mb_strlen($nombre) < 5) $errores[] = 'Ingresá el nombre completo.';
        if (mb_strlen($usuario) < 3) $errores[] = 'El usuario debe tener al menos 3 caracteres.';
        if (strlen($pass) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';

        if (!$errores) {
            $st = db()->prepare('INSERT INTO bibliotecarios (usuario, nombre_completo, password_hash) VALUES (?, ?, ?)');
            $st->execute([$usuario, $nombre, password_hash($pass, PASSWORD_DEFAULT)]);
            flash('exito', 'Cuenta de bibliotecario creada. Esta pantalla quedó deshabilitada.');
            redirect('login.php?modulo=bibliotecario');
        }
    }
}

render('publico/primer_uso', [
    'titulo'   => 'Alta inicial · Bibliotecario',
    'rolPanel' => 'publico',
    'errores'  => $errores,
    'rol'      => 'bibliotecario',
    'rolNombre' => 'Administración de biblioteca',
    'directo'  => url('login.php?modulo=bibliotecario'),
]);