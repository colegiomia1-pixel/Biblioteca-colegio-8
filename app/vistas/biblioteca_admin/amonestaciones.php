<?php
/** Vista: amonestaciones (bibliotecario). */
$alumnos = $alumnos ?? [];
$amonestaciones = $amonestaciones ?? [];
$tipos = $tipos ?? [];
$errores = $errores ?? [];

$badgesTipo = [
    'tardanza' => 'badge-gris',
    'mal_estado' => 'badge-ambar',
    'perdida' => 'badge-rojo',
    'otro' => 'badge-gris',
];
?>
<div class="encabezado-pagina">
  <div>
    <h2>Amonestaciones</h2>
    <p>Registro de incidentes de biblioteca por alumno.</p>
  </div>
</div>

<div class="rejilla rejilla-2">
  <div class="tarjeta">
    <h3 class="titulo-seccion">Registrar amonestación</h3>
    <?php if ($errores): ?>
      <div class="aviso-error" role="alert">
        <ul style="margin:0;padding-left:1.1rem"><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" class="formulario" novalidate>
      <?= csrf_field() ?>
      <div class="campo">
        <label for="alumno_id">Alumno <span class="requerido">*</span></label>
        <select id="alumno_id" name="alumno_id" required>
          <option value="">Elegir…</option>
          <?php foreach ($alumnos as $a): ?>
            <option value="<?= (int) $a['id'] ?>">
              <?= e($a['apellido'] . ', ' . $a['nombre']) ?><?= $a['anio'] ? ' — ' . (int) $a['anio'] . '° ' . e($a['division'] ?? '') : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fila-campos">
        <div class="campo">
          <label for="tipo">Tipo <span class="requerido">*</span></label>
          <select id="tipo" name="tipo" required>
            <option value="">Elegir…</option>
            <?php foreach ($tipos as $k => $v): ?>
              <option value="<?= e($k) ?>"><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="fecha">Fecha <span class="requerido">*</span></label>
          <input type="date" id="fecha" name="fecha" required value="<?= e(hoy_iso()) ?>">
        </div>
      </div>
      <div class="campo">
        <label for="descripcion">Descripción del incidente <span class="requerido">*</span></label>
        <textarea id="descripcion" name="descripcion" rows="3" required placeholder="¿Qué sucedió? ¿Qué libro u otro elemento está implicado?"></textarea>
      </div>
      <button class="btn btn-primario" type="submit">Guardar amonestación</button>
    </form>
  </div>

  <div class="tarjeta">
    <h3 class="titulo-seccion">Últimas amonestaciones</h3>
    <div class="tabla-recorrido">
      <table class="tabla">
        <thead><tr><th>Fecha</th><th>Alumno</th><th>Tipo</th><th>Registrada por</th></tr></thead>
        <tbody>
          <?php if (!$amonestaciones): ?>
            <tr><td colspan="4" class="fila-media" style="text-align:center;padding:1.2rem">Sin amonestaciones.</td></tr>
          <?php endif; ?>
          <?php foreach ($amonestaciones as $am): ?>
            <tr>
              <td class="fila-media"><?= e(fecha_es($am['fecha'])) ?></td>
              <td><strong><?= e($am['alumno']) ?></strong><br>
                <span class="fila-media" style="font-size:.78rem"><?= $am['anio'] ? (int) $am['anio'] . '° ' . e($am['division'] ?? '') : '' ?></span></td>
              <td><span class="badge <?= $badgesTipo[$am['tipo']] ?? 'badge-gris' ?>"><?= e($tipos[$am['tipo']] ?? $am['tipo']) ?></span>
                <div class="fila-media" style="font-size:.8rem;max-width:220px"><?= e($am['descripcion']) ?></div></td>
              <td class="fila-media"><?= e($am['bibliotecario'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>