<?php
/** Vista: mis favoritos + recomendaciones (lector). */
$favoritos = $favoritos ?? [];
$recomendados = $recomendados ?? [];
$generosFav = $generosFav ?? [];
?>
<div class="encabezado-pagina">
  <div>
    <h2>Mis favoritos</h2>
    <p><?= count($favoritos) ?> libro(s) guardados. Según tus gustos, te sugerimos más.</p>
  </div>
</div>

<?php if ($generosFav): ?>
  <div style="margin-bottom:1rem;display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
    <span class="ayuda">Tus géneros:</span>
    <?php foreach ($generosFav as $g): ?><span class="badge badge-verde"><?= e($g) ?></span><?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$favoritos && !$recomendados): ?>
  <div class="aviso-info">Todavía no marcaste favoritos. Tocá «☆ Guardar» en cualquier libro del <a href="<?= url('biblioteca-lector/catalogo.php') ?>">catálogo</a>.</div>
<?php endif; ?>

<?php if ($favoritos): ?>
<div class="red-catalogo">
  <?php foreach ($favoritos as $l): ?>
    <article class="libro-tarjeta">
      <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-portada enlace-portada">
        <?= portada_libro_contenido($l) ?>
      </a>
      <div class="libro-cuerpo">
        <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-titulo"><?= e($l['titulo']) ?></a>
        <div class="libro-autor"><?= e($l['autor']) ?></div>
        <form method="post" style="margin-top:.5rem" data-confirm="¿Quitar de favoritos?">
          <?= csrf_field() ?>
          <input type="hidden" name="libro_id" value="<?= (int) $l['id'] ?>">
          <button class="btn btn-pequeno btn-fantasma" type="submit">★ Quitar</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($recomendados): ?>
<div class="tarjeta" style="margin-top:1.2rem">
  <h3 class="titulo-seccion">Recomendaciones para vos</h3>
  <div class="red-catalogo red-catalogo-4">
    <?php foreach ($recomendados as $l): ?>
      <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-tarjeta enlace-portada">
        <div class="libro-portada">
          <?= portada_libro_contenido($l) ?>
        </div>
        <div class="libro-cuerpo">
          <span class="libro-titulo" style="font-size:.9rem"><?= e($l['titulo']) ?></span>
          <span class="libro-autor" style="font-size:.8rem"><?= e($l['autor']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>