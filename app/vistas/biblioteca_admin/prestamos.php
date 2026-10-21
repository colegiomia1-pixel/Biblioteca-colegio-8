<?php
/** Vista: préstamos (bibliotecario). */
$alumnos  = $alumnos ?? [];
$profesores = $profesores ?? [];
$librosDisp = $librosDisp ?? [];
$activos  = $activos ?? [];
$historial = $historial ?? [];
$errores  = $errores ?? [];
$vistaForm = $vistaForm ?? '';
$dias     = $diasPrestamo ?? 15;

$alt = fn(string $fecha) => strtotime($fecha) < time();

/* Datos para los autocompletados (los camina app.js). */
$personasJson = [];
foreach ($alumnos as $a) {
    $personasJson[] = [
        'v' => 'alumno:' . (int) $a['id'],
        'etiqueta' => $a['apellido'] . ', ' . $a['nombre'],
        'sub' => ($a['dni'] ? 'DNI ' . $a['dni'] . ' · ' : '') . 'Alumno',
    ];
}
foreach ($profesores as $p) {
    $personasJson[] = [
        'v' => 'profesor:' . (int) $p['id'],
        'etiqueta' => $p['apellido'] . ', ' . $p['nombre'],
        'sub' => ($p['dni'] ? 'DNI ' . $p['dni'] . ' · ' : '') . 'Profesor',
    ];
}
$librosJson = [];
foreach ($librosDisp as $l) {
    $librosJson[] = [
        'v' => (int) $l['id'],
        'etiqueta' => $l['titulo'] . ' — ' . $l['autor'],
        'sub' => (int) $l['ejemplares_disponibles'] . ' ejemplar(es) disponible(s) · ' . $l['codigo_interno'],
    ];
}
usort($personasJson, fn($a, $b) => strcmp(mb_strtolower($a['etiqueta']), mb_strtolower($b['etiqueta'])));
usort($librosJson, fn($a, $b) => strcmp(mb_strtolower($a['etiqueta']), mb_strtolower($b['etiqueta'])));
?>
<style>
  .autocompletar-lista li button { grid-template-columns: 1fr; }
</style>
<div class="encabezado-pagina">
  <div>
    <h2>Préstamos</h2>
    <p><?= count($activos) ?> activo(s). Registrar uno nuevo descuenta el ejemplar disponible.</p>
  </div>
  <a class="btn btn-primario" href="<?= url('biblioteca-admin/prestamos.php?vista=alta') ?>">+ Registrar préstamo</a>
</div>

<?php if ($errores): ?>
  <div class="aviso-error" role="alert">
    <strong>No se pudo completar la operación:</strong>
    <ul style="margin:.4rem 0 0 1.1rem;padding:0">
      <?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($vistaForm === 'alta'): ?>
<script type="application/json" id="datos-personas"><?= json_encode($personasJson, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?></script>
<script type="application/json" id="datos-libros"><?= json_encode($librosJson, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?></script>
<div class="tarjeta" style="margin-bottom:1.2rem">
  <h3 class="titulo-seccion">Nuevo préstamo</h3>
  <form method="post" class="formulario formulario-amplio" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="crear">
    <div class="fila-campos">
      <div class="campo autocompletar">
        <label for="buscarPersona">Persona que retira <span class="requerido">*</span></label>
        <div class="autocompletar-input">
          <input type="text" id="buscarPersona" data-autocompletar="personas" autocomplete="off"
                 placeholder="Escribí apellido o nombre…">
          <input type="hidden" name="persona" id="persona">
        </div>
        <ul class="autocompletar-lista" id="lista-personas" role="listbox" aria-label="Resultados"></ul>
        <p class="ayuda">Alumnos del módulo de inscripciones y profesores de la biblioteca (Usuarios).</p>
      </div>
      <div class="campo autocompletar">
        <label for="buscarLibro">Libro (con ejemplares disponibles) <span class="requerido">*</span></label>
        <div class="autocompletar-input">
          <input type="text" id="buscarLibro" data-autocompletar="libros" autocomplete="off"
                 placeholder="Escribí título o autor…">
          <input type="hidden" name="libro_id" id="libro_id">
        </div>
        <ul class="autocompletar-lista" id="lista-libros" role="listbox" aria-label="Resultados"></ul>
      </div>
    </div>
    <div class="fila-campos">
      <div class="campo">
        <label for="fecha_prestamo">Fecha de préstamo <span class="requerido">*</span></label>
        <input type="date" id="fecha_prestamo" name="fecha_prestamo" required value="<?= e(hoy_iso()) ?>">
      </div>
      <div class="campo">
        <label for="fecha_limite">Fecha límite de devolución <span class="requerido">*</span></label>
        <input type="date" id="fecha_limite" name="fecha_limite" required value="<?= e(date('Y-m-d', strtotime('+' . $dias . ' days'))) ?>">
        <p class="ayuda">Préstamo estándar: <?= (int) $dias ?> días.</p>
      </div>
    </div>
    <div style="display:flex;gap:.6rem">
      <button class="btn btn-primario" type="submit">Registrar préstamo</button>
      <a class="btn btn-fantasma" href="<?= url('biblioteca-admin/prestamos.php') ?>">Cancelar</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="tarjeta">
  <h3 class="titulo-seccion">Préstamos activos / vencidos</h3>
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead><tr><th>Persona</th><th>DNI</th><th>Libro</th><th>Prestado</th><th>Límite</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php if (!$activos): ?>
          <tr><td colspan="7" class="fila-media" style="padding:1.4rem;text-align:center">No hay préstamos activos.</td></tr>
        <?php endif; ?>
        <?php foreach ($activos as $p): $vencido = $p['estado'] === 'vencido' || $alt($p['fecha_limite']); ?>
          <tr class="<?= $vencido ? 'fila-vencido' : '' ?>">
            <td><strong><?= e($p['persona']) ?></strong></td>
            <td class="fila-media"><?= e($p['dni_persona'] ?? '—') ?></td>
            <td><?= e($p['titulo']) ?><br><span class="fila-media" style="font-size:.78rem"><?= e($p['codigo_interno']) ?></span></td>
            <td class="fila-media"><?= e(fecha_es($p['fecha_prestamo'])) ?></td>
            <td class="fila-media"><?= e(fecha_es($p['fecha_limite'])) ?></td>
            <td><span class="badge <?= $vencido ? 'badge-rojo' : 'badge-verde' ?>"><?= $vencido ? 'Vencido' : 'Activo' ?></span></td>
            <td class="acciones-fila">
              <form method="post" style="display:inline" data-confirm="¿Registrar la devolución de «<?= e($p['titulo']) ?>»?">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="devolver">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="btn btn-pequeno btn-primario" type="submit">Devolver</button>
              </form>
              <form method="post" style="display:inline" data-confirm="¿Cancelar este préstamo?">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="cancelar">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="btn btn-pequeno btn-fantasma" type="submit">Cancelar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="tarjeta" style="margin-top:1.2rem">
  <h3 class="titulo-seccion">Historial reciente (devueltos / cancelados)</h3>
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead><tr><th>Persona</th><th>Libro</th><th>Prestado</th><th>Devuelto</th><th>Estado</th></tr></thead>
      <tbody>
        <?php if (!$historial): ?>
          <tr><td colspan="5" class="fila-media" style="padding:1.4rem;text-align:center">Sin devoluciones registradas aún.</td></tr>
        <?php endif; ?>
        <?php foreach ($historial as $h): ?>
          <tr>
            <td><?= e($h['persona']) ?></td>
            <td><?= e($h['titulo']) ?></td>
            <td class="fila-media"><?= e(fecha_es($h['fecha_prestamo'])) ?></td>
            <td class="fila-media"><?= e(fecha_es($h['fecha_devolucion'])) ?></td>
            <td><span class="badge <?= $h['estado'] === 'devuelto' ? 'badge-verde' : 'badge-gris' ?>"><?= e(ucfirst($h['estado'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>