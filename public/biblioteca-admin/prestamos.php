<?php
/**
 * Colegio8 — Préstamos de biblioteca (registrar, devolver, cancelar).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('bibliotecario');
$bib = sess('bibliotecario');

// Marcar vencidos automáticamente.
db()->query("UPDATE biblioteca_prestamos SET estado = 'vencido' WHERE estado = 'activo' AND fecha_limite < CURDATE()");

$alumnos = db()->query(
    "SELECT a.id, a.nombre, a.apellido, a.dni
     FROM alumnos a
     JOIN solicitudes s ON s.alumno_id = a.id AND s.estado = 'aprobada'
     ORDER BY a.apellido, a.nombre"
)->fetchAll();

$profesores = db()->query('SELECT id, nombre, apellido, dni FROM biblioteca_profesores ORDER BY apellido, nombre')->fetchAll();

$librosDisp = db()->query('SELECT id, titulo, autor, codigo_interno, ejemplares_disponibles FROM biblioteca_libros WHERE activo = 1 AND ejemplares_disponibles > 0 ORDER BY titulo')->fetchAll();
$librosTodos = db()->query('SELECT id, titulo, autor, codigo_interno FROM biblioteca_libros WHERE activo = 1 ORDER BY titulo')->fetchAll();

$diasPrestamo = (int) (valor_config('dias_prestamo', '15') ?? 15);

$prestamosActivos = db()->query(
    "SELECT p.*, l.titulo, l.codigo_interno,
            CASE p.tipo_persona WHEN 'alumno' THEN CONCAT(a.apellido, ', ', a.nombre)
                                ELSE CONCAT(bp.apellido, ', ', bp.nombre) END AS persona,
            CASE p.tipo_persona WHEN 'alumno' THEN a.dni ELSE bp.dni END AS dni_persona
     FROM biblioteca_prestamos p
     JOIN biblioteca_libros l ON l.id = p.libro_id
     LEFT JOIN alumnos a ON a.id = p.alumno_id
     LEFT JOIN biblioteca_profesores bp ON bp.id = p.profesor_id
     WHERE p.estado IN ('activo','vencido')
     ORDER BY p.fecha_limite, p.id"
)->fetchAll();

$historial = db()->query(
    "SELECT p.*, l.titulo,
            CASE p.tipo_persona WHEN 'alumno' THEN CONCAT(a.apellido, ', ', a.nombre)
                                ELSE CONCAT(bp.apellido, ', ', bp.nombre) END AS persona
     FROM biblioteca_prestamos p
     JOIN biblioteca_libros l ON l.id = p.libro_id
     LEFT JOIN alumnos a ON a.id = p.alumno_id
     LEFT JOIN biblioteca_profesores bp ON bp.id = p.profesor_id
     WHERE p.estado IN ('devuelto','cancelado')
     ORDER BY p.id DESC LIMIT 40"
)->fetchAll();

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró.';
    } else {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear') {
            $persona = $_POST['persona'] ?? '';
            $libroId = (int) ($_POST['libro_id'] ?? 0);
            $fechaPre = $_POST['fecha_prestamo'] ?? hoy_iso();
            $fechaLim = $_POST['fecha_limite'] ?? '';

            [$tipoP, $idP] = array_pad(explode(':', (string) $persona, 2), 2, null);
            $idP = (int) ($idP ?? 0);

            if (!in_array($tipoP, ['alumno', 'profesor'], true) || $idP <= 0) {
                $errores[] = 'Elegí la persona que retira el libro.';
            }
            if ($libroId <= 0) {
                $errores[] = 'Elegí un libro.';
            }
            if (!strtotime($fechaPre) || !strtotime($fechaLim)) {
                $errores[] = 'Las fechas son obligatorias.';
            }
            if (strtotime($fechaLim) < strtotime($fechaPre)) {
                $errores[] = 'La fecha límite no puede ser anterior a la de préstamo.';
            }

            if (!$errores) {
                db()->beginTransaction();
                try {
                    $stL = db()->prepare('SELECT ejemplares_disponibles, titulo FROM biblioteca_libros WHERE id = ? AND activo = 1 FOR UPDATE');
                    $stL->execute([$libroId]);
                    $lib = $stL->fetch();
                    if (!$lib) {
                        throw new RuntimeException('El libro no existe o está inactivo.');
                    }
                    if ((int) $lib['ejemplares_disponibles'] <= 0) {
                        throw new RuntimeException('No quedan ejemplares disponibles de «' . $lib['titulo'] . '».');
                    }
                    if ($tipoP === 'alumno') {
                        $st = db()->prepare('INSERT INTO biblioteca_prestamos (tipo_persona, alumno_id, profesor_id, libro_id, fecha_prestamo, fecha_limite)
                                             VALUES (?, ?, NULL, ?, ?, ?)');
                        $st->execute(['alumno', $idP, $libroId, $fechaPre, $fechaLim]);
                    } else {
                        $st = db()->prepare('INSERT INTO biblioteca_prestamos (tipo_persona, alumno_id, profesor_id, libro_id, fecha_prestamo, fecha_limite)
                                             VALUES (?, NULL, ?, ?, ?, ?)');
                        $st->execute(['profesor', $idP, $libroId, $fechaPre, $fechaLim]);
                    }
                    db()->prepare('UPDATE biblioteca_libros SET ejemplares_disponibles = ejemplares_disponibles - 1 WHERE id = ?')->execute([$libroId]);
                    db()->commit();
                    flash('exito', 'Préstamo registrado. Se descontó un ejemplar.');
                    redirect('biblioteca-admin/prestamos.php');
                } catch (Throwable $t) {
                    db()->rollBack();
                    $errores[] = $t->getMessage();
                }
            }
        } elseif (in_array($accion, ['devolver', 'cancelar'], true)) {
            $id = (int) ($_POST['id'] ?? 0);
            db()->beginTransaction();
            try {
                $st = db()->prepare('SELECT * FROM biblioteca_prestamos WHERE id = ? FOR UPDATE');
                $st->execute([$id]);
                $p = $st->fetch();
                if (!$p || in_array($p['estado'], ['devuelto', 'cancelado'], true)) {
                    throw new RuntimeException('Préstamo no encontrado o ya finalizado.');
                }
                if ($accion === 'devolver') {
                    db()->prepare("UPDATE biblioteca_prestamos SET estado = 'devuelto', fecha_devolucion = CURDATE() WHERE id = ?")->execute([$id]);
                    flash('exito', 'Devolución registrada. Se repuso el ejemplar.');
                } else {
                    db()->prepare("UPDATE biblioteca_prestamos SET estado = 'cancelado', fecha_devolucion = CURDATE() WHERE id = ?")->execute([$id]);
                    flash('info', 'Préstamo cancelado. Se repuso el ejemplar.');
                }
                db()->prepare('UPDATE biblioteca_libros SET ejemplares_disponibles = ejemplares_disponibles + 1 WHERE id = ?')->execute([(int) $p['libro_id']]);
                db()->commit();
            } catch (Throwable $t) {
                db()->rollBack();
                $errores[] = $t->getMessage();
            }
            redirect('biblioteca-admin/prestamos.php');
        }
    }
}

render('biblioteca_admin/prestamos', [
    'titulo'   => 'Préstamos',
    'rolPanel' => 'bibliotecario',
    'alumnos'  => $alumnos,
    'profesores' => $profesores,
    'librosDisp' => $librosDisp,
    'librosTodos' => $librosTodos,
    'diasPrestamo' => $diasPrestamo,
    'activos'  => $prestamosActivos,
    'historial' => $historial,
    'errores'  => $errores,
    'vistaForm' => $_GET['vista'] ?? ($errores ? 'alta' : ''),
]);