<?php
/**
 * Colegio8 — Controlador de Reservas y Reseñas para Administración.
 */
require __DIR__ . '/../../app/core.php';
require_once __DIR__ . '/../../app/biblioteca.php';

if (function_exists('requerir_sesion_activa')) {
    requerir_sesion_activa();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verificar_csrf') && !verificar_csrf()) {
        flash('error', 'La sesión o el formulario han expirado.');
        redirect('biblioteca-admin/resenas-reservas.php');
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'completar_reserva') {
        $reservaId = (int)($_POST['reserva_id'] ?? 0);
        $st = db()->prepare("UPDATE biblioteca_reservas SET estado = 'completado' WHERE id = ?");
        $st->execute([$reservaId]);
        flash('exito', 'Reserva completada con éxito.');
        redirect('biblioteca-admin/resenas-reservas.php');
    }

    if ($accion === 'cancelar_reserva') {
        $reservaId = (int)($_POST['reserva_id'] ?? 0);
        $st = db()->prepare("UPDATE biblioteca_reservas SET estado = 'cancelado' WHERE id = ?");
        $st->execute([$reservaId]);
        flash('info', 'Reserva cancelada.');
        redirect('biblioteca-admin/resenas-reservas.php');
    }

    if ($accion === 'eliminar_resena') {
        $resenaId = (int)($_POST['resena_id'] ?? 0);
        $st = db()->prepare("DELETE FROM biblioteca_resenas WHERE id = ?");
        $st->execute([$resenaId]);
        flash('exito', 'Reseña eliminada correctamente.');
        redirect('biblioteca-admin/resenas-reservas.php');
    }
}

// Consultar reservas pendientes
$stRes = db()->query(
    "SELECT r.*, l.titulo AS libro_titulo, l.codigo_interno,
            COALESCE(CONCAT(a.apellido, ', ', a.nombre), CONCAT(p.apellido, ', ', p.nombre)) AS solicitante
     FROM biblioteca_reservas r
     JOIN biblioteca_libros l ON l.id = r.libro_id
     LEFT JOIN alumnos a ON a.id = r.alumno_id
     LEFT JOIN profesores p ON p.id = r.profesor_id
     WHERE r.estado = 'pendiente'
     ORDER BY r.fecha_creacion ASC"
);
$reservas = $stRes->fetchAll();

// Consultar reseñas para moderar
$stRev = db()->query(
    "SELECT r.*, l.titulo AS libro_titulo,
            COALESCE(CONCAT(a.apellido, ', ', a.nombre), CONCAT(p.apellido, ', ', p.nombre)) AS usuario
     FROM biblioteca_resenas r
     JOIN biblioteca_libros l ON l.id = r.libro_id
     LEFT JOIN alumnos a ON a.id = r.alumno_id
     LEFT JOIN profesores p ON p.id = r.profesor_id
     ORDER BY r.fecha_creacion DESC"
);
$resenas = $stRev->fetchAll();

render('biblioteca_admin/resenas_reservas', [
    'titulo'   => 'Reservas y Reseñas · Biblioteca',
    'rolPanel' => 'admin',
    'reservas' => $reservas,
    'resenas'  => $resenas,
]);