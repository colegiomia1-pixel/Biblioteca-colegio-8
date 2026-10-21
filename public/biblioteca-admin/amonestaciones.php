<?php
/**
 * Colegio8 — Amonestaciones de la biblioteca (incidentes de alumnos).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('bibliotecario');
$bib = sess('bibliotecario');

$alumnos = db()->query(
    'SELECT a.id, a.nombre, a.apellido, a.dni, v.anio, v.division, v.turno
     FROM alumnos a
     LEFT JOIN vacantes v ON v.id = a.vacante_id
     ORDER BY a.apellido, a.nombre'
)->fetchAll();

$amonestaciones = db()->query(
    'SELECT am.*, CONCAT(a.apellido, ", ", a.nombre) AS alumno, a.dni,
            b.nombre_completo AS bibliotecario, v.anio, v.division
     FROM biblioteca_amonestaciones am
     JOIN alumnos a ON a.id = am.alumno_id
     LEFT JOIN bibliotecarios b ON b.id = am.bibliotecario_id
     LEFT JOIN vacantes v ON v.id = a.vacante_id
     ORDER BY am.fecha DESC, am.id DESC
     LIMIT 60'
)->fetchAll();

$tipos = [
    'tardanza'  => 'Tardanza',
    'mal_estado'=> 'Daño del libro',
    'perdida'   => 'Pérdida',
    'otro'      => 'Otro',
];

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró.';
    } else {
        $alumnoId = (int) ($_POST['alumno_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? '';
        $desc = trim((string) ($_POST['descripcion'] ?? ''));
        $fecha = $_POST['fecha'] ?? hoy_iso();

        if ($alumnoId <= 0) $errores[] = 'Elegí el alumno.';
        if (!array_key_exists($tipo, $tipos)) $errores[] = 'Elegí el tipo de amonestación.';
        if (mb_strlen($desc) < 5) $errores[] = 'Describí el incidente.';
        if (!strtotime($fecha)) $errores[] = 'La fecha no es válida.';

        if (!$errores) {
            db()->prepare('INSERT INTO biblioteca_amonestaciones (alumno_id, tipo, descripcion, fecha, bibliotecario_id)
                           VALUES (?, ?, ?, ?, ?)')
                ->execute([$alumnoId, $tipo, $desc, $fecha, (int) $bib['id']]);
            flash('exito', 'Amonestación registrada.');
            redirect('biblioteca-admin/amonestaciones.php');
        }
    }
}

render('biblioteca_admin/amonestaciones', [
    'titulo'   => 'Amonestaciones',
    'rolPanel' => 'bibliotecario',
    'alumnos'  => $alumnos,
    'amonestaciones' => $amonestaciones,
    'tipos'    => $tipos,
    'errores'  => $errores,
]);