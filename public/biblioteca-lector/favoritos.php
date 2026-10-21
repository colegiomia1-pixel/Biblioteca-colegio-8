<?php
/**
 * Colegio8 — Mis favoritos + recomendaciones (lector).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('lector');
$lec = sess('lector');
[$col, $id] = lector_scope($lec);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        flash('error', 'La solicitud expiró.');
        redirect('biblioteca-lector/favoritos.php');
    }
    if (!lector_tiene_persona($lec)) {
        flash('info', 'Completá tu solicitud de inscripción para guardar favoritos.');
        redirect('biblioteca-lector/favoritos.php');
    }
    $libroId = (int) ($_POST['libro_id'] ?? 0);
    db()->prepare("DELETE FROM biblioteca_favoritos WHERE libro_id = ? AND $col = ?")->execute([$libroId, $id]);
    flash('info', 'Se quitó de tus favoritos.');
    redirect('biblioteca-lector/favoritos.php');
}

$favoritos = db()->prepare(
    "SELECT l.*, f.fecha
     FROM biblioteca_favoritos f
     JOIN biblioteca_libros l ON l.id = f.libro_id
     WHERE f.$col = ? AND l.activo = 1
     ORDER BY f.fecha DESC"
);
$favoritos->execute([$id]);
$favoritos = $favoritos->fetchAll();

$recomendados = recomendar_libros($lec, 8);

$stG = db()->prepare(
    "SELECT l.genero, COUNT(*) AS n
     FROM biblioteca_favoritos f
     JOIN biblioteca_libros l ON l.id = f.libro_id
     WHERE f.$col = ? AND l.genero IS NOT NULL AND l.genero <> ''
     GROUP BY l.genero ORDER BY n DESC, l.genero"
);
$stG->execute([$id]);
$generosFav = $stG->fetchAll(PDO::FETCH_COLUMN);

render('biblioteca_lector/favoritos', [
    'titulo'    => 'Mis favoritos · Biblioteca',
    'rolPanel'  => 'lector',
    'favoritos' => $favoritos,
    'recomendados' => $recomendados,
    'generosFav' => $generosFav,
]);