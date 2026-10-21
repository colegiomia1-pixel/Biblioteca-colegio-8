<?php
/**
 * Colegio8 — Reportes de biblioteca (CSV + vista para imprimir/PDF).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('bibliotecario');

$tipo = $_GET['tipo'] ?? 'inventario';
$descargar = ($_GET['descargar'] ?? '') === '1';
$imprimir  = ($_GET['imprimir'] ?? '') === '1';

/* ---------------- Construcción de cada reporte ---------------- */

function datos_reporte_biblioteca(string $tipo): array
{
    switch ($tipo) {
        case 'inventario':
            $filas = db()->query(
                "SELECT l.titulo, l.autor, l.genero, l.isbn, l.codigo_interno,
                        l.ejemplares_total, l.ejemplares_disponibles, l.activo
                 FROM biblioteca_libros l ORDER BY l.titulo"
            )->fetchAll();
            foreach ($filas as &$f) {
                $f['prestados'] = (int) $f['ejemplares_total'] - (int) $f['ejemplares_disponibles'];
                $f['estado_t'] = $f['activo'] ? 'Activo' : 'De baja';
            }
            unset($f);
            return [
                'nombre' => 'inventario_libros',
                'columnas' => [
                    'Título' => 'titulo', 'Autor' => 'autor', 'Género' => 'genero',
                    'ISBN' => 'isbn', 'Código' => 'codigo_interno', 'Ejemplares' => 'ejemplares_total',
                    'Disponibles' => 'ejemplares_disponibles', 'Prestados' => 'prestados', 'Estado' => 'estado_t',
                ],
                'filas' => $filas,
            ];

        case 'prestamos':
            $filas = db()->query(
                "SELECT l.titulo,
                        CASE p.tipo_persona WHEN 'alumno' THEN CONCAT(a.apellido, ', ', a.nombre)
                                            ELSE CONCAT(bp.apellido, ', ', bp.nombre) END AS persona,
                        p.fecha_prestamo, p.fecha_limite, p.fecha_devolucion, p.estado
                 FROM biblioteca_prestamos p
                 JOIN biblioteca_libros l ON l.id = p.libro_id
                 LEFT JOIN alumnos a ON a.id = p.alumno_id
                 LEFT JOIN biblioteca_profesores bp ON bp.id = p.profesor_id
                 ORDER BY p.id DESC"
            )->fetchAll();
            return [
                'nombre' => 'prestamos',
                'columnas' => [
                    'Libro' => 'titulo', 'Persona' => 'persona', 'Prestado' => 'fecha_prestamo',
                    'Límite' => 'fecha_limite', 'Devuelto' => 'fecha_devolucion', 'Estado' => 'estado',
                ],
                'filas' => $filas,
            ];

        case 'morosidad':
            db()->query("UPDATE biblioteca_prestamos SET estado = 'vencido' WHERE estado = 'activo' AND fecha_limite < CURDATE()");
            $filas = db()->query(
                "SELECT l.titulo,
                        CASE p.tipo_persona WHEN 'alumno' THEN CONCAT(a.apellido, ', ', a.nombre)
                                            ELSE CONCAT(bp.apellido, ', ', bp.nombre) END AS persona,
                        p.fecha_limite, DATEDIFF(CURDATE(), p.fecha_limite) AS dias,
                        (SELECT COUNT(*) FROM biblioteca_avisos av WHERE av.prestamo_id = p.id) AS avisos
                 FROM biblioteca_prestamos p
                 JOIN biblioteca_libros l ON l.id = p.libro_id
                 LEFT JOIN alumnos a ON a.id = p.alumno_id
                 LEFT JOIN biblioteca_profesores bp ON bp.id = p.profesor_id
                 WHERE p.estado = 'vencido'
                 ORDER BY p.fecha_limite"
            )->fetchAll();
            return [
                'nombre' => 'morosidad',
                'columnas' => ['Libro' => 'titulo', 'Persona' => 'persona', 'Límite' => 'fecha_limite',
                    'Días vencido' => 'dias', 'Avisos' => 'avisos'],
                'filas' => $filas,
            ];

        case 'pedidos':
            $filas = db()->query(
                "SELECT l.titulo,
                        CASE p.tipo_persona WHEN 'alumno' THEN CONCAT(a.apellido, ', ', a.nombre)
                                            ELSE CONCAT(bp.apellido, ', ', bp.nombre) END AS persona,
                        p.fecha_peticion, p.estado, p.motivo_rechazo
                 FROM biblioteca_peticiones p
                 JOIN biblioteca_libros l ON l.id = p.libro_id
                 LEFT JOIN alumnos a ON a.id = p.alumno_id
                 LEFT JOIN biblioteca_profesores bp ON bp.id = p.profesor_id
                 ORDER BY p.id DESC"
            )->fetchAll();
            return [
                'nombre' => 'pedidos',
                'columnas' => ['Libro' => 'titulo', 'Persona' => 'persona', 'Pedido' => 'fecha_peticion',
                    'Estado' => 'estado', 'Motivo de rechazo' => 'motivo_rechazo'],
                'filas' => $filas,
            ];

        case 'alumnos':
            $filas = db()->query(
                "SELECT a.nombre, a.apellido, a.dni, a.telefono,
                        v.anio, v.division, v.turno,
                        (SELECT COUNT(*) FROM biblioteca_amonestaciones am WHERE am.alumno_id = a.id) AS amonestaciones
                 FROM alumnos a
                 LEFT JOIN vacantes v ON v.id = a.vacante_id
                 ORDER BY a.apellido, a.nombre"
            )->fetchAll();
            foreach ($filas as &$f) {
                $f['curso_t'] = $f['anio'] ? (int) $f['anio'] . '° ' . $f['division'] . ' (' . ($f['turno'] === 'manana' ? 'mañana' : 'tarde') . ')' : 'Sin asignar';
            }
            unset($f);
            return [
                'nombre' => 'alumnos_biblioteca',
                'columnas' => ['Apellido' => 'apellido', 'Nombre' => 'nombre', 'DNI' => 'dni',
                    'Teléfono' => 'telefono', 'Curso' => 'curso_t', 'Amonestaciones' => 'amonestaciones'],
                'filas' => $filas,
            ];

        case 'profesores':
            $filas = db()->query(
                "SELECT pr.nombre, pr.apellido, pr.dni, pr.email, pr.telefono, pr.usuario, pr.activo,
                        (SELECT COUNT(*) FROM biblioteca_prestamos p WHERE p.tipo_persona = 'profesor' AND p.profesor_id = pr.id) AS prestamos
                 FROM biblioteca_profesores pr
                 ORDER BY pr.apellido, pr.nombre"
            )->fetchAll();
            foreach ($filas as &$f) {
                $f['estado_t'] = $f['activo'] ? 'Activo' : 'Desactivado';
            }
            unset($f);
            return [
                'nombre' => 'profesores',
                'columnas' => ['Apellido' => 'apellido', 'Nombre' => 'nombre', 'DNI' => 'dni',
                    'Email' => 'email', 'Teléfono' => 'telefono', 'Usuario' => 'usuario',
                    'Estado' => 'estado_t', 'Préstamos' => 'prestamos'],
                'filas' => $filas,
            ];
    }
    return ['nombre' => '', 'columnas' => [], 'filas' => []];
}

$reporte = datos_reporte_biblioteca($tipo);

if ($descargar && $reporte['nombre'] !== '') {
    descargar_csv('colegio8_biblio_' . $reporte['nombre'] . '_' . date('Ymd') . '.csv', $reporte['columnas'], $reporte['filas']);
}

$tiposInfo = [
    'inventario' => ['Inventario de libros', 'Todos los títulos con ejemplares disponibles, prestados y estado.'],
    'prestamos'  => ['Préstamos', 'Historial completo de préstamos y devoluciones.'],
    'morosidad'  => ['Morosidad', 'Préstamos vencidos con días de atraso y avisos enviados.'],
    'pedidos'    => ['Pedidos de libros', 'Todas las peticiones con su estado y motivo de rechazo.'],
    'alumnos'    => ['Alumnos', 'Padrón de alumnos con curso asignado y amonestaciones.'],
    'profesores' => ['Profesores', 'Profesores con acceso a biblioteca y cantidad de préstamos.'],
];

render('biblioteca_admin/reportes', [
    'titulo'        => 'Reportes · Biblioteca',
    'rolPanel'      => 'bibliotecario',
    'tipo'          => $tipo,
    'reporte'       => $reporte,
    'tiposInfo'     => $tiposInfo,
    'vistaImpresion'=> $imprimir,
]);