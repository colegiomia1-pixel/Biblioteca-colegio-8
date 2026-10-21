<?php
/** Vista: gestión de cupos y cursos. */
$cursos  = $cursos ?? [];
$cursosTotal = (int) ($cursosTotal ?? 0);
$anios   = $anios ?? [];
$valores = $valores ?? [];
$editar  = $editar ?? null;
$errores = $errores ?? [];
$vistaForm = $vistaForm ?? '';
$dictOcupacion = $dictOcupacion ?? [];
$filtroTurno     = (string) ($filtroTurno ?? '');
$filtroOcupacion = (string) ($filtroOcupacion ?? '');
$filtroEstado    = (string) ($filtroEstado ?? '');

$nivelCupo = function (int $ocupado, int $total): array {
    if ($total === 0) return ['', ''];
    $pct = $ocupado / $total;
    if ($ocupado >= $total) return ['barra-cupo llena', 'badge-rojo', 'Lleno'];
    if ($pct >= 0.8)      return ['barra-cupo casi', 'badge-ambar', 'Casi lleno'];
    return ['', 'badge-verde', 'Con lugar'];
};
?>
<div class="encabezado-pagina">
  <div>
    <h2>Catálogo de cursos y cupos</h2>
    <p><?= count($cursos) ?> de <?= $cursosTotal ?> curso(s) según los filtros.</p>
  </div>
  <a class="btn btn-primario" href="<?= url('secretaria/vacantes.php?vista=alta') ?>">+ Nuevo curso</a>
</div>

<div class="tarjeta" style="padding:.8rem 1rem;margin-bottom:1rem">
  <form method="get" class="fila-campos" style="align-items:flex-end;gap:.6rem;flex-wrap:wrap">
    <input type="hidden" name="vista" value="">
    <div class="campo" style="min-width:130px;margin:0">
      <label for="f-turno">Turno</label>
      <select id="f-turno" name="turno">
        <option value="">Todos</option>
        <option value="manana" <?= $filtroTurno === 'manana' ? 'selected' : '' ?>>Mañana</option>
        <option value="tarde"  <?= $filtroTurno === 'tarde' ? 'selected' : '' ?>>Tarde</option>
      </select>
    </div>
    <div class="campo" style="min-width:150px;margin:0">
      <label for="f-ocupacion">Ocupación</label>
      <select id="f-ocupacion" name="ocupacion">
        <option value="">Todas</option>
        <?php foreach ($dictOcupacion as $ok => $ov): ?>
          <option value="<?= e($ok) ?>" <?= $filtroOcupacion === $ok ? 'selected' : '' ?>><?= e($ov) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo" style="min-width:130px;margin:0">
      <label for="f-estado">Estado</label>
      <select id="f-estado" name="estado">
        <option value="">Todos</option>
        <option value="activo"   <?= $filtroEstado === 'activo' ? 'selected' : '' ?>>Activo</option>
        <option value="inactivo" <?= $filtroEstado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
      </select>
    </div>
    <button class="btn btn-primario" type="submit">Filtrar</button>
    <?php if ($filtroTurno !== '' || $filtroOcupacion !== '' || $filtroEstado !== ''): ?>
      <a class="btn btn-fantasma" href="<?= url('secretaria/vacantes.php') ?>">Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<?php if ($errores): ?>
  <div class="aviso-error" role="alert">
    <strong>Revisá estos datos:</strong>
    <ul style="margin:.4rem 0 0 1.1rem;padding:0">
      <?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($vistaForm === 'alta' || $editar): ?>
<div class="tarjeta" style="margin-bottom:1.2rem">
  <h3 class="titulo-seccion"><?= $editar ? 'Editar curso' : 'Nuevo curso' ?></h3>
  <form method="post" class="formulario formulario-amplio" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="<?= $editar ? 'actualizar' : 'crear' ?>">
    <?php if ($editar): ?><input type="hidden" name="id" value="<?= (int) $editar['id'] ?>"><?php endif; ?>
    <div class="fila-campos">
      <div class="campo">
        <label for="anio">Año <span class="requerido">*</span></label>
        <select id="anio" name="anio" required>
          <?php foreach ($anios as $a): ?>
            <option value="<?= $a ?>" <?= (int) $valores['anio'] === $a ? 'selected' : '' ?>><?= $a ?>° año</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label for="turno">Turno <span class="requerido">*</span></label>
        <select id="turno" name="turno" required>
          <option value="manana" <?= ($valores['turno'] ?? '') === 'manana' ? 'selected' : '' ?>>Mañana</option>
          <option value="tarde"  <?= ($valores['turno'] ?? '') === 'tarde' ? 'selected' : '' ?>>Tarde</option>
        </select>
      </div>
      <div class="campo">
        <label for="division">División <span class="requerido">*</span></label>
        <input type="text" id="division" name="division" maxlength="1" required
               value="<?= e($valores['division']) ?>" placeholder="A">
      </div>
      <div class="campo">
        <label for="cupo_total">Cupo máximo <span class="requerido">*</span></label>
        <input type="number" id="cupo_total" name="cupo_total" min="1" max="300" required
               value="<?= (int) $valores['cupo_total'] ?>">
      </div>
      <?php if ($editar): ?>
        <div class="campo">
          <label for="activo">Estado</label>
          <select id="activo" name="activo">
            <option value="1" <?= (int) $valores['activo'] === 1 ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= (int) $valores['activo'] === 0 ? 'selected' : '' ?>>Inactivo</option>
          </select>
        </div>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:.6rem">
      <button class="btn btn-primario" type="submit"><?= $editar ? 'Guardar cambios' : 'Crear curso' ?></button>
      <a class="btn btn-fantasma" href="<?= url('secretaria/vacantes.php') ?>">Cancelar</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="tarjeta">
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead>
        <tr><th>Curso</th><th>Cupo</th><th>Ocupación</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$cursos): ?>
          <tr><td colspan="5" class="fila-media" style="padding:1.4rem;text-align:center">
            No hay cursos que coincidan con los filtros.
          </td></tr>
        <?php endif; ?>
        <?php foreach ($cursos as $c):
            [$clsBarra, $clsBadge, $txtBadge] = $nivelCupo((int) $c['cupo_ocupado'], (int) $c['cupo_total']);
            $pct = $c['cupo_total'] > 0 ? round(((int) $c['cupo_ocupado'] / (int) $c['cupo_total']) * 100) : 0;
        ?>
          <tr style="<?= $c['activo'] ? '' : 'opacity:.55' ?>">
            <td>
              <a href="<?= url('secretaria/curso.php?id=' . (int) $c['id']) ?>" style="font-weight:650"><?= e(curso_titulo($c)) ?></a>
              <?php if (!$c['activo']): ?><span class="badge badge-gris">inactivo</span><?php endif; ?>
            </td>
            <td><?= (int) $c['cupo_ocupado'] ?> / <?= (int) $c['cupo_total'] ?></td>
            <td style="min-width:160px">
              <div class="barra-cupo <?= $clsBarra ?>"><span style="width:<?= $pct ?>%"></span></div>
              <span style="font-size:.75rem;color:var(--gris-400)"><?= $pct ?>% ocupado</span>
            </td>
            <td><span class="badge <?= (int) $c['activo'] === 1 ? 'badge-verde' : 'badge-gris' ?>"><?= (int) $c['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="acciones-fila">
              <a class="btn btn-pequeno btn-primario" href="<?= url('secretaria/curso.php?id=' . (int) $c['id']) ?>">Ver alumnos</a>
              <a class="btn btn-pequeno btn-fantasma" href="<?= url('secretaria/vacantes.php?vista=editar&id=' . (int) $c['id']) ?>">Editar</a>
              <?php if ((int) $c['cupo_ocupado'] === 0): ?>
                <form method="post" style="display:inline" data-confirm="¿Eliminar el curso <?= e(curso_titulo($c)) ?>?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                  <button class="btn btn-pequeno btn-peligro" type="submit">Eliminar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>