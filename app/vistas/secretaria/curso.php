<?php
/** Vista: detalle de curso. */
$curso    = $curso ?? [];
$alumnos  = $alumnos ?? [];
$espera   = $espera ?? [];
$destinos = $destinos ?? [];
$movimientos = $movimientos ?? [];
$pct = $curso['cupo_total'] > 0 ? round(((int) $curso['cupo_ocupado'] / (int) $curso['cupo_total']) * 100) : 0;
?>
<div class="encabezado-pagina">
  <div>
    <h2>Curso <?= e(curso_titulo($curso)) ?></h2>
    <p>Cupo: <?= (int) $curso['cupo_ocupado'] ?> / <?= (int) $curso['cupo_total'] ?> alumnos inscriptos.</p>
  </div>
  <a class="btn btn-fantasma" href="<?= url('secretaria/vacantes.php') ?>">← Cupos y cursos</a>
</div>

<div class="tarjeta" style="margin-bottom:1.2rem">
  <div class="barra-cupo <?= (int) $curso['cupo_ocupado'] >= (int) $curso['cupo_total'] ? 'llena' : '' ?>">
    <span style="width:<?= $pct ?>%"></span>
  </div>
  <p style="font-size:.78rem;color:var(--gris-400);margin:.4rem 0 0"><?= $pct ?>% de ocupación</p>
</div>

<div class="tarjeta">
  <h3 class="titulo-seccion">Alumnos inscriptos (<?= count($alumnos) ?>)</h3>
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead>
        <tr><th>Alumno</th><th>DNI</th><th>Tutor / responsable</th><th>Tel. tutor</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php if (!$alumnos): ?>
          <tr><td colspan="5" class="fila-media" style="padding:1.4rem;text-align:center">
            No hay alumnos en este curso por ahora. Cuando se aprueben solicitudes con este curso como destino, aparecerán acá.
          </td></tr>
        <?php endif; ?>
        <?php foreach ($alumnos as $a): ?>
          <tr>
            <td><strong><?= e($a['apellido'] . ', ' . $a['nombre']) ?></strong></td>
            <td class="fila-media"><?= e($a['dni'] ?: '—') ?></td>
            <td><?= e(($a['tu_nombre'] ?? '') . ' ' . ($a['tu_apellido'] ?? '—')) ?></td>
            <td class="fila-media"><?= e($a['tu_telefono'] ?? '—') ?></td>
            <td class="acciones-fila">
              <?php if ($destinos): ?>
                <button type="button" class="btn btn-pequeno btn-fantasma"
                        data-mover-alumno="<?= (int) $a['id'] ?>"
                        data-mover-nombre="<?= e($a['apellido'] . ', ' . $a['nombre']) ?>"
                        data-curso-id="<?= (int) $curso['id'] ?>">Mover</button>
              <?php endif; ?>
              <form method="post" style="display:inline" data-confirm="¿Quitar a <?= e($a['nombre'] . ' ' . $a['apellido']) ?> de este curso?">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="quitar">
                <input type="hidden" name="alumno_id" value="<?= (int) $a['id'] ?>">
                <button class="btn btn-pequeno btn-peligro" type="submit">Quitar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($espera): ?>
  <div class="tarjeta" style="margin-top:1.2rem">
    <h3 class="titulo-seccion">Lista de espera (<?= count($espera) ?>)</h3>
    <div class="tabla-recorrido">
      <table class="tabla">
        <thead><tr><th>Posición</th><th>Alumno</th><th>En espera desde</th></tr></thead>
        <tbody>
          <?php foreach ($espera as $w): ?>
            <tr>
              <td><span class="badge <?= (int) $w['posicion'] === 1 ? 'badge-ambar' : 'badge-gris' ?>">#<?= (int) $w['posicion'] ?></span></td>
              <td><strong><?= e($w['apellido'] . ', ' . $w['nombre']) ?></strong></td>
              <td class="fila-media"><?= e(fecha_es($w['fecha_ingreso'], true)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="ayuda" style="margin-top:.6rem">Al quedar un cupo libre, el primer alumno de la lista pasa automáticamente al curso.</p>
  </div>
<?php endif; ?>

<?php if ($movimientos): ?>
  <div class="tarjeta" style="margin-top:1.2rem">
    <h3 class="titulo-seccion">Movimientos recientes de este curso</h3>
    <div class="tabla-recorrido">
      <table class="tabla">
        <thead><tr><th>Alumno</th><th>Detalle</th><th>Fecha</th></tr></thead>
        <tbody>
          <?php foreach ($movimientos as $m): ?>
            <tr>
              <td><strong><?= e($m['apellido'] . ', ' . $m['nombre']) ?></strong></td>
              <td class="fila-media"><?= e($m['motivo']) ?></td>
              <td class="fila-media"><?= e(fecha_es($m['fecha'], true)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<script id="destinosMoverJson" type="application/json"><?= json_encode($destinos) ?></script>