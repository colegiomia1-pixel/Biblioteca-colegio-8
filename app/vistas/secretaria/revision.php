<?php
/** Vista: revisión completa de una solicitud. */
$r         = $r ?? [];
$docs      = $docs ?? [];
$motivos   = $motivos ?? [];
$bloqueada = $bloqueada ?? false;
$bloqueador = $bloqueador ?? '';
$segBloqueo = max(30, (int) ($segBloqueo ?? 90));
$estadoClass = [
    'recibida'    => 'badge-gris',
    'en_revision' => 'badge-ambar',
    'aprobada'    => 'badge-verde',
    'rechazada'   => 'badge-rojo',
][$r['estado'] ?? 'recibida'] ?? 'badge-gris';
?>
<div class="encabezado-pagina">
  <div>
    <h2>Solicitud de <?= e($r['al_apellido'] . ', ' . $r['al_nombre']) ?></h2>
    <p>Presentada el <?= e(fecha_es($r['fecha_presentacion'], true)) ?>
      · <span class="badge <?= $estadoClass ?>"><?= e(ucfirst($r['estado'])) ?></span></p>
  </div>
  <a class="btn btn-fantasma" href="<?= url('secretaria/bandeja.php') ?>">← Volver a la bandeja</a>
</div>

<?php if ($r['estado'] === 'rechazada' && $r['motivo_rechazo']): ?>
  <div class="aviso-error"><strong>Motivo del rechazo anterior:</strong> <?= e($r['motivo_rechazo']) ?></div>
<?php endif; ?>

<?php if ($bloqueada): ?>
  <div class="aviso-info">
    <strong>Esta solicitud la está revisando <?= e($bloqueador ?: 'otra secretaria') ?>.</strong>
    La vista es de solo lectura; se desbloqueará sola si la revisión se deja abandonada.
  </div>
<?php endif; ?>

<?php if ($r['estado'] === 'en_revision' && !$bloqueada): ?>
  <div class="aviso-info" id="avisoLatido">
    <strong>La revisión está en curso.</strong> Se libera automáticamente a los
    <?= (int) $segBloqueo ?> segundos de inactividad o al cerrar la ventana.
  </div>
<?php endif; ?>

<div class="rejilla rejilla-2">
  <div class="tarjeta">
    <h3 class="titulo-seccion">Datos del alumno</h3>
    <dl class="detalle">
      <div><dt>Nombre</dt><dd><?= e($r['al_nombre'] . ' ' . $r['al_apellido']) ?></dd></div>
      <div><dt>DNI</dt><dd><?= e($r['al_dni'] ?: '—') ?></dd></div>
      <div><dt>Teléfono</dt><dd><?= e($r['al_telefono'] ?: '—') ?></dd></div>
      <div><dt>Nacimiento</dt><dd><?= e(fecha_es($r['al_nac'])) ?></dd></div>
      <div><dt>Postula a</dt><dd><?= (int) $r['anio_postulado'] ?>° año</dd></div>
    </dl>
  </div>

  <div class="tarjeta">
    <h3 class="titulo-seccion">Datos del tutor / responsable</h3>
    <dl class="detalle">
      <div><dt>Nombre</dt><dd><?= e($r['tu_nombre'] . ' ' . $r['tu_apellido']) ?></dd></div>
      <div><dt>DNI</dt><dd><?= e($r['tu_dni']) ?></dd></div>
      <div><dt>Nacimiento</dt><dd><?= e(fecha_es($r['tu_nac'])) ?></dd></div>
      <div><dt>Teléfono</dt><dd><?= e($r['tu_telefono']) ?></dd></div>
      <div><dt>Dirección</dt><dd><?= e($r['tu_direccion'] ?: '—') ?></dd></div>
    </dl>
  </div>
</div>

<div class="tarjeta" style="margin-top:1.2rem">
  <h3 class="titulo-seccion">Documentación adjunta (4 fotos del DNI)</h3>
  <div class="rejilla rejilla-2 doc-grupo">
    <?php foreach (tipos_dni_documentos() as $campoDoc): ?>
      <?php $docF = $docs[$campoDoc] ?? null; ?>
      <div class="doc-miniatura">
        <span class="doc-ico" aria-hidden="true">🪪</span>
        <span style="flex:1;font-size:.85rem">
          <strong><?= e(doc_etiqueta_tipo($campoDoc)) ?></strong><br>
          <?php if ($docF): ?>
            <?= e($docF['nombre_original']) ?>
          <?php else: ?>
            <em style="color:var(--rojo)">No adjuntado</em>
          <?php endif; ?>
        </span>
        <?php if ($docF): ?>
          <button class="btn btn-fantasma btn-pequeno"
                  data-doc-preview="documento.php?id=<?= (int) $docF['id'] ?>"
                  data-doc-nombre="<?= e(doc_etiqueta_tipo($campoDoc)) ?>"
                  <?= es_img_documento((string) $docF['nombre_original']) ? 'data-doc-img="1"' : '' ?>>Ver en pantalla</button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if (in_array($r['estado'], ['recibida', 'en_revision'], true) && !$bloqueada): ?>
  <div class="rejilla rejilla-2" style="margin-top:1.2rem">
    <form method="post" class="tarjeta" data-confirm="¿Confirmás la aprobación de esta solicitud?">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="aprobar">
      <p class="titulo-seccion">Decisión</p>
      <p style="color:var(--gris-500);font-size:.9rem">
        Al aprobar, la documentación queda validada y el alumno pasa a la asignación de división.
      </p>
      <button class="btn btn-primario" type="submit">✓ Validar y aprobar</button>
    </form>

    <form method="post" class="tarjeta" id="formRechazo" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="rechazar">
      <p class="titulo-seccion">Rechazar solicitud</p>
      <div class="campo">
        <label for="motivo">Motivo del rechazo <span class="requerido">*</span></label>
        <textarea id="motivo" name="motivo" rows="4" required minlength="5"
                  placeholder="Explicá al postulante qué debe corregir o adjuntar."><?= e($_POST['motivo'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.5rem">
        <?php foreach ($motivos as $m): ?>
          <button type="button" class="btn btn-pequeno btn-fantasma" data-llenar-motivo="<?= e($m) ?>"><?= e($m) ?></button>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-peligro" type="submit" style="margin-top:.9rem">Rechazar solicitud</button>
    </form>
  </div>
<?php endif; ?>

<style>
  .detalle { display: grid; gap: .4rem; margin: 0; font-size: .92rem; }
  .detalle > div { display: grid; grid-template-columns: 120px 1fr; gap: .6rem; border-bottom: 1px dashed var(--gris-200); padding: .35rem 0; }
  .detalle dt { color: var(--gris-500); font-weight: 600; }
  .detalle dd { margin: 0; }
</style>

<?php if ($r['estado'] === 'en_revision' && !$bloqueada): ?>
<script>
(function () {
  var finsc = <?= (int) $segBloqueo * 1000 ?>;
  var msLatido = Math.max(10000, Math.floor(finsc / 3));
  var id = <?= (int) $r['id'] ?>;
  var csrf = <?= json_encode(csrf_token()) ?>;
  var aviso = document.getElementById('avisoLatido');
  var detenido = false;

  function latido(tipo) {
    var body = 'id=' + encodeURIComponent(id) + '&csrf=' + encodeURIComponent(csrf);
    fetch('<?= url('secretaria/frecuencia-revision.php') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body,
      keepalive: true
    }).catch(function () { detener(); });
  }

  function liberar() {
    if (detenido) return;
    detenido = true;
    if (aviso) aviso.style.display = 'none';
    var body = 'id=' + encodeURIComponent(id) + '&csrf=' + encodeURIComponent(csrf);
    fetch('<?= url('secretaria/liberar-revision.php') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body,
      keepalive: true
    }).catch(function () {});
  }

  function detener() {
    if (detenido) return;
    detenido = true;
    if (aviso) aviso.style.display = 'none';
    clearInterval(timer);
  }

  var timer = setInterval(latido, msLatido);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') liberar();
    else if (!detenido) latido();
  });
  window.addEventListener('pagehide', liberar);
  window.addEventListener('beforeunload', liberar);
})();
</script>
<?php endif; ?>