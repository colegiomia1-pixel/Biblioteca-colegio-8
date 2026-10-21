<?php
/** Vista: morosidad (bibliotecario). */
$vencidos = $vencidos ?? [];
$avisos = $avisos ?? [];
$medios = $medios ?? [];
$errores = $errores ?? [];
$mediosTxt = fn($m) => $medios[$m] ?? $m;
?>
<div class="encabezado-pagina">
  <div>
    <h2>Alertas de morosidad</h2>
    <p><?= count($vencidos) ?> préstamo(s) vencido(s). Cada aviso queda registrado en el historial del préstamo.</p>
  </div>
</div>

<?php if ($errores): ?>
  <div class="aviso-error" role="alert">
    <ul style="margin:0;padding-left:1.1rem"><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if (!$vencidos): ?>
  <div class="aviso-info">No hay préstamos vencidos. Todo en orden.</div>
<?php endif; ?>

<?php foreach ($vencidos as $v):
  $avDel = $avisos[(int) $v['id']] ?? [];
?>
  <div class="tarjeta" style="margin-bottom:.9rem;border-left:4px solid var(--peligro)">
    <div style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:space-between">
      <div>
        <strong><?= e($v['persona']) ?></strong>
        <span class="fila-media"> · <?= e($v['telefono'] ?? 'sin teléfono') ?> · <?= e($v['email'] ?? 'sin email') ?></span>
        <div style="margin-top:.25rem">
          Libro: <strong>«<?= e($v['titulo']) ?>»</strong>
          <span style="display:inline-block" class="badge badge-rojo">Vencido hace <?= (int) $v['dias_vencido'] ?> día(s)</span>
        </div>
        <div class="fila-media">Solicitado el <?= e(fecha_es($v['fecha_prestamo'])) ?> · límite <?= e(fecha_es($v['fecha_limite'])) ?></div>
        <?php if ($avDel): ?>
          <div style="margin-top:.5rem">
            <span class="ayuda">Avisos enviados:</span>
            <?php foreach ($avDel as $av): ?>
              <span class="badge badge-gris" title="<?= e($av['notas'] ?? '') ?>">
                <?= e($mediosTxt($av['medio'])) ?> · <?= e(fecha_es($av['fecha_aviso'])) ?><?= $av['biblio'] ? ' · ' . e($av['biblio']) : '' ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <details>
        <summary class="btn btn-primario btn-pequeno" style="cursor:pointer">Marcar aviso enviado…</summary>
        <form method="post" class="formulario" novalidate style="margin-top:.6rem;min-width:250px">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="avisar">
          <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
          <div class="campo">
            <label for="medio_<?= (int) $v['id'] ?>">Medio de contacto</label>
            <select id="medio_<?= (int) $v['id'] ?>" name="medio">
              <?php foreach ($medios as $k => $m): ?>
                <option value="<?= e($k) ?>"><?= e($m) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="campo">
            <label for="notas_<?= (int) $v['id'] ?>">Notas (opcional)</label>
            <input type="text" id="notas_<?= (int) $v['id'] ?>" name="notas" placeholder="¿Con quién se habló?">
          </div>
          <button class="btn btn-pequeno btn-primario" type="submit">Registrar aviso</button>
        </form>
      </details>
    </div>
  </div>
<?php endforeach; ?>