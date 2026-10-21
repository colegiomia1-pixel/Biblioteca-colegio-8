<?php
/** Vista: wizard de solicitud de inscripción. */
$errores   = $errores ?? [];
$docs      = $docs ?? [];
$modo      = $modo ?? 'nueva';
$pasoActual  = $paso ?? 1;
$aniosUsuario = $anios ?? [1, 2, 3, 4, 5];

$pasoHecho = function (int $n) use ($pasoActual): bool {
    return $n < $pasoActual;
};
$pasoEstado = function (int $n) use ($pasoActual, $pasoHecho): string {
    if ($pasoHecho($n)) return 'hecho';
    if ($n === $pasoActual) return 'activo';
    return '';
};
?>
<div class="encabezado-pagina">
  <div>
    <h2><?= $modo === 'correccion' ? 'Corregir y reenviar la solicitud' : 'Solicitud de vacante' ?></h2>
    <p><?= $modo === 'correccion'
        ? 'La secretaría rechazó la solicitud. Corregí los datos y/o reemplazá la documentación, y volvé a enviarla.'
        : 'Completá los datos del alumno y del tutor en los pasos indicados. Los archivos se guardan en el servidor.' ?></p>
  </div>
</div>

<ol class="wizard-pasos" aria-label="Pasos de la solicitud">
  <li class="wizard-paso <?= $pasoEstado(1) ?>"><span class="wizard-paso-nombre">Datos del alumno</span></li>
  <li class="wizard-paso <?= $pasoEstado(2) ?>"><span class="wizard-paso-nombre">Datos del tutor</span></li>
  <li class="wizard-paso <?= $pasoEstado(3) ?>"><span class="wizard-paso-nombre">Revisar y enviar</span></li>
</ol>

<?php if ($errores): ?>
  <div class="aviso-error" role="alert">
    <strong>Revisá estos datos:</strong>
    <ul style="margin:.4rem 0 0 1.1rem;padding:0">
      <?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($pasoActual === 1): ?>

  <form method="post" enctype="multipart/form-data" class="tarjeta" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="siguiente">
    <p class="titulo-seccion">Datos del alumno postulante</p>
    <div class="formulario formulario-amplio">
      <div class="fila-campos">
        <div class="campo">
          <label for="alumno_nombre">Nombre <span class="requerido">*</span></label>
          <input type="text" id="alumno_nombre" name="alumno_nombre" required
                 value="<?= e($draft['alumno']['nombre'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="alumno_apellido">Apellido <span class="requerido">*</span></label>
          <input type="text" id="alumno_apellido" name="alumno_apellido" required
                 value="<?= e($draft['alumno']['apellido'] ?? '') ?>">
        </div>
      </div>
      <div class="fila-campos">
        <div class="campo">
          <label for="alumno_dni">DNI <span class="ayuda">(opcional)</span></label>
          <input type="text" id="alumno_dni" name="alumno_dni" inputmode="numeric" maxlength="9"
                 placeholder="Sin puntos" value="<?= e($draft['alumno']['dni'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="alumno_telefono">Teléfono de contacto <span class="requerido">*</span></label>
          <input type="text" id="alumno_telefono" name="alumno_telefono" required
                 value="<?= e($draft['alumno']['telefono'] ?? '') ?>">
        </div>
      </div>
      <div class="fila-campos">
        <div class="campo">
          <label for="alumno_fecha_nacimiento">Fecha de nacimiento <span class="requerido">*</span></label>
          <input type="date" id="alumno_fecha_nacimiento" name="alumno_fecha_nacimiento" required
                 value="<?= e($draft['alumno']['fecha_nacimiento'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="alumno_anio">Año al que postula <span class="requerido">*</span></label>
          <select id="alumno_anio" name="alumno_anio" required>
            <option value="">Elegir…</option>
            <?php foreach ($aniosUsuario as $a): ?>
              <option value="<?= $a ?>" <?= (int) ($draft['alumno']['anio_postulado'] ?? 0) === $a ? 'selected' : '' ?>>
                <?= $a ?>° año
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="rejilla rejilla-2">
        <?php foreach (['dni_alumno_frente' => 'frente', 'dni_alumno_dorso' => 'dorso'] as $campoDoc => $caraDoc): ?>
          <?php $docF = $docs[$campoDoc] ?? null; ?>
          <div class="campo">
            <label for="<?= e($campoDoc) ?>">Foto del DNI del alumno — <?= e($caraDoc) ?>
              <?php if ($docF): ?>
                <span class="ayuda">(ya cargado — dejalo vacío para conservarlo)</span>
              <?php elseif ($modo === 'correccion'): ?>
                <span class="ayuda">(dejala vacía para mantener la actual)</span>
              <?php else: ?>
                <span class="requerido">*</span>
              <?php endif; ?>
            </label>
            <input type="file" id="<?= e($campoDoc) ?>" name="<?= e($campoDoc) ?>"
                   accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
            <p class="ayuda">JPG, PNG o PDF. Máximo <?= e(valor_config('limite_archivo_mb')) ?> MB. Se valida en el servidor.</p>
            <?php if ($docF): ?>
              <div class="doc-miniatura" style="margin-top:.5rem">
                <span class="doc-ico" aria-hidden="true">🪪</span>
                <span style="flex:1;font-size:.85rem"><?= e($docF['nombre_original']) ?></span>
                <button type="button" class="btn btn-fantasma btn-pequeno"
                        data-doc-preview="documento.php?id=<?= (int) $docF['id'] ?>"
                        data-doc-nombre="<?= e(doc_etiqueta_tipo($campoDoc)) ?>"
                        <?= es_img_documento((string) $docF['nombre_original']) ? 'data-doc-img="1"' : '' ?>>Ver</button>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="display:flex;justify-content:space-between;gap:.6rem;flex-wrap:wrap">
        <a class="btn btn-fantasma" href="<?= url('postulante/estado.php') ?>">Cancelar</a>
        <button class="btn btn-primario" type="submit">Continuar →</button>
      </div>
    </div>
  </form>

<?php elseif ($pasoActual === 2): ?>

  <form method="post" enctype="multipart/form-data" class="tarjeta" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="siguiente">
    <p class="titulo-seccion">Datos del tutor / responsable</p>
    <div class="formulario formulario-amplio">
      <div class="fila-campos">
        <div class="campo">
          <label for="tutor_nombre">Nombre <span class="requerido">*</span></label>
          <input type="text" id="tutor_nombre" name="tutor_nombre" required
                 value="<?= e($draft['tutor']['nombre'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="tutor_apellido">Apellido <span class="requerido">*</span></label>
          <input type="text" id="tutor_apellido" name="tutor_apellido" required
                 value="<?= e($draft['tutor']['apellido'] ?? '') ?>">
        </div>
      </div>
      <div class="fila-campos">
        <div class="campo">
          <label for="tutor_dni">DNI <span class="requerido">*</span></label>
          <input type="text" id="tutor_dni" name="tutor_dni" required inputmode="numeric" maxlength="10"
                 placeholder="Sin puntos" value="<?= e($draft['tutor']['dni'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="tutor_fecha_nacimiento">Fecha de nacimiento <span class="requerido">*</span></label>
          <input type="date" id="tutor_fecha_nacimiento" name="tutor_fecha_nacimiento" required
                 value="<?= e($draft['tutor']['fecha_nacimiento'] ?? '') ?>">
        </div>
      </div>
      <div class="fila-campos">
        <div class="campo">
          <label for="tutor_telefono">Teléfono <span class="requerido">*</span></label>
          <input type="text" id="tutor_telefono" name="tutor_telefono" required
                 value="<?= e($draft['tutor']['telefono'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="tutor_direccion">Dirección <span class="requerido">*</span></label>
          <input type="text" id="tutor_direccion" name="tutor_direccion" required
                 value="<?= e($draft['tutor']['direccion'] ?? '') ?>">
        </div>
      </div>

      <div class="rejilla rejilla-2">
        <?php foreach (['dni_tutor_frente' => 'frente', 'dni_tutor_dorso' => 'dorso'] as $campoDoc => $caraDoc): ?>
          <?php $docF = $docs[$campoDoc] ?? null; ?>
          <div class="campo">
            <label for="<?= e($campoDoc) ?>">Foto del DNI del tutor — <?= e($caraDoc) ?>
              <?php if ($docF): ?>
                <span class="ayuda">(ya cargado — dejalo vacío para conservarlo)</span>
              <?php elseif ($modo === 'correccion'): ?>
                <span class="ayuda">(dejala vacía para mantener la actual)</span>
              <?php else: ?>
                <span class="requerido">*</span>
              <?php endif; ?>
            </label>
            <input type="file" id="<?= e($campoDoc) ?>" name="<?= e($campoDoc) ?>"
                   accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
            <p class="ayuda">JPG, PNG o PDF. Máximo <?= e(valor_config('limite_archivo_mb')) ?> MB. Se valida en el servidor.</p>
            <?php if ($docF): ?>
              <div class="doc-miniatura" style="margin-top:.5rem">
                <span class="doc-ico" aria-hidden="true">🪪</span>
                <span style="flex:1;font-size:.85rem"><?= e($docF['nombre_original']) ?></span>
                <button type="button" class="btn btn-fantasma btn-pequeno"
                        data-doc-preview="documento.php?id=<?= (int) $docF['id'] ?>"
                        data-doc-nombre="<?= e(doc_etiqueta_tipo($campoDoc)) ?>"
                        <?= es_img_documento((string) $docF['nombre_original']) ? 'data-doc-img="1"' : '' ?>>Ver</button>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="display:flex;justify-content:space-between;gap:.6rem;flex-wrap:wrap">
        <a class="btn btn-fantasma" href="<?= url('postulante/aplicacion.php?paso=1') ?>">← Anterior</a>
        <button class="btn btn-primario" type="submit">Continuar →</button>
      </div>
    </div>
  </form>

<?php else: ?>

  <form method="post" class="tarjeta">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="enviar">
    <p class="titulo-seccion">Revisá los datos antes de enviar</p>
    <div class="rejilla rejilla-2">
      <div>
        <h3 style="font-size:.95rem">Alumno postulante</h3>
        <p style="font-size:.9rem;color:var(--gris-700)">
          <?= e($draft['alumno']['nombre'] . ' ' . $draft['alumno']['apellido']) ?><br>
          DNI: <?= e($draft['alumno']['dni'] ?: '—') ?><br>
          Tel: <?= e($draft['alumno']['telefono'] ?: '—') ?><br>
          Nacimiento: <?= e(fecha_es($draft['alumno']['fecha_nacimiento'] ?? null)) ?><br>
          Postula a: <?= (int) ($draft['alumno']['anio_postulado'] ?? 0) ?>° año
        </p>
      </div>
      <div>
        <h3 style="font-size:.95rem">Tutor / responsable</h3>
        <p style="font-size:.9rem;color:var(--gris-700)">
          <?= e($draft['tutor']['nombre'] . ' ' . $draft['tutor']['apellido']) ?><br>
          DNI: <?= e($draft['tutor']['dni']) ?><br>
          Tel: <?= e($draft['tutor']['telefono']) ?><br>
          Dirección: <?= e($draft['tutor']['direccion'] ?: '—') ?>
        </p>
      </div>
    </div>

    <h2 style="font-size:1rem">Documentación (las 4 fotos del DNI)</h2>
    <div class="rejilla rejilla-2 doc-grupo" style="margin-top:.4rem">
      <?php foreach (tipos_dni_documentos() as $campoDoc): ?>
        <?php $docF = $docs[$campoDoc] ?? null; ?>
        <div class="doc-miniatura">
          <span class="doc-ico" aria-hidden="true">🪪</span>
          <span style="flex:1;font-size:.85rem">
            <strong><?= e(doc_etiqueta_tipo($campoDoc)) ?></strong><br>
            <?= $docF ? e($docF['nombre_original']) : '(sin archivo)' ?>
          </span>
          <?php if ($docF): ?>
            <button type="button" class="btn btn-fantasma btn-pequeno"
                    data-doc-preview="documento.php?id=<?= (int) $docF['id'] ?>"
                    data-doc-nombre="<?= e(doc_etiqueta_tipo($campoDoc)) ?>"
                    <?= es_img_documento((string) $docF['nombre_original']) ? 'data-doc-img="1"' : '' ?>>Ver</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="ayuda" style="margin-top:.5rem">Si falta alguna foto, volvé al paso correspondiente para adjuntarla.</p>

    <div style="display:flex;justify-content:space-between;gap:.6rem;flex-wrap:wrap;margin-top:1.4rem">
      <a class="btn btn-fantasma" href="<?= url('postulante/aplicacion.php?paso=2') ?>">← Anterior</a>
      <button class="btn btn-primario" type="submit">
        <?= $modo === 'correccion' ? 'Reenviar solicitud' : 'Enviar solicitud' ?>
      </button>
    </div>
  </form>

<?php endif; ?>