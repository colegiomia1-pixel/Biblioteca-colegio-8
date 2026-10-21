<?php
/** Vista: historial de solicitudes resueltas. */
$items = $items ?? [];
$opes = ['aprobada', 'rechazada'];
?>
<div class="encabezado-pagina">
  <div>
    <h2>Historial de solicitudes resueltas</h2>
    <p><?= e(number_format($total)) ?> resultado(s).</p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a class="btn btn-fantasma" href="<?= url('secretaria/reportes.php?tipo=postulaciones') ?>">Exportar listado →</a>
  </div>
</div>

<form method="get" class="tarjeta" style="display:flex;gap:.7rem;flex-wrap:wrap;align-items:end;margin-bottom:1.1rem">
  <input type="hidden" name="estado" value="<?= e($fEst) ?>">
  <div class="campo" style="flex:1;min-width:220px">
    <label for="buscar">Buscar alumno / DNI</label>
    <input type="text" id="buscar" name="buscar" value="<?= e($fBus) ?>" placeholder="Nombre, apellido o DNI">
  </div>
  <div class="acciones-fila" style="align-self:center">
    <a class="btn btn-fantasma" href="<?= url('secretaria/historial.php') ?>">Limpiar</a>
    <button class="btn btn-primario" type="submit">Filtrar</button>
  </div>
</form>

<div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
  <a class="btn btn-pequeno <?= $fEst === '' ? 'btn-primario' : 'btn-fantasma' ?>" href="<?= url('secretaria/historial.php') ?>">Todas</a>
  <?php foreach ($opes as $op): ?>
    <a class="btn btn-pequeno <?= $fEst === $op ? 'btn-primario' : 'btn-fantasma' ?>"
       href="<?= url('secretaria/historial.php?estado=' . $op) ?>"><?= e(ucfirst($op)) ?>s</a>
  <?php endforeach; ?>
</div>

<div class="tarjeta">
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead>
        <tr><th>Alumno</th><th>Año</th><th>Resultado</th><th>Resuelta</th><th>Curso / motivo</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="6" class="fila-media" style="padding:1.4rem;text-align:center">Sin resultados.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $r): ?>
          <tr>
            <td><strong><?= e($r['apellido'] . ', ' . $r['nombre']) ?></strong></td>
            <td><?= (int) $r['anio_postulado'] ?>° año</td>
            <td>
              <span class="badge <?= $r['estado'] === 'aprobada' ? 'badge-verde' : 'badge-rojo' ?>">
                <?= e(ucfirst($r['estado'])) ?>
              </span>
            </td>
            <td class="fila-media"><?= e(fecha_es($r['fecha_resolucion'], true)) ?></td>
            <td class="fila-media">
              <?php if ($r['estado'] === 'aprobada' && $r['cu_anio']): ?>
                <?= e($r['cu_anio'] . '° ' . $r['cu_div']) ?> · <?= e($r['cu_turno'] === 'manana' ? 'mañana' : 'tarde') ?>
              <?php elseif ($r['estado'] === 'rechazada'): ?>
                <span title="<?= e($r['motivo_rechazo']) ?>"><?= e(mb_strimwidth((string) $r['motivo_rechazo'], 0, 60, '…')) ?></span>
              <?php else: ?>
                Sin asignar
              <?php endif; ?>
            </td>
            <td class="acciones-fila">
              <a class="btn btn-pequeno btn-fantasma" href="<?= url('secretaria/revision.php?id=' . (int) $r['id']) ?>">Ver</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= html_paginador('secretaria/historial.php?estado=' . urlencode($fEst) . '&buscar=' . urlencode($fBus), $pag, $totalPaginas) ?>
</div>