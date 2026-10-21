<?php
/**
 * Colegio8 — Controlador de Inicio para ADMINISTRACIÓN de Biblioteca.
 */
require __DIR__ . '/../../app/core.php';
require_once __DIR__ . '/../../app/biblioteca.php';

if (function_exists('requerir_sesion_activa')) {
    requerir_sesion_activa();
}

// Datos para las tarjetas del administrador
$totalLibros = (int) db()->query("SELECT COUNT(*) FROM biblioteca_libros WHERE activo = 1")->fetchColumn();
$totalPrestamos = (int) db()->query("SELECT COUNT(*) FROM biblioteca_prestamos WHERE estado = 'activo'")->fetchColumn();
$totalReservas = (int) db()->query("SELECT COUNT(*) FROM biblioteca_reservas WHERE estado = 'pendiente'")->fetchColumn();

// Carga la vista de ADMINISTRADOR
render('biblioteca_admin/inicio', [
    'titulo'           => 'Inicio · Panel Biblioteca',
    'rolPanel'         => 'admin',
    'totalLibros'      => $totalLibros,
    'totalPrestamos'   => $totalPrestamos,
    'totalReservas'    => $totalReservas,
    'libros'           => $totalLibros,
    'prestamosActivos' => $totalPrestamos,
    'reservas'         => $totalReservas
]);