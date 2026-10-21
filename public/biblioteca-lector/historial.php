<?php
/**
 * Colegio8 — Mi historial de préstamos (lector).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('lector');
$lec = sess('lector');
[$col, $id] = lector_scope($lec);

biblioteca_marcar_vencidos();

$historial = db()->prepare(
    "SELECT p.*, l.titulo, l.autor, l.portada,
            DATEDIFF(CURDATE(), p.fecha_limite) AS dias_vencido
     FROM biblioteca_prestamos p
     JOIN biblioteca_libros l ON l.id = p.libro_id
     WHERE p.$col = ?
     ORDER BY p.id DESC
     LIMIT 80"
);
$historial->execute([$id]);
$historial = $historial->fetchAll();

render('biblioteca_lector/historial', [
    'titulo'   => 'Mi historial · Biblioteca',
    'rolPanel' => 'lector',
    'historial'=> $historial,
]);