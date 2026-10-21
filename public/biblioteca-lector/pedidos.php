<?php
/**
 * Colegio8 — Mis pedidos de libros (lector).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('lector');
$lec = sess('lector');
[$col, $id] = lector_scope($lec);

$pedidos = db()->prepare(
    "SELECT pe.*, l.titulo, l.autor, l.portada,
            pe.fecha_peticion
     FROM biblioteca_peticiones pe
     JOIN biblioteca_libros l ON l.id = pe.libro_id
     WHERE pe.$col = ?
     ORDER BY pe.id DESC"
);
$pedidos->execute([$id]);
$pedidos = $pedidos->fetchAll();

render('biblioteca_lector/pedidos', [
    'titulo'   => 'Mis pedidos · Biblioteca',
    'rolPanel' => 'lector',
    'pedidos'  => $pedidos,
]);