<?php
/**
 * Colegio8 — Alertas de morosidad (préstamos vencidos + avisos).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('bibliotecario');
$bib = sess('bibliotecario');

// Recalcular en cada visita.
db()->query("UPDATE biblioteca_prestamos SET estado = 'vencido' WHERE estado = 'activo' AND fecha_limite < CURDATE()");

$vencidos = db()->query(
    "SELECT p.*, l.titulo, l.codigo_interno,
            CASE p.tipo_persona WHEN 'alumno' THEN CONCAT(a.apellido, ', ', a.nombre)
                                ELSE CONCAT(bp.apellido, ', ', bp.nombre) END AS persona,
            CASE p.tipo_persona WHEN 'alumno' THEN a.telefono ELSE bp.telefono END AS telefono,
            CASE p.tipo_persona WHEN 'alumno' THEN u.email ELSE bp.email END AS email,
            DATEDIFF(CURDATE(), p.fecha_limite) AS dias_vencido
     FROM biblioteca_prestamos p
     JOIN biblioteca_libros l ON l.id = p.libro_id
     LEFT JOIN alumnos a ON a.id = p.alumno_id
     LEFT JOIN usuarios u ON u.id = a.usuario_id
     LEFT JOIN biblioteca_profesores bp ON bp.id = p.profesor_id
     WHERE p.estado = 'vencido'
     ORDER BY p.fecha_limite, p.id"
)->fetchAll();

$ids = array_column($vencidos, 'id');
$avisosPorPrestamo = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare(
        "SELECT av.*, b.nombre_completo AS biblio
         FROM biblioteca_avisos av
         LEFT JOIN bibliotecarios b ON b.id = av.bibliotecario_id
         WHERE av.prestamo_id IN ($in)
         ORDER BY av.fecha_aviso DESC"
    );
    $st->execute($ids);
    foreach ($st->fetchAll() as $av) {
        $avisosPorPrestamo[(int) $av['prestamo_id']][] = $av;
    }
}

$medios = ['telefono' => 'Teléfono', 'email' => 'Email', 'presencial' => 'Presencial', 'otro' => 'Otro'];

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró.';
    } elseif (($_POST['accion'] ?? '') === 'avisar') {
        $id = (int) ($_POST['id'] ?? 0);
        $medio = $_POST['medio'] ?? 'otro';
        $notas = trim((string) ($_POST['notas'] ?? ''));
        if (!array_key_exists($medio, $medios)) $medio = 'otro';
        if ($id <= 0) {
            $errores[] = 'Préstamo inválido.';
        } else {
            db()->prepare('INSERT INTO biblioteca_avisos (prestamo_id, bibliotecario_id, medio, notas) VALUES (?, ?, ?, ?)')
                ->execute([$id, (int) $bib['id'], $medio, $notas ?: null]);
            flash('exito', 'Aviso registrado para el préstamo vencido.');
            redirect('biblioteca-admin/morosidad.php');
        }
    }
}

render('biblioteca_admin/morosidad', [
    'titulo'   => 'Alertas de morosidad',
    'rolPanel' => 'bibliotecario',
    'vencidos' => $vencidos,
    'avisos'   => $avisosPorPrestamo,
    'medios'   => $medios,
    'errores'  => $errores,
]);