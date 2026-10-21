<?php
/**
 * Colegio8 — Pedidos de libros (peticiones) de alumnos y profesores.
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('bibliotecario');

db()->query("UPDATE biblioteca_prestamos SET estado = 'vencido' WHERE estado = 'activo' AND fecha_limite < CURDATE()");

$pendientes = db()->query(
    "SELECT p.*, l.titulo, l.codigo_interno,
            CASE p.tipo_persona WHEN 'alumno' THEN CONCAT(a.apellido, ', ', a.nombre)
                                ELSE CONCAT(bp.apellido, ', ', bp.nombre) END AS persona,
            p.fecha_peticion AS fecha_pedido, p.motivo
     FROM biblioteca_peticiones p
     JOIN biblioteca_libros l ON l.id = p.libro_id
     LEFT JOIN alumnos a ON a.id = p.alumno_id
     LEFT JOIN biblioteca_profesores bp ON bp.id = p.profesor_id
     WHERE p.estado = 'pendiente'
     ORDER BY p.fecha_peticion, p.id"
)->fetchAll();

$resueltas = db()->query(
    "SELECT p.*, p.fecha_peticion AS fecha_pedido, l.titulo,
            CASE p.tipo_persona WHEN 'alumno' THEN CONCAT(a.apellido, ', ', a.nombre)
                                ELSE CONCAT(bp.apellido, ', ', bp.nombre) END AS persona
     FROM biblioteca_peticiones p
     JOIN biblioteca_libros l ON l.id = p.libro_id
     LEFT JOIN alumnos a ON a.id = p.alumno_id
     LEFT JOIN biblioteca_profesores bp ON bp.id = p.profesor_id
     WHERE p.estado IN ('aceptada','rechazada')
     ORDER BY p.id DESC LIMIT 30"
)->fetchAll();

$librosReserva = db()->query(
    'SELECT id, titulo, autor, ejemplares_disponibles FROM biblioteca_libros
     WHERE activo = 1 AND ejemplares_disponibles > 0 ORDER BY titulo'
)->fetchAll();

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró.';
    } else {
        $accion = $_POST['accion'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);

        if ($accion === 'aceptar') {
            $libroManual = (int) ($_POST['libro_id'] ?? 0);
            $fechaLim = $_POST['fecha_limite'] ?? '';

            db()->beginTransaction();
            try {
                $st = db()->prepare('SELECT * FROM biblioteca_peticiones WHERE id = ? AND estado = ? FOR UPDATE');
                $st->execute([$id, 'pendiente']);
                $ped = $st->fetch();
                if (!$ped) throw new RuntimeException('El pedido ya fue resuelto.');

                // El libro que origina el pedido o uno elegido como reemplazo.
                $libroId = $libroManual ?: (int) $ped['libro_id'];
                $stL = db()->prepare('SELECT id, titulo, ejemplares_disponibles FROM biblioteca_libros WHERE id = ? AND activo = 1 FOR UPDATE');
                $stL->execute([$libroId]);
                $lib = $stL->fetch();
                if (!$lib) throw new RuntimeException('El libro no existe o está inactivo.');
                if ((int) $lib['ejemplares_disponibles'] <= 0) throw new RuntimeException('No quedan ejemplares disponibles de «' . $lib['titulo'] . '».');
                if (!strtotime($fechaLim)) throw new RuntimeException('La fecha límite es obligatoria.');

                $fechaPre = hoy_iso();
                if ($ped['tipo_persona'] === 'alumno') {
                    $stNew = db()->prepare('INSERT INTO biblioteca_prestamos (tipo_persona, alumno_id, profesor_id, libro_id, fecha_prestamo, fecha_limite)
                                   VALUES (?, ?, NULL, ?, ?, ?)');
                    $stNew->execute(['alumno', (int) $ped['alumno_id'], $libroId, $fechaPre, $fechaLim]);
                } else {
                    $stNew = db()->prepare('INSERT INTO biblioteca_prestamos (tipo_persona, alumno_id, profesor_id, libro_id, fecha_prestamo, fecha_limite)
                                   VALUES (?, NULL, ?, ?, ?, ?)');
                    $stNew->execute(['profesor', (int) $ped['profesor_id'], $libroId, $fechaPre, $fechaLim]);
                }
                $idPrestamo = (int) db()->lastInsertId();
                db()->prepare('UPDATE biblioteca_libros SET ejemplares_disponibles = ejemplares_disponibles - 1 WHERE id = ?')->execute([$libroId]);
                db()->prepare("UPDATE biblioteca_peticiones SET estado = 'aceptada', libro_id = ?, prestamo_id = ?, motivo_rechazo = NULL WHERE id = ?")
                    ->execute([$libroId, $idPrestamo, $id]);
                db()->commit();
                flash('exito', 'Pedido aceptado: se generó el préstamo de «' . $lib['titulo'] . '».');
                redirect('biblioteca-admin/pedidos.php');
            } catch (Throwable $t) {
                db()->rollBack();
                $errores[] = $t->getMessage();
            }
        } elseif ($accion === 'rechazar') {
            $motivo = trim((string) ($_POST['motivo_rechazo'] ?? ''));
            if (mb_strlen($motivo) < 5) {
                $errores[] = 'Ingresá un motivo explicando el rechazo.';
            } else {
                db()->prepare("UPDATE biblioteca_peticiones SET estado = 'rechazada', motivo_rechazo = ? WHERE id = ? AND estado = 'pendiente'")
                    ->execute([$motivo, $id]);
                flash('exito', 'Pedido rechazado. La persona recibirá el motivo en su historial.');
                redirect('biblioteca-admin/pedidos.php');
            }
        }
    }
}

render('biblioteca_admin/pedidos', [
    'titulo'   => 'Pedidos',
    'rolPanel' => 'bibliotecario',
    'pendientes' => $pendientes,
    'resueltas'  => $resueltas,
    'librosReserva' => $librosReserva,
    'errores'   => $errores,
    'diasPrestamo' => (int) (valor_config('dias_prestamo', '15') ?? 15),
]);