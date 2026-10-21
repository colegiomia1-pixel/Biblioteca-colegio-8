<?php
/**
 * Colegio8 — Usuarios de la biblioteca (alumnos, profesores, bibliotecarios).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('bibliotecario');
$bib = sess('bibliotecario');

$bibId = (int) $bib['id'];

$filtroAl = trim((string) ($_GET['alunmo_buscar'] ?? ''));   // compat alias
$filtroAl = trim((string) ($_GET['alumno_buscar'] ?? $filtroAl));
$patron = '%' . $filtroAl . '%';

$stA = db()->prepare(
    "SELECT a.id, a.nombre, a.apellido, a.dni, a.telefono, u.email, a.fecha_nacimiento,
            v.anio, v.turno, v.division, v.id AS vacante_id
     FROM alumnos a
     JOIN usuarios u ON u.id = a.usuario_id
     LEFT JOIN vacantes v ON v.id = a.vacante_id
     WHERE a.nombre LIKE ? OR a.apellido LIKE ? OR a.dni LIKE ?
     ORDER BY a.apellido, a.nombre"
);
$stA->execute([$patron, $patron, $patron]);
$alumnos = $stA->fetchAll();

$profesores = db()->query('SELECT * FROM biblioteca_profesores ORDER BY apellido, nombre')->fetchAll();
$biblios = db()->query('SELECT b.*, (SELECT COUNT(*) FROM bibliotecarios) AS total FROM bibliotecarios b ORDER BY b.nombre_completo')->fetchAll();

$errores = [];
$valores = [
    'profNombre' => '', 'profApellido' => '', 'profDni' => '', 'profEmail' => '', 'profTel' => '',
    'profUsuario' => '', 'profPass' => '',
    'bibNombre' => '', 'bibUsuario' => '', 'bibPass' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró.';
    } else {
        $accion = $_POST['accion'] ?? '';

        /* -------- Contacto de alumno -------- */
        if ($accion === 'editar_alumno') {
            db()->prepare("UPDATE alumnos SET telefono = ? WHERE id = ?")->execute([
                $_POST['telefono'] ?? '' ?: null,
                (int) ($_POST['id'] ?? 0),
            ]);
            $stU = db()->prepare('SELECT usuario_id FROM alumnos WHERE id = ?');
            $stU->execute([(int) ($_POST['id'] ?? 0)]);
            $uid = (int) $stU->fetchColumn();
            if ($uid > 0) {
                db()->prepare('UPDATE usuarios SET email = ? WHERE id = ?')->execute([
                    $_POST['email'] ?? '' ?: null,
                    $uid,
                ]);
            }
            flash('exito', 'Contacto del alumno actualizado.');
            redirect('biblioteca-admin/usuarios.php?s=alumnos');
        }

        /* -------- Profesores -------- */
        if (in_array($accion, ['crear_profesor', 'editar_profesor', 'desactivar_profesor', 'reactivar_profesor'], true)) {
            $id = (int) ($_POST['id'] ?? 0);
            if ($accion === 'desactivar_profesor' || $accion === 'reactivar_profesor') {
                $nuevo = $accion === 'desactivar_profesor' ? 0 : 1;
                db()->prepare('UPDATE biblioteca_profesores SET activo = ? WHERE id = ?')->execute([$nuevo, $id]);
                flash('exito', 'Profesor actualizado.');
                redirect('biblioteca-admin/usuarios.php?s=profesores');
            }

            $nombre = ucwords(mb_strtolower(trim((string) $_POST['nombre'])));
            $apellido = ucwords(mb_strtolower(trim((string) $_POST['apellido'])));
            $dni = trim((string) $_POST['dni']);
            $email = trim((string) $_POST['email']);
            $telefono = trim((string) $_POST['telefono']);
            $usuario = trim((string) $_POST['usuario']);
            $pass = (string) ($_POST['password'] ?? '');

            if (mb_strlen($nombre) < 2 || mb_strlen($apellido) < 2) $errores[] = 'Nombre y apellido son obligatorios.';
            if (!preg_match('/^\d{6,10}$/', $dni)) $errores[] = 'DNI inválido (entre 6 y 10 dígitos).';
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido.';
            if (mb_strlen($usuario) < 3) $errores[] = 'El usuario debe tener al menos 3 caracteres.';
            if ($accion === 'crear_profesor' && mb_strlen($pass) < 4) $errores[] = 'La contraseña debe tener al menos 4 caracteres.';
            if ($accion === 'editar_profesor' && $pass !== '' && mb_strlen($pass) < 4) $errores[] = 'La contraseña debe tener al menos 4 caracteres (o dejá vacía para no cambiarla).';

            if (!$errores) {
                $dup = db()->prepare('SELECT id FROM biblioteca_profesores WHERE (dni = ? OR usuario = ? OR email = ?) AND id <> ?');
                $dup->execute([$dni, $usuario, $email ?: '__null__', $id]);
                if ($dup->fetch()) {
                    $errores[] = 'Ya existe un profesor con ese DNI, usuario o email.';
                }
            }

            if (!$errores) {
                if ($accion === 'crear_profesor') {
                    db()->prepare('INSERT INTO biblioteca_profesores (nombre, apellido, dni, email, telefono, usuario, password_hash)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)')
                        ->execute([$nombre, $apellido, $dni, $email ?: null, $telefono ?: null, $usuario, password_hash($pass, PASSWORD_BCRYPT)]);
                    flash('exito', 'Profesor creado. Ya puede ingresar como lector.');
                } else {
                    if ($pass !== '') {
                        db()->prepare('UPDATE biblioteca_profesores SET nombre = ?, apellido = ?, dni = ?, email = ?, telefono = ?, usuario = ?, password_hash = ? WHERE id = ?')
                            ->execute([$nombre, $apellido, $dni, $email ?: null, $telefono ?: null, $usuario, password_hash($pass, PASSWORD_BCRYPT), $id]);
                    } else {
                        db()->prepare('UPDATE biblioteca_profesores SET nombre = ?, apellido = ?, dni = ?, email = ?, telefono = ?, usuario = ? WHERE id = ?')
                            ->execute([$nombre, $apellido, $dni, $email ?: null, $telefono ?: null, $usuario, $id]);
                    }
                    flash('exito', 'Profesor actualizado.');
                }
                redirect('biblioteca-admin/usuarios.php?s=profesores');
            }
            $valores = [
                'profNombre' => $nombre, 'profApellido' => $apellido, 'profDni' => $dni,
                'profEmail' => $email, 'profTel' => $telefono, 'profUsuario' => $usuario, 'profPass' => $pass,
            ] + $valores;
        }

        /* -------- Bibliotecarios -------- */
        if ($accion === 'crear_bibliotecario') {
            $nombre = trim((string) $_POST['nombre']);
            $usuario = trim((string) $_POST['usuario']);
            $pass = (string) ($_POST['password'] ?? '');

            if (mb_strlen($nombre) < 3) $errores[] = 'Ingresá el nombre completo del bibliotecario.';
            if (mb_strlen($usuario) < 3) $errores[] = 'El usuario debe tener al menos 3 caracteres.';
            if (mb_strlen($pass) < 4) $errores[] = 'La contraseña debe tener al menos 4 caracteres.';
            $dup = db()->prepare('SELECT id FROM bibliotecarios WHERE usuario = ?');
            $dup->execute([$usuario]);
            if ($dup->fetch()) $errores[] = 'Ese usuario de bibliotecario ya existe.';

            if (!$errores) {
                db()->prepare('INSERT INTO bibliotecarios (usuario, nombre_completo, password_hash) VALUES (?, ?, ?)')
                    ->execute([$usuario, $nombre, password_hash($pass, PASSWORD_BCRYPT)]);
                flash('exito', 'Bibliotecario creado.');
                redirect('biblioteca-admin/usuarios.php?s=bibliotecarios');
            }
            $valores = ['bibNombre' => $nombre, 'bibUsuario' => $usuario] + $valores;
        }
    }
}

render('biblioteca_admin/usuarios', [
    'titulo'   => 'Usuarios',
    'rolPanel' => 'bibliotecario',
    'seccion'  => $_GET['s'] ?? 'alumnos',
    'alumnos'  => $alumnos,
    'profesores' => $profesores,
    'biblios'  => $biblios,
    'bibId'    => $bibId,
    'filtroAl' => $filtroAl,
    'errores'  => $errores,
    'valores'  => $valores,
]);