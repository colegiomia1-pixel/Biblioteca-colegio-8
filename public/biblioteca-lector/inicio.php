<?php
/**
 * Colegio8 — Controlador de Inicio para LECTORES (Alumnos / Docentes).
 */
require __DIR__ . '/../../app/core.php';
require_once __DIR__ . '/../../app/biblioteca.php';

if (function_exists('requerir_sesion_activa')) {
    requerir_sesion_activa();
}

// Consultar los libros disponibles para el lector
$stLibros = db()->query("SELECT * FROM biblioteca_libros WHERE activo = 1 ORDER BY id DESC LIMIT 12");
$libros = $stLibros->fetchAll();

// OJO: AQUÍ SE CARGA LA VISTA DE LECTOR, NO DE ADMIN
render('biblioteca_lector/inicio', [
    'titulo'   => 'Biblioteca · Catálogo de Libros',
    'rolPanel' => 'lector',
    'libros'   => $libros
]);