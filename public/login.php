<?php
/**
 * Colegio8 — Página de ingreso.
 */
require __DIR__ . '/../app/core.php';

$modulo    = $_REQUEST['modulo'] ?? 'lector';
$permitido = ['postulante', 'lector', 'secretaria', 'bibliotecario'];
if (!in_array($modulo, $permitido, true)) {
    redirect('index.php');
}

$destinos = [
    'postulante'    => 'postulante/estado.php',
    'lector'        => 'biblioteca-lector/inicio.php',
    'secretaria'    => 'secretaria/inicio.php',
    'bibliotecario' => 'biblioteca-admin/inicio.php',
];

if (tiene_sesion('bibliotecario')) {
    redirect($destinos['bibliotecario']);
}
if (tiene_sesion('lector')) {
    redirect($destinos['lector']);
}

$error = null;
$perfilSeleccionado = $_POST['perfil_tipo'] ?? 'lector'; // 'lector' o 'admin'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $error = 'La solicitud expiró o es inválida. Volvé a intentar.';
    } else {
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $clave   = (string) ($_POST['password'] ?? '');
        $recordar = isset($_POST['recordar']);

        if ($perfilSeleccionado === 'admin' || $modulo === 'bibliotecario') {
            // --- INGRESO COMO ADMINISTRADOR DE BIBLIOTECA ---
            $stB = db()->prepare('SELECT id, usuario, nombre_completo, password_hash FROM bibliotecarios WHERE usuario = ?');
            $stB->execute([$usuario]);
            $b = $stB->fetch();

            if ($b && password_verify($clave, $b['password_hash'])) {
                iniciar_sesion_rol('bibliotecario', [
                    'id'      => (int) $b['id'],
                    'usuario' => $b['usuario'],
                    'nombre'  => $b['nombre_completo'],
                ]);
                flash('exito', 'Bienvenido/a al Panel de Administración, ' . $b['nombre_completo'] . '.');
                redirect($destinos['bibliotecario']);
            } else {
                $error = 'Credenciales de Administrador incorrectas o no tenés permisos de gestión.';
            }
        } else {
            // --- INGRESO COMO LECTOR (ALUMNO / TUTOR / PROFESOR) ---
            $st = db()->prepare('SELECT id, usuario, email, password_hash FROM usuarios WHERE usuario = ? OR email = ?');
            $st->execute([$usuario, $usuario]);
            $u = $st->fetch();

            if ($u && password_verify($clave, $u['password_hash'])) {
                $stAlu = db()->prepare('SELECT id, nombre, apellido FROM alumnos WHERE usuario_id = ?');
                $stAlu->execute([(int) $u['id']]);
                $alu = $stAlu->fetch();

                iniciar_sesion_rol('lector', [
                    'id'         => (int) $u['id'],
                    'tipo'       => 'alumno',
                    'persona_id' => $alu ? (int) $alu['id'] : null,
                    'usuario'    => $u['usuario'],
                    'nombre'     => $alu ? ($alu['nombre'] . ' ' . $alu['apellido']) : $u['usuario'],
                ]);

                flash('exito', 'Bienvenido/a a la biblioteca, ' . ($alu ? $alu['nombre'] : $u['usuario']) . '.');
                redirect($destinos['lector']);
            } else {
                // Probar si es profesor
                $stP = db()->prepare('SELECT id, nombre, apellido, usuario, password_hash FROM biblioteca_profesores WHERE usuario = ? AND activo = 1');
                $stP->execute([$usuario]);
                $p = $stP->fetch();

                if ($p && password_verify($clave, $p['password_hash'])) {
                    iniciar_sesion_rol('lector', [
                        'id'         => (int) $p['id'],
                        'tipo'       => 'profesor',
                        'persona_id' => (int) $p['id'],
                        'usuario'    => $p['usuario'],
                        'nombre'     => $p['nombre'] . ' ' . $p['apellido'],
                    ]);
                    flash('exito', 'Bienvenido Profesor/a, ' . $p['nombre'] . '.');
                    redirect($destinos['lector']);
                } else {
                    $error = 'Usuario o contraseña de Lector incorrectos.';
                }
            }
        }
    }
}

render('login', [
    'titulo'             => 'Acceso a Biblioteca',
    'rolPanel'           => 'publico',
    'modulo'             => $modulo,
    'perfilSeleccionado' => $perfilSeleccionado,
    'error'              => $error,
]);