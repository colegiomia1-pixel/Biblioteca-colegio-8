<?php
/** Vista: catálogo del lector. */
$lec = $lec ?? [];
$visita = empty($lec['persona_id'] ?? null);
$libros = $libros ?? [];
$generos = $generos ?? [];
$buscar = $buscar ?? '';
$genero = $genero ?? '';
$pag = $pag ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$queryExtra = $queryExtra ?? '';
?>
<div class="encabezado-pagina">
  <div>
    <h2>Catálogo de la biblioteca</h2>
    <p>Explorá, guardá favoritos y pedí libros. La consulta no modifica nada: los pedidos van al bibliotecario.</p>
  </div>
</div>

<?php if ($visita): ?>
  <div class="aviso-info" role="status">
    Estás explorando el catálogo como visitante. Para guardar favoritos y pedir libros
    completá tu <a href="<?= url('postulante/aplicacion.php') ?>">solicitud de inscripción</a>.
  </div>
<?php endif; ?>

<form method="get" class="tarjeta" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:end;margin-bottom:1.1rem">
  <div class="campo" style="flex:1;min-width:220px">
    <label for="buscar">Buscar por título, autor, género o ISBN</label>
    <input type="text" id="buscar" name="buscar" value="<?= e($buscar) ?>">
  </div>
  <div class="campo">
    <label for="genero">Género</label>
    <select id="genero" name="genero">
      <option value="">Todos</option>
      <?php foreach ($generos as $g): ?>
        <option value="<?= e($g) ?>" <?= $g === $genero ? 'selected' : '' ?>><?= e($g) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="acciones-fila" style="align-self:center">
    <a class="btn btn-fantasma" href="<?= url('biblioteca-lector/catalogo.php') ?>">Limpiar</a>
    <button class="btn btn-primario" type="submit">Buscar</button>
  </div>
</form>

<?php if (!$libros): ?>
  <div class="aviso-info">No se encontraron libros para esa búsqueda.</div>
<?php endif; ?>

<div class="red-catalogo">
  <?php foreach ($libros as $l): ?>
    <article class="libro-tarjeta">
      <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-portada enlace-portada">
        <?= portada_libro_contenido($l) ?>
      </a>
      <div class="libro-cuerpo">
        <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-titulo"><?= e($l['titulo']) ?></a>
        <div class="libro-autor"><?= e($l['autor']) ?>
          <?php if ($l['genero']): ?><span class="badge badge-gris" style="margin-left:.3rem"><?= e($l['genero']) ?></span><?php endif; ?>
        </div>
        <div class="libro-pie">
          <span class="disp <?= (int) $l['ejemplares_disponibles'] > 0 ? 'disp-si' : 'disp-no' ?>">
            <?= (int) $l['ejemplares_disponibles'] > 0 ? 'Disponible' : 'Sin ejemplares' ?>
          </span>
        </div>
        <div class="acciones-fila" style="margin-top:.5rem">
          <?php if ($visita): ?>
            <span class="ayuda">Favoritos y pedidos: requiere la solicitud de inscripción</span>
          <?php else: ?>
          <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="favorito">
            <input type="hidden" name="libro_id" value="<?= (int) $l['id'] ?>">
            <input type="hidden" name="volver" value="biblioteca-lector/catalogo.php">
            <button class="btn btn-pequeno <?= $l['es_favorito'] ? 'btn-ambar' : 'btn-fantasma' ?>" type="submit">
              <?= $l['es_favorito'] ? '★ Favorito' : '☆ Guardar' ?>
            </button>
          </form>
          <a class="btn btn-pequeno btn-primario" href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>">
            <?= $l['pedido_pendiente'] ? 'Pedido enviado' : 'Pedir' ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?= html_paginador('biblioteca-lector/catalogo.php' . $queryExtra, $pag, $totalPaginas) ?>