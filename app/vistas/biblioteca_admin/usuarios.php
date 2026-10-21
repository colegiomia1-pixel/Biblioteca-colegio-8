<?php
/** Vista: usuarios de la biblioteca (bibliotecario). */
$seccion   = $seccion ?? 'alumnos';
$alumnos   = $alumnos ?? [];
$profesores = $profesores ?? [];
$biblios   = $biblios ?? [];
$bibId     = (int) ($bibId ?? 0);
$filtroAl  = $filtroAl ?? '';
$errores   = $errores ?? [];
$valores   = $valores ?? [];
$seccionValida = ['alumnos', 'profesores', 'bibliotecarios'];
if (!in_array($seccion, $seccionValida, true)) $seccion = 'alumnos';
?>
<div class="encabezado-pagina">
  <div>
    <h2>Usuarios de la biblioteca</h2>
    <p>Alumnos del colegio, profesores con acceso de lector y cuentas de bibliotecario.</p>
  </div>
</div>

<?php if ($errores): ?>
  <div class="aviso-error" role="alert">
    <ul style="margin:0;padding-left:1.1rem"><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<nav class="pestanas" role="tablist">
  <a class="pestana <?= $seccion === 'alumnos' ? 'activa' : '' ?>" href="<?= url('biblioteca-admin/usuarios.php?s=alumnos') ?>">Alumnos</a>
  <a class="pestana <?= $seccion === 'profesores' ? 'activa' : '' ?>" href="<?= url('biblioteca-admin/usuarios.php?s=profesores') ?>">Profesores</a>
  <a class="pestana <?= $seccion === 'bibliotecarios' ? 'activa' : '' ?>" href="<?= url('biblioteca-admin/usuarios.php?s=bibliotecarios') ?>">Bibliotecarios</a>
</nav>

<?php if ($seccion === 'alumnos'): ?>
  <div class="tarjeta">
    <form method="get" class="formulario" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:end">
      <input type="hidden" name="s" value="alumnos">
      <div class="campo" style="flex:1;min-width:220px">
        <label for="alumno_buscar">Buscar alumno</label>
        <input type="text" id="alumno_buscar" name="alumno_buscar" value="<?= e($filtroAl) ?>" placeholder="Nombre, apellido o DNI">
      </div>
      <button class="btn btn-primario" type="submit">Buscar</button>
    </form>
    <div class="tabla-recorrido" style="margin-top:.8rem">
      <table class="tabla">
        <thead><tr><th>Alumno</th><th>DNI</th><th>Curso</th><th>Contacto</th><th>Editar contacto</th></tr></thead>
        <tbody>
          <?php if (!$alumnos): ?>
            <tr><td colspan="5" class="fila-media" style="text-align:center;padding:1.2rem">No se encontraron alumnos.</td></tr>
          <?php endif; ?>
          <?php foreach ($alumnos as $a): ?>
            <tr>
              <td><strong><?= e($a['apellido'] . ', ' . $a['nombre']) ?></strong></td>
              <td class="fila-media"><?= e($a['dni'] ?? '—') ?></td>
              <td class="fila-media"><?= $a['anio'] ? (int) $a['anio'] . '° ' . e($a['division'] ?? '') . ' · ' . e($a['turno'] ?? '') : 'Sin curso' ?></td>
              <td class="fila-media"><?= e($a['telefono'] ?? '—') ?><br><?= e($a['email'] ?? '') ?></td>
              <td>
                <details>
                  <summary class="enlace">Editar…</summary>
                  <form method="post" class="formulario" novalidate style="margin-top:.5rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="editar_alumno">
                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                    <div class="fila-campos">
                      <div class="campo">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" value="<?= e($a['telefono'] ?? '') ?>">
                      </div>
                      <div class="campo">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= e($a['email'] ?? '') ?>">
                      </div>
                    </div>
                    <button class="btn btn-pequeno btn-primario" type="submit">Guardar</button>
                  </form>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="ayuda" style="margin-top:.6rem">Los datos de la inscripción (fecha de nacimiento, documentos…) se administran en el módulo de secretaría.</p>
  </div>

<?php elseif ($seccion === 'profesores'): ?>
  <div class="tarjeta">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap">
      <h3 class="titulo-seccion" style="margin:0">Profesores con acceso a biblioteca</h3>
      <details>
        <summary class="btn btn-primario btn-pequeno" style="cursor:pointer">+ Nuevo profesor</summary>
        <form method="post" class="formulario" novalidate style="margin-top:.8rem">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="crear_profesor">
          <div class="fila-campos">
            <div class="campo"><label>Nombre *</label><input type="text" name="nombre" required value="<?= e($valores['profNombre'] ?? '') ?>"></div>
            <div class="campo"><label>Apellido *</label><input type="text" name="apellido" required value="<?= e($valores['profApellido'] ?? '') ?>"></div>
            <div class="campo"><label>DNI *</label><input type="text" name="dni" required value="<?= e($valores['profDni'] ?? '') ?>"></div>
          </div>
          <div class="fila-campos">
            <div class="campo"><label>Email</label><input type="email" name="email" value="<?= e($valores['profEmail'] ?? '') ?>"></div>
            <div class="campo"><label>Teléfono</label><input type="text" name="telefono" value="<?= e($valores['profTel'] ?? '') ?>"></div>
          </div>
          <div class="fila-campos">
            <div class="campo"><label>Usuario (acceso lector) *</label><input type="text" name="usuario" required value="<?= e($valores['profUsuario'] ?? '') ?>"></div>
            <div class="campo"><label>Contraseña *</label><input type="password" name="password" required autocomplete="new-password"></div>
          </div>
          <button class="btn btn-pequeno btn-primario" type="submit">Crear profesor</button>
        </form>
      </details>
    </div>

    <div class="tabla-recorrido" style="margin-top:.8rem">
      <table class="tabla">
        <thead><tr><th>Profesor</th><th>DNI</th><th>Contacto</th><th>Usuario</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php if (!$profesores): ?>
            <tr><td colspan="6" class="fila-media" style="text-align:center;padding:1.2rem">Todavía no hay profesores cargados.</td></tr>
          <?php endif; ?>
          <?php foreach ($profesores as $pr): ?>
            <tr style="<?= $pr['activo'] ? '' : 'opacity:.55' ?>">
              <td><strong><?= e($pr['apellido'] . ', ' . $pr['nombre']) ?></strong></td>
              <td class="fila-media"><?= e($pr['dni']) ?></td>
              <td class="fila-media"><?= e($pr['telefono'] ?? '—') ?><br><?= e($pr['email'] ?? '') ?></td>
              <td class="fila-media"><?= e($pr['usuario']) ?></td>
              <td><span class="badge <?= $pr['activo'] ? 'badge-verde' : 'badge-rojo' ?>"><?= $pr['activo'] ? 'Activo' : 'Desactivado' ?></span></td>
              <td class="acciones-fila">
                <details>
                  <summary class="enlace">Editar…</summary>
                  <form method="post" class="formulario" novalidate style="margin-top:.5rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="<?= $pr['activo'] ? 'editar_profesor' : 'reactivar_profesor' ?>">
                    <input type="hidden" name="id" value="<?= (int) $pr['id'] ?>">
                    <?php if ($pr['activo']): ?>
                      <div class="fila-campos">
                        <div class="campo"><label>Nombre</label><input type="text" name="nombre" value="<?= e($pr['nombre']) ?>"></div>
                        <div class="campo"><label>Apellido</label><input type="text" name="apellido" value="<?= e($pr['apellido']) ?>"></div>
                        <div class="campo"><label>DNI</label><input type="text" name="dni" value="<?= e($pr['dni']) ?>"></div>
                      </div>
                      <div class="fila-campos">
                        <div class="campo"><label>Usuario</label><input type="text" name="usuario" value="<?= e($pr['usuario']) ?>"></div>
                        <div class="campo"><label>Contraseña nueva (opcional)</label><input type="password" name="password" autocomplete="new-password"></div>
                        <div class="campo"><label>Email</label><input type="email" name="email" value="<?= e($pr['email'] ?? '') ?>"></div>
                      </div>
                      <div class="campo"><label>Teléfono</label><input type="text" name="telefono" value="<?= e($pr['telefono'] ?? '') ?>"></div>
                      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <button class="btn btn-pequeno btn-primario" type="submit">Guardar</button>
                      </div>
                    <?php else: ?>
                      <p class="ayuda">Este profesor está desactivado y no puede ingresar.</p>
                      <button class="btn btn-pequeno btn-primario" type="submit">Reactivar</button>
                    <?php endif; ?>
                  </form>
                  <?php if ($pr['activo']): ?>
                    <form method="post" style="margin-top:.5rem" data-confirm="¿Desactivar el acceso de <?= e($pr['nombre'] . ' ' . $pr['apellido']) ?>?" novalidate>
                      <?= csrf_field() ?>
                      <input type="hidden" name="accion" value="desactivar_profesor">
                      <input type="hidden" name="id" value="<?= (int) $pr['id'] ?>">
                      <button class="btn btn-pequeno btn-peligro" type="submit">Desactivar acceso</button>
                    </form>
                  <?php endif; ?>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php elseif ($seccion === 'bibliotecarios'): ?>
  <div class="tarjeta">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap">
      <h3 class="titulo-seccion" style="margin:0">Cuentas de bibliotecario</h3>
      <details>
        <summary class="btn btn-primario btn-pequeno" style="cursor:pointer">+ Nueva cuenta</summary>
        <form method="post" class="formulario" novalidate style="margin-top:.8rem">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="crear_bibliotecario">
          <div class="fila-campos">
            <div class="campo"><label>Nombre completo *</label><input type="text" name="nombre" required value="<?= e($valores['bibNombre'] ?? '') ?>"></div>
            <div class="campo"><label>Usuario *</label><input type="text" name="usuario" required value="<?= e($valores['bibUsuario'] ?? '') ?>"></div>
            <div class="campo"><label>Contraseña *</label><input type="password" name="password" required autocomplete="new-password"></div>
          </div>
          <button class="btn btn-pequeno btn-primario" type="submit">Crear bibliotecario</button>
        </form>
      </details>
    </div>
    <div class="tabla-recorrido" style="margin-top:.8rem">
      <table class="tabla">
        <thead><tr><th>Nombre</th><th>Usuario</th><th>Alta</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($biblios as $b): ?>
            <tr>
              <td><strong><?= e($b['nombre_completo']) ?></strong><?= (int) $b['id'] === $bibId ? ' <span class="badge badge-verde">Sos vos</span>' : '' ?></td>
              <td class="fila-media"><?= e($b['usuario']) ?></td>
              <td class="fila-media"><?= e(fecha_es($b['fecha_alta'])) ?></td>
              <td></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="ayuda" style="margin-top:.6rem">La primera cuenta se crea desde «Primer uso»; las adicionales se crean acá.</p>
  </div>
<?php endif; ?>