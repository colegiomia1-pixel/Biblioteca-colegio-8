<?php
/** Vista: asignación de cursos a alumnos aprobados. */
$porAsignar   = $porAsignar ?? [];
$vacantesActivas = $vacantesActivas ?? [];
$resaltar     = $resaltar ?? 0;
$tituloTurno = fn($t) => $t === 'manana' ? 'mañana' : 'tarde';
?>
<div class="encabezado-pagina">
  <div>
    <h2>Asignación de división</h2>
    <p>Alumnos aprobados que aún no tienen curso (los que están en espera pueden cambiarse y se conserva su posición).</p>
  </div>
  <span class="badge badge-gris">Total: <?= count($porAsignar) ?></span>
</div>

<?php if (!$porAsignar): ?>
  <div class="aviso-exito">No hay alumnos aprobados pendientes de asignación.</div>
<?php endif; ?>

<div class="tarjeta">
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead>
        <tr>
          <th>Alumno</th><th>DNI</th><th>Postula a</th><th>Estado</th><th>Asignar curso</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($porAsignar as $a): ?>
          <tr id="alumno-<?= (int) $a['id'] ?>" class="<?= $resaltar && (int) $a['id'] === $resaltar ? 'fila-resaltada' : '' ?>">
            <td><strong><?= e($a['apellido'] . ', ' . $a['nombre']) ?></strong></td>
            <td class="fila-media"><?= e($a['dni'] ?: '—') ?></td>
            <td><?= (int) $a['anio_postulado'] ?>° año</td>
            <td>
              <?php if ($a['en_espera_pos']): ?>
                <span class="badge badge-ambar">Espera #<?= (int) $a['en_espera_pos'] ?> · <?= (int) $a['esp_anio'] ?>° <?= e($a['esp_div']) ?> (<?= e($tituloTurno((string) $a['esp_turno'])) ?>)</span>
              <?php else: ?>
                <span class="badge badge-gris">Por asignar</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:.3rem;align-items:center;flex-wrap:wrap">
                <form method="post" style="display:inline-flex;gap:.3rem;align-items:center;flex-wrap:wrap">
                  <?= csrf_field() ?>
                  <input type="hidden" name="alumno_id" value="<?= (int) $a['id'] ?>">
                  <select name="vacante_id" aria-label="Curso de destino" required>
                    <option value="">Elegir curso…</option>
                    <?php foreach (array_filter($vacantesActivas, fn($v) => (int) $v['anio'] === (int) $a['anio_postulado']) as $v): ?>
                      <?php
                        $lleno = (int) $v['cupo_ocupado'] >= (int) $v['cupo_total'];
                        $etq = curso_titulo($v) . ($lleno ? ' · lleno (espera)' : ' · ' . ((int) $v['cupo_total'] - (int) $v['cupo_ocupado']) . ' libres');
                      ?>
                      <option value="<?= (int) $v['id'] ?>" <?= $lleno ? 'data-lleno="1"' : '' ?>><?= e($etq) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-pequeno btn-primario" type="submit">Asignar</button>
                </form>
                <form method="post" data-confirm="¿Reenviar la postulación de este alumno a revisión y sacarlo de la lista de espera?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="accion" value="reenviar">
                  <input type="hidden" name="alumno_id" value="<?= (int) $a['id'] ?>">
                  <button class="btn btn-pequeno btn-fantasma" type="submit">Reenviar a revisión</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>