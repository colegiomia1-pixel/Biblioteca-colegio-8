<?php
/**
 * Colegio8 — Detalle de un libro para el lector.
 */
require __DIR__ . '/../../app/core.php';
require_once __DIR__ . '/../../app/biblioteca.php';

requerir_sesion('lector');
$lec = sess('lector');
[$col, $id] = lector_scope($lec);

$libroId = (int) ($_GET['id'] ?? 0);

$idPers = (int) $id; // siempre es un entero interno del servidor (whitelist de columna)
$st = db()->prepare(
    "SELECT l.*,
            (SELECT COUNT(*) FROM biblioteca_favoritos f WHERE f.libro_id = l.id AND f.$col = $idPers) AS es_favorito,
            (SELECT COUNT(*) FROM biblioteca_prestamos p WHERE p.libro_id = l.id AND p.estado IN ('activo','vencido')) AS ejemplares_prestados,
            (SELECT COUNT(*) FROM biblioteca_peticiones pe WHERE pe.libro_id = l.id AND pe.$col = $idPers AND pe.estado = 'pendiente') AS pedido_pendiente,
            (SELECT COUNT(*) FROM biblioteca_reservas r WHERE r.libro_id = l.id AND r.$col = $idPers AND r.estado = 'pendiente') AS reserva_pendiente
     FROM biblioteca_libros l WHERE l.id = :lid AND l.activo = 1"
);
$st->execute(['lid' => $libroId]);
$libro = $st->fetch();

if (!$libro) {
    flash('error', 'El libro no existe o no está en el catálogo.');
    redirect('biblioteca-lector/catalogo.php');
}

// Disponibilidad real de ejemplares
$disponibles = max(0, (int)($libro['ejemplares_totales'] ?? $libro['ejemplares_total'] ?? 1) - (int)($libro['ejemplares_prestados'] ?? 0));
$libro['ejemplares_disponibles'] = $disponibles;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        flash('error', 'La solicitud expiró.');
        redirect('biblioteca-lector/libro.php?id=' . $libroId);
    }

    if (!lector_tiene_persona($lec)) {
        flash('info', 'Completá tu solicitud de inscripción para interactuar con los libros.');
        redirect('biblioteca-lector/libro.php?id=' . $libroId);
    }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'favorito') {
        $st = db()->prepare("SELECT id FROM biblioteca_favoritos WHERE libro_id = ? AND $col = ?");
        $st->execute([$libroId, $id]);
        if ($st->fetch()) {
            db()->prepare("DELETE FROM biblioteca_favoritos WHERE libro_id = ? AND $col = ?")->execute([$libroId, $id]);
            flash('info', 'Se quitó de tus favoritos.');
        } else {
            db()->prepare("INSERT INTO biblioteca_favoritos (tipo_persona, $col, libro_id) VALUES (?, ?, ?)")
                ->execute([$lec['tipo'] === 'profesor' ? 'profesor' : 'alumno', $id, $libroId]);
            flash('exito', 'Guardado en favoritos.');
        }
        redirect('biblioteca-lector/libro.php?id=' . $libroId);
    }

    if ($accion === 'pedir') {
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        if (mb_strlen($motivo) > 500) {
            flash('error', 'El motivo es demasiado largo.');
        } else {
            $st = db()->prepare("SELECT id FROM biblioteca_peticiones WHERE libro_id = ? AND $col = ? AND estado = 'pendiente'");
            $st->execute([$libroId, $id]);
            if ($st->fetch()) {
                flash('info', 'Ya tenés un pedido pendiente de este libro.');
            } else {
                db()->prepare("INSERT INTO biblioteca_peticiones (tipo_persona, $col, libro_id, motivo) VALUES (?, ?, ?, ?)")
                    ->execute([$lec['tipo'] === 'profesor' ? 'profesor' : 'alumno', $id, $libroId, $motivo ?: null]);
                flash('exito', 'Pedido enviado. El bibliotecario va a responderlo y vas a verlo en «Mis pedidos».');
            }
        }
        redirect('biblioteca-lector/libro.php?id=' . $libroId);
    }

    if ($accion === 'reservar') {
        if (crear_reserva($libroId, $lec)) {
            flash('exito', '¡Reserva realizada con éxito! Te avisaremos cuando se libere un ejemplar.');
        } else {
            flash('info', 'Ya tenés una reserva pendiente para este libro.');
        }
        redirect('biblioteca-lector/libro.php?id=' . $libroId);
    }

    if ($accion === 'resena') {
        $puntuacion = (int)($_POST['puntuacion'] ?? 0);
        $comentario = trim((string)($_POST['comentario'] ?? ''));

        if ($puntuacion < 1 || $puntuacion > 5) {
            flash('error', 'Por favor selecciona una puntuación entre 1 y 5 estrellas.');
        } else {
            if (function_exists('agregar_resena') && agregar_resena($libroId, $lec, $puntuacion, $comentario)) {
                flash('exito', '¡Gracias por tu opinión! Tu reseña ha sido guardada.');
            } else {
                flash('error', 'No se pudo guardar la reseña.');
            }
        }
        redirect('biblioteca-lector/libro.php?id=' . $libroId);
    }
}

$rating = function_exists('promedio_puntuacion_libro') 
    ? promedio_puntuacion_libro($libroId) 
    : ['promedio' => 0, 'total' => 0];

$tambienEnCurso = db()->prepare('SELECT id, titulo, autor, portada FROM biblioteca_libros WHERE genero = :g AND id <> :lid AND activo = 1 ORDER BY id DESC LIMIT 4');
$tambienEnCurso->execute(['g' => $libro['genero'] ?: '__sin_genero__', 'lid' => $libroId]);
$similares = $tambienEnCurso->fetchAll();

render('biblioteca_lector/libro', [
    'titulo'    => $libro['titulo'] . ' · Biblioteca',
    'rolPanel'  => 'lector',
    'lec'       => $lec,
    'libro'     => $libro,
    'rating'    => $rating,
    'similares' => $similares,
]);