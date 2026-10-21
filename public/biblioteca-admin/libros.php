<?php
/**
 * Colegio8 — Catálogo de libros (alta, edición, baja y búsqueda).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('bibliotecario');

/** Ejemplares actualmente fuera (activos o vencidos). */
function libros_ocupados(int $libroId): int
{
    static $c = [];
    if (!isset($c[$libroId])) {
        $st = db()->prepare("SELECT COUNT(*) FROM biblioteca_prestamos WHERE libro_id = ? AND estado IN ('activo','vencido')");
        $st->execute([$libroId]);
        $c[$libroId] = (int) $st->fetchColumn();
    }
    return $c[$libroId];
}

$buscar = trim((string) ($_GET['buscar'] ?? ''));
$genero = $_GET['genero'] ?? '';
$estado = $_GET['estado'] ?? '';        // 'activo' | 'inactivo'
$disp   = $_GET['disponibilidad'] ?? ''; // 'disponibles' | 'agotados'

$where = ['1=1'];
$params = [];
if ($buscar !== '') {
    $where[] = '(l.titulo LIKE ? OR l.autor LIKE ? OR l.isbn LIKE ? OR l.codigo_interno LIKE ?)';
    $bus = '%' . $buscar . '%';
    array_push($params, $bus, $bus, $bus, $bus);
}
if ($genero !== '') {
    $where[] = 'l.genero = ?';
    $params[] = $genero;
}
if ($estado === 'activo') { $where[] = 'l.activo = 1'; }
if ($estado === 'inactivo') { $where[] = 'l.activo = 0'; }
if ($disp === 'disponibles') { $where[] = 'l.ejemplares_disponibles > 0'; }
if ($disp === 'agotados') { $where[] = 'l.ejemplares_disponibles = 0'; }
$whereSql = implode(' AND ', $where);

$generos = db()->query('SELECT DISTINCT genero FROM biblioteca_libros WHERE genero IS NOT NULL AND genero <> "" ORDER BY genero')->fetchAll(PDO::FETCH_COLUMN);

$porPagina = 16;
$pag = max(1, (int) ($_GET['pagina'] ?? 1));
$offset = ($pag - 1) * $porPagina;

$stT = db()->prepare('SELECT COUNT(*) FROM biblioteca_libros l WHERE ' . $whereSql);
$stT->execute($params);
$total = (int) $stT->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pag > $totalPaginas) { $pag = $totalPaginas; $offset = ($pag - 1) * $porPagina; }

$st = db()->prepare('SELECT l.* FROM biblioteca_libros l WHERE ' . $whereSql . ' ORDER BY l.titulo LIMIT ' . $porPagina . ' OFFSET ' . $offset);
$st->execute($params);
$libros = $st->fetchAll();
foreach ($libros as &$l) {
    $l['ocupados'] = libros_ocupados((int) $l['id']);
}
unset($l);

/* ---------------- Alta / edición ---------------- */
$vistaForm = $_GET['vista'] ?? '';
$editando = null;
if ($vistaForm === 'editar') {
    $stE = db()->prepare('SELECT * FROM biblioteca_libros WHERE id = ?');
    $stE->execute([(int) ($_GET['id'] ?? 0)]);
    $editando = $stE->fetch();
    if (!$editando) {
        flash('error', 'El libro indicado no existe.');
        redirect('biblioteca-admin/libros.php');
    }
    // disponibles sugerido = ocupados restados del total.
    $editando['ocupados'] = libros_ocupados((int) $editando['id']);
}

$valores = [
    'titulo' => $_POST['titulo'] ?? ($editando['titulo'] ?? ''),
    'autor' => $_POST['autor'] ?? ($editando['autor'] ?? ''),
    'editorial' => $_POST['editorial'] ?? ($editando['editorial'] ?? ''),
    'genero' => $_POST['genero'] ?? ($editando['genero'] ?? ''),
    'isbn' => $_POST['isbn'] ?? ($editando['isbn'] ?? ''),
    'codigo_interno' => $_POST['codigo_interno'] ?? ($editando['codigo_interno'] ?? ''),
    'sinopsis' => $_POST['sinopsis'] ?? ($editando['sinopsis'] ?? ''),
    'ejemplares_total' => $_POST['ejemplares_total'] ?? ($editando['ejemplares_total'] ?? 1),
];

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró.';
    } else {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'baja' || $accion === 'reactivar') {
            $id = (int) ($_POST['id'] ?? 0);
            $nuevo = $accion === 'baja' ? 0 : 1;
            db()->prepare('UPDATE biblioteca_libros SET activo = ? WHERE id = ?')->execute([$nuevo, $id]);
            flash('exito', $accion === 'baja' ? 'Libro dado de baja (desactivado).' : 'Libro reactivado.');
            redirect('biblioteca-admin/libros.php');
        }

        if (mb_strlen(trim($valores['titulo'])) < 2) $errores[] = 'Ingresá el título.';
        if (mb_strlen(trim($valores['autor'])) < 2) $errores[] = 'Ingresá el autor.';
        if (!$valores['codigo_interno']) $errores[] = 'Ingresá el código interno.';
        $total = (int) $valores['ejemplares_total'];
        if ($total < 1 || $total > 500) $errores[] = 'La cantidad de ejemplares debe estar entre 1 y 500.';

        if (!$errores) {
            $dup = db()->prepare('SELECT id FROM biblioteca_libros WHERE (codigo_interno = ? OR (isbn IS NOT NULL AND isbn <> "" AND isbn = ?)) AND id <> ?');
            $dup->execute([$valores['codigo_interno'], $valores['isbn'], (int) ($editando['id'] ?? 0)]);
            if ($dup->fetch()) {
                $errores[] = 'Ya existe un libro con ese código interno o ISBN.';
            }
        }

        if (!$errores) {
            // Portada opcional
            $portada = $editando['portada'] ?? null;
            if (!empty($_FILES['portada']['name'])) {
                $errSub = [];
                $ruta = subir_archivo('portada', 'libros', $errSub, null, ['jpg', 'jpeg', 'png']);
                if ($ruta) {
                    if ($portada) borrar_archivo($portada);
                    $portada = $ruta;
                } else {
                    $errores[] = $errSub[0] ?? 'No se pudo guardar la portada.';
                }
            }

            $ocupados = $editando ? libros_ocupados((int) $editando['id']) : 0;
            if ($total < $ocupados) {
                $errores[] = 'No podés bajar los ejemplares a ' . $total . ' porque ' . $ocupados . ' están prestados.';
            } else {
                $disponibles = $total - $ocupados;
                if ($accion === 'crear') {
                    $st = db()->prepare(
                        'INSERT INTO biblioteca_libros (titulo, autor, editorial, genero, isbn, codigo_interno, portada, sinopsis, ejemplares_total, ejemplares_disponibles)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $st->execute([
                        $valores['titulo'], $valores['autor'], $valores['editorial'] ?: null, $valores['genero'] ?: null,
                        $valores['isbn'] ?: null, $valores['codigo_interno'], $portada, $valores['sinopsis'] ?: null,
                        $total, $disponibles,
                    ]);
                    flash('exito', 'Libro agregado al catálogo.');
                } else {
                    $st = db()->prepare(
                        'UPDATE biblioteca_libros SET titulo = ?, autor = ?, editorial = ?, genero = ?, isbn = ?,
                                codigo_interno = ?, portada = ?, sinopsis = ?, ejemplares_total = ?, ejemplares_disponibles = ?
                         WHERE id = ?'
                    );
                    $st->execute([
                        $valores['titulo'], $valores['autor'], $valores['editorial'] ?: null, $valores['genero'] ?: null,
                        $valores['isbn'] ?: null, $valores['codigo_interno'], $portada, $valores['sinopsis'] ?: null,
                        $total, $disponibles, (int) $editando['id'],
                    ]);
                    flash('exito', 'Libro actualizado.');
                }
                redirect('biblioteca-admin/libros.php');
            }
        }
    }
}

/******** Limpieza de filtros para paginación ********/
$queryExtra = '?buscar=' . urlencode($buscar) . '&genero=' . urlencode($genero) . '&estado=' . urlencode($estado) . '&disponibilidad=' . urlencode($disp);

render('biblioteca_admin/libros', [
    'titulo'       => 'Libros',
    'rolPanel'     => 'bibliotecario',
    'libros'       => $libros,
    'generos'      => $generos,
    'filtros'      => ['buscar' => $buscar, 'genero' => $genero, 'estado' => $estado, 'disp' => $disp],
    'pag'          => $pag,
    'totalPaginas' => $totalPaginas,
    'queryExtra'   => $queryExtra,
    'vistaForm'  => $vistaForm,
    'editando'     => $editando,
    'valores'      => $valores,
    'errores'      => $errores,
]);