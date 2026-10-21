<?php
/** Vista: inicio del lector. */
$lec = $lec ?? [];
$prestamos = $prestamos ?? [];
$pendientes = $pendientes ?? 0;
$recomendados = $recomendados ?? [];
$novedades = $novedades ?? [];
$respuestas = $respuestas ?? [];
$alDia = !array_filter($prestamos, fn($p) => $p['estado'] === 'vencido');
?>
<div class="encabezado-pagina">
  <div>
    <h2>Hola, <?= e($lec['nombre'] ?? '') ?></h2>
    <p>Esto es lo que tenés prestado y lo que podés descubrir.</p>
  </div>
  <a class="btn btn-primario" href="<?= url('biblioteca-lector/catalogo.php') ?>">Buscar libros</a>
</div>

<?php if (empty($lec['persona_id'] ?? null)): ?>
  <div class="aviso-info" role="status">
    Estás entrando como visitante: podés consultar el catálogo. Para pedir libros y guardar favoritos
    completá tu <a href="<?= url('postulante/aplicacion.php') ?>">solicitud de inscripción</a>.
  </div>
<?php endif; ?>

<div class="rejilla rejilla-3">
  <div class="tarjeta tarjeta-stats <?= $alDia ? '' : 'destacada' ?>">
    <span class="stats-num" data-contar="<?= count($prestamos) ?>"><?= count($prestamos) ?></span>
    <span class="stats-etq">Préstamos activos / vencidos</span>
    <span class="stats-sub"><?= $alDia ? 'Todo al día' : '¡Tenés préstamos vencidos!' ?></span>
  </div>
  <div class="tarjeta tarjeta-stats">
    <span class="stats-num" data-contar="<?= (int) $pendientes ?>"><?= (int) $pendientes ?></span>
    <span class="stats-etq">Pedidos en espera</span>
    <span class="stats-sub"><a href="<?= url('biblioteca-lector/pedidos.php') ?>">Ver estado →</a></span>
  </div>
  <div class="tarjeta tarjeta-stats">
    <span class="stats-num" data-contar="<?= count($recomendados) ?>"><?= count($recomendados) ?></span>
    <span class="stats-etq">Para vos</span>
    <span class="stats-sub">Según tus intereses</span>
  </div>
</div>

<?php if ($prestamos): ?>
<div class="tarjeta" style="margin-top:1.2rem">
  <h3 class="titulo-seccion">Tus préstamos</h3>
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead><tr><th>Libro</th><th>Prestado</th><th>Devolver antes del</th><th>Estado</th></tr></thead>
      <tbody>
        <?php foreach ($prestamos as $p): $vencido = $p['estado'] === 'vencido'; ?>
          <tr class="<?= $vencido ? 'fila-vencido' : '' ?>">
            <td><strong><?= e($p['titulo']) ?></strong><br><span class="fila-media" style="font-size:.78rem"><?= e($p['codigo_interno']) ?></span></td>
            <td class="fila-media"><?= e(fecha_es($p['fecha_prestamo'])) ?></td>
            <td class="fila-media"><?= e(fecha_es($p['fecha_limite'])) ?></td>
            <td>
              <?php if ($vencido): ?>
                <span class="badge badge-rojo">Vencido hace <?= (int) $p['dias_vencido'] ?> día(s)</span>
                <div class="ayuda" style="font-size:.8rem">Devolvelo en la biblioteca.</div>
              <?php else: ?>
                <span class="badge badge-verde">En tu poder</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($respuestas): ?>
<div class="tarjeta" style="margin-top:1.2rem">
  <h3 class="titulo-seccion">Últimas respuestas a tus pedidos</h3>
  <ul class="lista-simple">
    <?php foreach ($respuestas as $r): ?>
      <li>
        <strong>«<?= e($r['titulo']) ?>»</strong>
        <span class="badge <?= $r['estado'] === 'aceptada' ? 'badge-verde' : 'badge-rojo' ?>" style="margin-left:.3rem"><?= e($r['estado']) ?></span>
        <span class="fila-media"> · <?= e(fecha_es($r['fecha_peticion'])) ?></span>
        <?php if ($r['motivo_rechazo']): ?><div class="ayuda"><?= e($r['motivo_rechazo']) ?></div><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="rejilla rejilla-2" style="margin-top:1.2rem">
  <div class="tarjeta">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h3 class="titulo-seccion" style="margin:0">Recomendados para vos</h3>
      <a class="btn btn-fantasma btn-pequeno" href="<?= url('biblioteca-lector/favoritos.php') ?>">Mis favoritos</a>
    </div>
    <?php if (!$recomendados): ?>
      <p class="ayuda">Marcá favoritos y vas a ver sugerencias según tus géneros.</p>
    <?php endif; ?>
    <div class="red-catalogo red-catalogo-3">
      <?php foreach ($recomendados as $l): ?>
        <article class="libro-tarjeta">
          <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-portada enlace-portada">
            <?= portada_libro_contenido($l) ?>
          </a>
          <div class="libro-cuerpo">
            <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-titulo"><?= e($l['titulo']) ?></a>
            <div class="libro-autor"><?= e($l['autor']) ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="tarjeta">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h3 class="titulo-seccion" style="margin:0">Novedades en el catálogo</h3>
      <a class="btn btn-fantasma btn-pequeno" href="<?= url('biblioteca-lector/catalogo.php') ?>">Ver todo</a>
    </div>
    <div class="red-catalogo red-catalogo-3">
      <?php foreach ($novedades as $l): ?>
        <article class="libro-tarjeta">
          <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-portada enlace-portada">
            <?= portada_libro_contenido($l) ?>
          </a>
          <div class="libro-cuerpo">
            <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $l['id']) ?>" class="libro-titulo"><?= e($l['titulo']) ?></a>
            <div class="libro-autor"><?= e($l['autor']) ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</div>