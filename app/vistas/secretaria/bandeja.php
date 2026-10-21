<?php
/** Vista: bandeja de entrada de secretaría. */
$bandeja   = $bandeja ?? [];
$badges    = $badges ?? [];
$sinVacante = (int) ($sinVacante ?? 0);
?>
<div class="encabezado-pagina">
  <div>
    <h2>Bandeja de entrada</h2>
    <p><?= e(number_format($total)) ?> solicitud(es) pendientes, ordenadas por antigüedad.</p>
  </div>
  <a class="btn btn-fantasma" href="<?= url('secretaria/historial.php') ?>">Ver historial →</a>
</div>

<?php if ($sinVacante > 0): ?>
  <div class="aviso-info" style="margin-bottom:1rem">
    <strong><?= (int) $sinVacante ?> solicitud(es) aprobada(s) aún sin división.</strong>
    <a href="<?= url('secretaria/asignar.php') ?>" style="font-weight:600">Ir a asignación de curso →</a>
  </div>
<?php endif; ?>

<div class="tarjeta">
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead>
        <tr>
          <th>Alumno</th><th>Año</th><th>DNI</th><th>Estado</th><th>Presentada</th><th>Revisor</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$bandeja): ?>
          <tr><td colspan="7" class="fila-media" style="padding:1.4rem;text-align:center">
            No hay solicitudes pendientes.
          </td></tr>
        <?php endif; ?>
        <?php foreach ($bandeja as $r): ?>
          <tr>
            <td><strong><?= e($r['apellido'] . ', ' . $r['nombre']) ?></strong></td>
            <td><?= (int) $r['anio_postulado'] ?>° año</td>
            <td class="fila-media"><?= e($r['dni'] ?: '—') ?></td>
            <td><span class="badge <?= $badges[$r['estado']][0] ?>"><?= $badges[$r['estado']][1] ?></span></td>
            <td class="fila-media"><?= e(fecha_es($r['fecha_presentacion'], true)) ?></td>
            <td class="fila-media"><?= e($r['revisor'] ?: '—') ?></td>
            <td class="acciones-fila">
              <a class="btn btn-pequeno btn-primario" href="<?= url('secretaria/revision.php?id=' . (int) $r['id']) ?>">Revisar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= html_paginador('secretaria/bandeja.php', $pag, $totalPaginas) ?>
  <?php if (!$bandeja && $sinVacante > 0): ?>
    <div style="text-align:center;padding:1rem 1.4rem 1.4rem">
      <a class="btn btn-primario" href="<?= url('secretaria/asignar.php') ?>">Ir a asignación de curso →</a>
    </div>
  <?php endif; ?>
</div>