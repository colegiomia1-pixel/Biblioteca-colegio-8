<?php
/**
 * Colegio8 — Catálogo de libros para el lector (consulta, favoritos, pedir).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('lector');
$lec = sess('lector');
[$col, $id] = lector_scope($lec);

/* ---------------- Acciones desde el catálogo ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        flash('error', 'La solicitud expiró.');
        redirect('biblioteca-lector/catalogo.php');
    }
    $accion = $_POST['accion'] ?? '';
    $libroId = (int) ($_POST['libro_id'] ?? 0);

    // Destino de vuelta (solo rutas internas del lector, evita open redirect).
    $volver = (string) ($_POST['volver'] ?? '');
    if (!preg_match('~^biblioteca-lector/[A-Za-z0-9_./?=&-]*$~', $volver)) {
        $volver = 'biblioteca-lector/catalogo.php';
    }
    // Con parámetros seguros si envuelve al libro que se acaba de operar.
    if ($accion === 'favorito' && isset($_POST['es_detalle'])) {
        $volver = 'biblioteca-lector/libro.php?id=' . $libroId;
    }

    // Visitante (cuenta sin postulación): lectura del catálogo sí, acciones no.
    if (!lector_tiene_persona($lec)) {
        flash('info', 'Completá tu solicitud de inscripción para guardar favoritos y pedir libros.');
        redirect($volver);
    }

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
        redirect($volver);
    }

    if ($accion === 'pedir') {
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        if (mb_strlen($motivo) > 500) {
            flash('error', 'El motivo es demasiado largo.');
            redirect('biblioteca-lector/catalogo.php');
        }
        // No duplicar pedidos pendientes del mismo libro y persona.
        $st = db()->prepare("SELECT id FROM biblioteca_peticiones WHERE libro_id = ? AND $col = ? AND estado = 'pendiente'");
        $st->execute([$libroId, $id]);
        if ($st->fetch()) {
            flash('info', 'Ya tenés un pedido pendiente de este libro.');
        } else {
            db()->prepare("INSERT INTO biblioteca_peticiones (tipo_persona, $col, libro_id, motivo) VALUES (?, ?, ?, ?)")
                ->execute([$lec['tipo'] === 'profesor' ? 'profesor' : 'alumno', $id, $libroId, $motivo ?: null]);
            flash('exito', 'Pedido enviado. El bibliotecario va a responderlo.');
        }
        redirect('biblioteca-lector/catalogo.php');
    }
}

$buscar = trim((string) ($_GET['buscar'] ?? ''));
$genero = $_GET['genero'] ?? '';

$where = ['l.activo = 1'];
$params = [];
if ($buscar !== '') {
    $patron = '%' . $buscar . '%';
    $where[] = '(l.titulo LIKE ? OR l.autor LIKE ? OR l.genero LIKE ? OR l.isbn LIKE ?)';
    array_push($params, $patron, $patron, $patron, $patron);
}
if ($genero !== '') {
    $where[] = 'l.genero = ?';
    $params[] = $genero;
}
$whereSql = implode(' AND ', $where);

$generos = db()->query("SELECT DISTINCT genero FROM biblioteca_libros WHERE genero IS NOT NULL AND genero <> '' AND activo = 1 ORDER BY genero")->fetchAll(PDO::FETCH_COLUMN);

$porPagina = 12;
$pag = max(1, (int) ($_GET['pagina'] ?? 1));
$offset = ($pag - 1) * $porPagina; // CORREGIDO AQUÍ

$stT = db()->prepare('SELECT COUNT(*) FROM biblioteca_libros l WHERE ' . $whereSql);
$stT->execute($params);
$totalPaginas = max(1, (int) ceil((int) $stT->fetchColumn() / $porPagina));

if ($pag > $totalPaginas) { 
    $pag = $totalPaginas; 
    $offset = ($pag - 1) * $porPagina; // CORREGIDO AQUÍ
}

$idSeguro = (int) $id; // scope interno (whitelisted col + id entero del servidor)
$st = db()->prepare(
    "SELECT l.*, (f.id IS NOT NULL) AS es_favorito,
            (SELECT COUNT(*) FROM biblioteca_peticiones pe WHERE pe.libro_id = l.id AND pe.$col = $idSeguro AND pe.estado = 'pendiente') AS pedido_pendiente
     FROM biblioteca_libros l
     LEFT JOIN biblioteca_favoritos f ON f.libro_id = l.id AND f.$col = $idSeguro
     WHERE " . $whereSql . "
     ORDER BY l.titulo LIMIT " . $porPagina . " OFFSET " . $offset
);
$st->execute($params);
$libros = $st->fetchAll();

$queryExtra = '?buscar=' . urlencode($buscar) . '&genero=' . urlencode($genero);

render('biblioteca_lector/catalogo', [
    'titulo'       => 'Catálogo · Biblioteca',
    'rolPanel'     => 'lector',
    'lec'          => $lec,
    'libros'       => $libros,
    'generos'      => $generos,
    'buscar'       => $buscar,
    'genero'       => $genero,
    'pag'          => $pag,
    'totalPaginas' => $totalPaginas,
    'queryExtra'   => $queryExtra,
]);