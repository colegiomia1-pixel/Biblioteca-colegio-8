<?php
/** Vista: mis pedidos (lector). */
$pedidos = $pedidos ?? [];
?>
<div class="encabezado-pagina">
  <div>
    <h2>Mis pedidos de libros</h2>
    <p>Estado de cada solicitud que enviaste al bibliotecario.</p>
  </div>
</div>

<?php if (!$pedidos): ?>
  <div class="aviso-info">No hiciste pedidos todavía. <a href="<?= url('biblioteca-lector/catalogo.php') ?>">Explorá el catálogo →</a></div>
<?php endif; ?>

<div class="rejilla rejilla-1">
  <?php foreach ($pedidos as $pe): ?>
    <div class="tarjeta">
      <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;justify-content:space-between">
        <div style="display:flex;gap:1rem;align-items:center">
          <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $pe['libro_id']) ?>" class="libro-portada libro-portada-mini">
            <?= portada_libro_contenido(['id' => (int) $pe['libro_id'], 'titulo' => $pe['titulo'], 'portada' => $pe['portada']]) ?>
          </a>
          <div>
            <a class="libro-titulo" href="<?= url('biblioteca-lector/libro.php?id=' . (int) $pe['libro_id']) ?>"><?= e($pe['titulo']) ?></a>
            <div class="fila-media"><?= e($pe['autor']) ?> · Pedido el <?= e(fecha_es($pe['fecha_peticion'])) ?></div>
            <?php if ($pe['motivo']): ?><div class="fila-media" style="font-size:.85rem">Motivo: <?= e($pe['motivo']) ?></div><?php endif; ?>
          </div>
        </div>
        <div style="text-align:right">
          <?php
            $infoPed = [
                'pendiente' => ['badge-ambar', 'En espera', 'El bibliotecario aún no lo responde.'],
                'aceptada'  => ['badge-verde', 'Aceptado', 'Se generó tu préstamo: miralo en tu historial y pasá a retirarlo.'],
                'rechazada' => ['badge-rojo', 'Rechazado', $pe['motivo_rechazo'] ?? 'Sin motivo informado.'],
            ][$pe['estado']] ?? ['badge-gris', $pe['estado'], ''];
          ?>
          <div><span class="badge <?= $infoPed[0] ?>"><?= $infoPed[1] ?></span></div>
          <div class="ayuda" style="max-width:300px"><?= e($infoPed[2]) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>