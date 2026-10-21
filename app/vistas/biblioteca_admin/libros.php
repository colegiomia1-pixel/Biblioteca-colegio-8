<?php
/** Vista: catálogo de libros (bibliotecario). */
$libros       = $libros ?? [];
$filtros      = $filtros ?? [];
$pag          = $pag ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$queryExtra   = $queryExtra ?? '';
$vistaForm    = $vistaForm ?? '';
$editando     = $editando ?? null;
$valores      = $valores ?? [];
$errores      = $errores ?? [];
$generos      = $generos ?? [];
?>

<?php if ($vistaForm === 'alta' || $editando): ?>
<div class="tarjeta mb-4 border-0 shadow-sm rounded-4 p-4">
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-journal-plus text-success fs-4"></i>
    <h3 class="titulo-seccion m-0 fw-bold text-dark"><?= $editando ? 'Editar libro' : 'Nuevo libro' ?></h3>
  </div>
  
  <form method="post" enctype="multipart/form-data" class="formulario formulario-amplio" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int) $editando['id'] ?>"><?php endif; ?>

    <?php if ($errores): ?>
      <div class="aviso-error rounded-3 p-3 mb-3" role="alert">
        <strong><i class="bi bi-exclamation-circle-fill me-1"></i> Revisá estos datos:</strong>
        <ul style="margin:.4rem 0 0 1.1rem;padding:0">
          <?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="fila-campos">
      <div class="campo">
        <label for="titulo">Título <span class="requerido">*</span></label>
        <input type="text" id="titulo" name="titulo" required value="<?= e($valores['titulo'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="autor">Autor <span class="requerido">*</span></label>
        <input type="text" id="autor" name="autor" required value="<?= e($valores['autor'] ?? '') ?>">
      </div>
    </div>
    <div class="fila-campos">
      <div class="campo">
        <label for="editorial">Editorial</label>
        <input type="text" id="editorial" name="editorial" value="<?= e($valores['editorial'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="genero">Género</label>
        <input type="text" id="genero" name="genero" list="listaGeneros" value="<?= e($valores['genero'] ?? '') ?>" placeholder="Novela, Ensayo…">
        <datalist id="listaGeneros">
          <?php foreach ($generos as $g): ?><option value="<?= e($g) ?>"><?php endforeach; ?>
        </datalist>
      </div>
    </div>
    <div class="fila-campos">
      <div class="campo">
        <label for="isbn">ISBN</label>
        <input type="text" id="isbn" name="isbn" value="<?= e($valores['isbn'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="codigo_interno">Código interno <span class="requerido">*</span></label>
        <input type="text" id="codigo_interno" name="codigo_interno" required value="<?= e($valores['codigo_interno'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="ejemplares_total">Ejemplares totales <span class="requerido">*</span></label>
        <input type="number" id="ejemplares_total" name="ejemplares_total" min="1" max="500" required
               value="<?= (int) ($valores['ejemplares_total'] ?? 1) ?>">
        <?php if ($editando && (int) ($editando['ocupados'] ?? 0)): ?>
          <p class="ayuda text-danger mt-1"><i class="bi bi-info-circle me-1"></i><?= (int) $editando['ocupados'] ?> ejemplar(es) están prestados: no puede bajar de esa cifra.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="campo">
      <label for="sinopsis">Sinopsis</label>
      <textarea id="sinopsis" name="sinopsis" rows="3"><?= e($valores['sinopsis'] ?? '') ?></textarea>
    </div>
    <div class="campo">
      <label for="portada">Portada (imagen, opcional)</label>
      <input type="file" id="portada" name="portada" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
      <p class="ayuda">JPG o PNG. Máximo <?= e(valor_config('limite_archivo_mb')) ?> MB.</p>
      <?php if ($editando && !empty($editando['portada'])): ?>
        <div style="display:flex;align-items:center;gap:.6rem;margin-top:.4rem">
          <img src="<?= url('libro-portada.php?id=' . (int) $editando['id']) ?>" alt="Portada actual" style="width:56px;height:72px;object-fit:contain;background:var(--verde-suave);border-radius:6px">
          <span class="ayuda">Portada actual. Elegí un archivo para reemplazarla.</span>
        </div>
      <?php endif; ?>
    </div>
    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-primario rounded-3" type="submit">
        <i class="bi bi-check-lg me-1"></i> <?= $editando ? 'Guardar cambios' : 'Crear libro' ?>
      </button>
      <a class="btn btn-fantasma rounded-3" href="<?= url('biblioteca-admin/libros.php') ?>">Cancelar</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Encabezado de página -->
<div class="encabezado-pagina mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
  <div>
    <h2 class="fw-bold text-dark mb-1"><i class="bi bi-book-half text-success me-2"></i>Catálogo de Libros</h2>
    <p class="text-secondary mb-0">
      <a href="<?= url('biblioteca-admin/reportes.php?tipo=inventario') ?>" class="text-decoration-none">
        <i class="bi bi-file-earmark-text me-1"></i> Ver inventario general →
      </a>
    </p>
  </div>
  <a class="btn btn-primario rounded-3 px-3 py-2 fw-semibold" href="<?= url('biblioteca-admin/libros.php?vista=alta') ?>">
    <i class="bi bi-plus-circle me-1"></i> + Nuevo libro
  </a>
</div>

<!-- Filtros de búsqueda -->
<form method="get" class="tarjeta p-3 mb-4 border-0 shadow-sm rounded-4 d-flex gap-2 flex-wrap align-items-end">
  <div class="campo" style="flex:1;min-width:200px">
    <label for="buscar" class="fw-semibold text-secondary small">Buscar por título, autor, ISBN o código</label>
    <input type="text" id="buscar" name="buscar" value="<?= e($filtros['buscar'] ?? '') ?>" placeholder="Escribí aquí...">
  </div>
  <div class="campo">
    <label for="genero" class="fw-semibold text-secondary small">Género</label>
    <select id="genero" name="genero">
      <option value="">Todos</option>
      <?php foreach ($generos as $g): ?>
        <option value="<?= e($g) ?>" <?= ($filtros['genero'] ?? '') === $g ? 'selected' : '' ?>><?= e($g) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo">
    <label for="estado" class="fw-semibold text-secondary small">Estado</label>
    <select id="estado" name="estado">
      <option value="">Todos</option>
      <option value="activo" <?= ($filtros['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activos</option>
      <option value="inactivo" <?= ($filtros['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>De baja</option>
    </select>
  </div>
  <div class="campo">
    <label for="disponibilidad" class="fw-semibold text-secondary small">Disponibilidad</label>
    <select id="disponibilidad" name="disponibilidad">
      <option value="">Todas</option>
      <option value="disponibles" <?= ($filtros['disp'] ?? '') === 'disponibles' ? 'selected' : '' ?>>Con ejemplares disponibles</option>
      <option value="agotados" <?= ($filtros['disp'] ?? '') === 'agotados' ? 'selected' : '' ?>>Todos prestados</option>
    </select>
  </div>
  <div class="acciones-fila ms-auto">
    <a class="btn btn-fantasma rounded-3" href="<?= url('biblioteca-admin/libros.php') ?>">Limpiar</a>
    <button class="btn btn-primario rounded-3" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
  </div>
</form>

<?php if (!$libros): ?>
  <div class="aviso-info rounded-4 p-4 text-center my-4">
    <i class="bi bi-info-circle display-6 d-block mb-2 opacity-50"></i>
    No se encontraron libros que coincidan con la búsqueda o los filtros aplicados.
  </div>
<?php endif; ?>

<!-- Grid del Catálogo de Libros -->
<div class="red-catalogo">
  <?php foreach ($libros as $l): ?>
    <article class="libro-tarjeta" style="<?= $l['activo'] ? '' : 'opacity:.55' ?>">
      <div class="libro-portada">
        <?= portada_libro_contenido($l) ?>
      </div>
      <div class="libro-cuerpo p-3 d-flex flex-column">
        <h3 class="libro-titulo text-truncate mb-1 fw-bold"><?= e($l['titulo']) ?></h3>
        <div class="libro-autor text-secondary mb-2 d-flex align-items-center justify-content-between">
          <span class="text-truncate me-1"><?= e($l['autor']) ?></span>
          <?php if (!empty($l['genero'])): ?>
            <span class="badge badge-gris ms-1"><?= e($l['genero']) ?></span>
          <?php endif; ?>
        </div>
        
        <div class="libro-pie border-top pt-2 mt-auto d-flex justify-content-between align-items-center">
          <span class="disp <?= (int) ($l['ejemplares_disponibles'] ?? 0) > 0 ? 'disp-si text-success fw-bold' : 'disp-no text-danger fw-bold' ?>">
            <i class="bi <?= (int) ($l['ejemplares_disponibles'] ?? 0) > 0 ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-1"></i>
            <?= (int) ($l['ejemplares_disponibles'] ?? 0) ?> disp. / <?= (int) ($l['ejemplares_total'] ?? 0) ?>
          </span>
          <span class="ayuda font-monospace small"><?= e($l['codigo_interno'] ?? '') ?></span>
        </div>

        <div class="acciones-fila mt-3 pt-2 border-top d-flex gap-1 justify-content-between">
          <a class="btn btn-pequeno btn-fantasma rounded-2" href="<?= url('biblioteca-admin/libros.php?vista=editar&id=' . (int) $l['id']) ?>" title="Editar libro">
            <i class="bi bi-pencil me-1"></i> Editar
          </a>
          
          <?php if ($l['activo']): ?>
            <form method="post" style="display:inline" data-confirm="¿Dar de baja «<?= e($l['titulo']) ?>»?">
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="baja">
              <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
              <button class="btn btn-pequeno btn-peligro rounded-2" type="submit">
                <i class="bi bi-trash me-1"></i> Baja
              </button>
            </form>
          <?php else: ?>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="reactivar">
              <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
              <button class="btn btn-pequeno btn-fantasma rounded-2" type="submit">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reactivar
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<!-- Paginador nativo -->
<div class="mt-4">
  <?= html_paginador('biblioteca-admin/libros.php' . $queryExtra, $pag, $totalPaginas) ?>
</div>