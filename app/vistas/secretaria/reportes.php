<?php
/** Vista: reportes de secretaría. */
$reporte  = $reporte ?? ['nombre' => '', 'columnas' => [], 'filas' => []];
$tiposInfo = $tiposInfo ?? [];
$vistaImpresion = $vistaImpresion ?? false;
$nombre = $reporte['nombre'] ?? '';
$columnas = $reporte['columnas'] ?? [];
$filas = $reporte['filas'] ?? [];
?>
<?php if ($vistaImpresion): ?>

  <!-- ============= VISTA IMPRIMIR / PDF ============= -->
  <?php render_cabecera_impresion($tipo, $tiposInfo); ?>
  <div class="pagina-impresion">
    <div class="meta no-imprimir">
      <span>Generado el <?= e(date('d/m/Y H:i')) ?></span>
      <span>Inscripciones · Colegio8</span>
    </div>
    <div class="no-imprimir" style="margin-bottom:.8rem;display:flex;gap:.5rem">
      <button class="btn btn-primario" onclick="window.print()">Imprimir / PDF</button>
      <a class="btn btn-fantasma" href="<?= e(url('secretaria/reportes.php?tipo=' . urlencode($tipo) . '&descargar=1')) ?>">Descargar CSV</a>
      <a class="btn btn-fantasma" href="<?= e(url('secretaria/reportes.php?tipo=' . urlencode($tipo))) ?>">← Volver</a>
    </div>
    <table>
      <thead><tr><?php foreach ($columnas as $tit => $k): ?><th><?= e($tit) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php foreach ($filas as $f): ?>
          <tr><?php foreach ($columnas as $k): ?><td><?= e($f[$k] ?? '') ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php else: ?>

  <div class="encabezado-pagina">
    <div><h2>Reportes</h2><p>Listados descargables (CSV) y vista para imprimir/PDF.</p></div>
  </div>

  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.1rem">
    <?php foreach ($tiposInfo as $tk => $ti): ?>
      <a class="btn btn-pequeno <?= $tipo === $tk ? 'btn-primario' : 'btn-fantasma' ?>" href="<?= url('secretaria/reportes.php?tipo=' . $tk) ?>"><?= e($ti[0]) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="tarjeta">
    <div class="encabezado-pagina" style="margin-bottom:.6rem">
      <div>
        <h3 class="titulo-seccion" style="margin:0"><?= e($tiposInfo[$tipo][0]) ?></h3>
        <p style="font-size:.9rem;color:var(--gris-500)"><?= e($tiposInfo[$tipo][1]) ?></p>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a class="btn btn-primario" href="<?= url('secretaria/reportes.php?tipo=' . urlencode($tipo) . '&descargar=1') ?>">⬇ Descargar CSV</a>
        <a class="btn btn-fantasma" href="<?= url('secretaria/reportes.php?tipo=' . urlencode($tipo) . '&imprimir=1') ?>">Imprimir / PDF</a>
      </div>
    </div>
    <div class="tabla-recorrido">
      <table class="tabla">
        <thead><tr><?php foreach ($columnas as $tit => $k): ?><th><?= e($tit) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php if (!$filas): ?>
            <tr><td colspan="<?= max(1, count($columnas)) ?>" class="fila-media" style="padding:1.4rem;text-align:center">Sin datos para este reporte.</td></tr>
          <?php endif; ?>
          <?php foreach ($filas as $f): ?>
            <tr><?php foreach ($columnas as $k): ?><td><?= e($f[$k] ?? '') ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php endif; ?>