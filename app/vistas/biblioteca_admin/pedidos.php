<?php
/** Vista: pedidos de libros (bibliotecario). */
$pendientes = $pendientes ?? [];
$resueltas  = $resueltas ?? [];
$librosReserva = $librosReserva ?? [];
$errores = $errores ?? [];
$diasP = $diasPrestamo ?? 15;
?>
<div class="encabezado-pagina">
  <div>
    <h2>Pedidos de libros</h2>
    <p>Los lectores piden libros desde su perfil; acá se aceptan (generando el préstamo) o se rechazan con motivo.</p>
  </div>
  <a class="btn btn-primario" href="<?= url('biblioteca-admin/prestamos.php?vista=alta') ?>">+ Préstamo directo</a>
</div>

<?php if ($errores): ?>
  <div class="aviso-error" role="alert">
    <ul style="margin:0;padding-left:1.1rem"><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="pedido-marco">
  <div class="pedido-resumen">
    <h3 class="titulo-seccion" style="margin:0">Pedidos pendientes (<?= count($pendientes) ?>)</h3>
    <span class="ayuda">Primero los más antiguos · <strong><?= count($pendientes) ?></strong> sin responder</span>
  </div>
  <?php if (!$pendientes): ?>
    <div class="aviso-info">No hay pedidos en espera.</div>
  <?php endif; ?>
  <div class="pedidos-lista">
    <?php foreach ($pendientes as $ped): ?>
      <article class="tarjeta pedido-tarjeta">
        <div class="pedido-cabecera">
          <span class="pedido-quien"><strong><?= e($ped['persona']) ?></strong><span class="badge badge-ambar">En espera</span></span>
          <span class="pedido-fecha">Pedido el <?= e(fecha_es($ped['fecha_pedido'])) ?></span>
        </div>
        <div class="pedido-libro">
          Solicitó <strong>«<?= e($ped['titulo']) ?>»</strong>
          <?php if ($ped['codigo_interno']): ?><span class="pedido-codigo"><?= e($ped['codigo_interno']) ?></span><?php endif; ?>
        </div>
        <?php if (!empty($ped['motivo'])): ?>
          <div class="pedido-nota">Nota: <?= e($ped['motivo']) ?></div>
        <?php endif; ?>
        <div class="pedido-acciones">
          <details class="pedido-expandir">
            <summary class="btn btn-pequeno btn-primario">Aceptar (genera préstamo)</summary>
            <form method="post" class="formulario" novalidate>
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="aceptar">
              <input type="hidden" name="id" value="<?= (int) $ped['id'] ?>">
              <div class="campo">
                <label for="libro_id_<?= (int) $ped['id'] ?>">Libro a entregar (por defecto el solicitado)</label>
                <select id="libro_id_<?= (int) $ped['id'] ?>" name="libro_id">
                  <option value="">«<?= e($ped['titulo']) ?>»</option>
                  <?php foreach ($librosReserva as $l): ?>
                    <?php if ((int) $l['id'] === (int) $ped['libro_id']) continue; ?>
                    <option value="<?= (int) $l['id'] ?>"><?= e($l['titulo']) ?> (<?= (int) $l['ejemplares_disponibles'] ?> disp.)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="campo">
                <label for="fechaLim_<?= (int) $ped['id'] ?>">Devolución límite</label>
                <input type="date" id="fechaLim_<?= (int) $ped['id'] ?>" name="fecha_limite" required
                       value="<?= e(date('Y-m-d', strtotime('+' . (int) $diasP . ' days'))) ?>">
              </div>
              <button class="btn btn-pequeno btn-primario" type="submit">Confirmar y generar préstamo</button>
            </form>
          </details>
          <details class="pedido-expandir">
            <summary class="btn btn-pequeno btn-peligro">Rechazar</summary>
            <form method="post" class="formulario" novalidate>
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="rechazar">
              <input type="hidden" name="id" value="<?= (int) $ped['id'] ?>">
              <div class="campo">
                <label for="motivo_rechazo_<?= (int) $ped['id'] ?>">Motivo del rechazo</label>
                <textarea id="motivo_rechazo_<?= (int) $ped['id'] ?>" name="motivo_rechazo" rows="2" required placeholder="Ej.: ejemplares no disponibles, libro reservado…"></textarea>
              </div>
              <button class="btn btn-pequeno btn-peligro" type="submit">Rechazar pedido</button>
            </form>
          </details>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>

<div class="tarjeta" style="margin-top:1.2rem">
  <h3 class="titulo-seccion">Resueltos recientes</h3>
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead><tr><th>Persona</th><th>Libro</th><th>Pedido</th><th>Respuesta</th><th>Motivo</th></tr></thead>
      <tbody>
        <?php if (!$resueltas): ?>
          <tr><td colspan="5" class="fila-media" style="text-align:center;padding:1.2rem">Sin pedidos resueltos.</td></tr>
        <?php endif; ?>
        <?php foreach ($resueltas as $r): ?>
          <tr>
            <td><?= e($r['persona']) ?></td>
            <td><?= e($r['titulo']) ?></td>
            <td class="fila-media"><?= e(fecha_es($r['fecha_pedido'] ?? $r['fecha_peticion'] ?? '')) ?></td>
            <td><span class="badge <?= $r['estado'] === 'aceptada' ? 'badge-verde' : 'badge-rojo' ?>"><?= e(ucfirst($r['estado'])) ?></span></td>
            <td class="fila-media"><?= $r['motivo_rechazo'] ? e($r['motivo_rechazo']) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>