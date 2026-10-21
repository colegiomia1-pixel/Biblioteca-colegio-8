<?php
/** Vista: historial de préstamos (lector). */
$historial = $historial ?? [];
$estados = [
    'activo'    => ['badge-verde', 'Activo'],
    'vencido'   => ['badge-rojo',  'Vencido'],
    'devuelto'  => ['badge-gris',  'Devuelto'],
    'cancelado' => ['badge-gris',  'Cancelado'],
];
?>
<div class="encabezado-pagina">
  <div>
    <h2>Mi historial</h2>
    <p>Préstamos y devoluciones de tu cuenta de biblioteca.</p>
  </div>
</div>

<?php if (!$historial): ?>
  <div class="aviso-info">Todavía no tenés préstamos. Podés pedir un libro desde el <a href="<?= url('biblioteca-lector/catalogo.php') ?>">catálogo</a>.</div>
<?php endif; ?>

<div class="tarjeta">
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead><tr><th>Libro</th><th>Prestado</th><th>Devolver</th><th>Devuelto</th><th>Estado</th></tr></thead>
      <tbody>
        <?php foreach ($historial as $h): $info = $estados[$h['estado']] ?? ['badge-gris', $h['estado']]; ?>
          <tr class="<?= $h['estado'] === 'vencido' ? 'fila-vencido' : '' ?>">
            <td>
              <strong><?= e($h['titulo']) ?></strong><br>
              <span class="fila-media" style="font-size:.8rem"><?= e($h['autor']) ?></span>
            </td>
            <td class="fila-media"><?= e(fecha_es($h['fecha_prestamo'])) ?></td>
            <td class="fila-media"><?= e(fecha_es($h['fecha_limite'])) ?><?= $h['estado'] === 'vencido' ? ' <span class="badge badge-rojo">+' . (int) $h['dias_vencido'] . ' días</span>' : '' ?></td>
            <td class="fila-media"><?= $h['fecha_devolucion'] ? e(fecha_es($h['fecha_devolucion'])) : '—' ?></td>
            <td><span class="badge <?= $info[0] ?>"><?= e($info[1]) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>