<?php
/** Vista: reportes de biblioteca. */
$reporte        = $reporte ?? ['nombre' => '', 'columnas' => [], 'filas' => []];
$tiposInfo      = $tiposInfo ?? [];
$vistaImpresion = $vistaImpresion ?? false;
$nombre         = $reporte['nombre'] ?? '';
$columnas       = $reporte['columnas'] ?? [];
$filas          = $reporte['filas'] ?? [];
$baseUrl        = 'biblioteca-admin/reportes.php';
?>

<?php if ($vistaImpresion): ?>

  <!-- ============= VISTA IMPRIMIR / PDF ============= -->
  <?php render_cabecera_impresion($tipo, $tiposInfo, 'Biblioteca'); ?>
  <div class="pagina-impresion p-4">
    <div class="meta no-imprimir d-flex justify-content-between mb-3 text-secondary small">
      <span><i class="bi bi-clock me-1"></i> Generado el <?= e(date('d/m/Y H:i')) ?></span>
      <span>Biblioteca · Colegio8</span>
    </div>

    <div class="no-imprimir d-flex gap-2 mb-4">
      <button class="btn btn-primario" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Imprimir / PDF
      </button>
      <a class="btn btn-fantasma" href="<?= e(url($baseUrl . '?tipo=' . urlencode($tipo) . '&descargar=1')) ?>">
        <i class="bi bi-download me-1"></i> Descargar CSV
      </a>
      <a class="btn btn-fantasma" href="<?= e(url($baseUrl . '?tipo=' . urlencode($tipo))) ?>">
        <i class="bi bi-arrow-left me-1"></i> Volver
      </a>
    </div>

    <div class="tabla-recorrido">
      <table class="tabla align-middle">
        <thead>
          <tr>
            <?php foreach ($columnas as $tit => $k): ?>
              <th><?= e($tit) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filas as $f): ?>
            <tr>
              <?php foreach ($columnas as $k): ?>
                <td><?= e($f[$k] ?? '') ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>

  <!-- ============= VISTA PANEL ADMINISTRACIÓN ============= -->
  <div class="encabezado-pagina mb-4">
    <div>
      <h2 class="mb-1 fw-bold text-dark"><i class="bi bi-bar-chart-line-fill text-success me-2"></i>Reportes de Biblioteca</h2>
      <p class="text-secondary mb-0">Listados descargables en CSV y vistas optimizadas para impresión o exportación a PDF.</p>
    </div>
  </div>

  <!-- Pestañas de Selector de Tipo de Reporte -->
  <div class="pestanas mb-4">
    <?php foreach ($tiposInfo as $tk => $ti): ?>
      <a class="pestana <?= $tipo === $tk ? 'activa' : '' ?>" href="<?= url($baseUrl . '?tipo=' . $tk) ?>">
        <?= e($ti[0]) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Tarjeta Contenedora del Reporte -->
  <div class="tarjeta p-4 shadow-sm border-0 rounded-4">
    <div class="encabezado-pagina pb-3 mb-4 border-bottom">
      <div>
        <h3 class="titulo-seccion text-uppercase fw-bold text-dark mb-1" style="margin:0">
          <?= e($tiposInfo[$tipo][0] ?? 'Reporte') ?>
        </h3>
        <p class="mb-0 text-muted small"><?= e($tiposInfo[$tipo][1] ?? '') ?></p>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-primario rounded-3" href="<?= url($baseUrl . '?tipo=' . urlencode($tipo) . '&descargar=1') ?>">
          <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Descargar CSV
        </a>
        <a class="btn btn-fantasma rounded-3" href="<?= url($baseUrl . '?tipo=' . urlencode($tipo) . '&imprimir=1') ?>">
          <i class="bi bi-printer-fill me-1"></i> Imprimir / PDF
        </a>
      </div>
    </div>

    <!-- Tabla de Datos -->
    <div class="tabla-recorrido">
      <table class="tabla align-middle mb-0">
        <thead>
          <tr>
            <?php foreach ($columnas as $tit => $k): ?>
              <th class="py-3"><?= e($tit) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (!$filas): ?>
            <tr>
              <td colspan="<?= max(1, count($columnas)) ?>" class="fila-media text-center py-5 text-muted">
                <i class="bi bi-inbox display-6 d-block mb-2 opacity-50"></i>
                Sin datos disponibles para este reporte en este momento.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($filas as $f): ?>
              <tr>
                <?php foreach ($columnas as $k): ?>
                  <td><?= e($f[$k] ?? '') ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php endif; ?>