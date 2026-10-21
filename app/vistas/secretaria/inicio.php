<?php
/** Vista: inicio de secretaría. */
$sec      = $sec ?? [];
$recientes = $recientes ?? [];
$badges = [
    'recibida'    => ['badge-gris',  'Recibida'],
    'en_revision' => ['badge-ambar', 'En revisión'],
    'aprobada'    => ['badge-verde', 'Aprobada'],
    'rechazada'   => ['badge-rojo',  'Rechazada'],
];
?>
<div class="encabezado-pagina">
  <div>
    <h2>Hola, <?= e($sec['nombre'] ?? '') ?></h2>
    <p>Resumen en tiempo real del módulo de inscripciones.</p>
  </div>
  <div style="display:flex;gap:.6rem;flex-wrap:wrap">
    <a class="btn btn-primario" href="<?= url('secretaria/bandeja.php') ?>">Revisar solicitudes</a>
    <a class="btn btn-fantasma" href="<?= url('secretaria/asignar.php') ?>">Asignar cursos</a>
  </div>
</div>

<div class="rejilla rejilla-4">
  <div class="tarjeta tarjeta-stats destacada">
    <span class="stats-num" data-contar="<?= (int) $pendientes ?>"><?= e(number_format($pendientes)) ?></span>
    <span class="stats-etq">Solicitudes por revisar</span>
    <span class="stats-sub">Recibidas + en revisión</span>
  </div>
  <div class="tarjeta tarjeta-stats">
    <span class="stats-num" data-contar="<?= (int) $aprobadasSinAsignar ?>"><?= e(number_format($aprobadasSinAsignar)) ?></span>
    <span class="stats-etq">Aprobadas sin asignar</span>
    <span class="stats-sub">Listas para asignar curso</span>
  </div>
  <div class="tarjeta tarjeta-stats">
    <span class="stats-num" data-contar="<?= (int) $inscriptos ?>"><?= e(number_format($inscriptos)) ?></span>
    <span class="stats-etq">Alumnos inscriptos</span>
    <span class="stats-sub">Con división asignada</span>
  </div>
  <div class="tarjeta tarjeta-stats">
    <span class="stats-num" data-contar="<?= (int) $enEspera ?>"><?= e(number_format($enEspera)) ?></span>
    <span class="stats-etq">En lista de espera</span>
    <span class="stats-sub"><?= $cursosLlenos ?> de <?= $totalVacantes ?> cursos llenos</span>
  </div>
</div>

<div class="tarjeta" style="margin-top:1.2rem">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap">
    <h3 class="titulo-seccion" style="margin:0">Últimas solicitudes</h3>
    <a href="<?= url('secretaria/bandeja.php') ?>">Ver todas →</a>
  </div>
  <div class="tabla-recorrido">
    <table class="tabla">
      <thead>
        <tr><th>Alumno</th><th>Año</th><th>Estado</th><th>Presentada</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$recientes): ?>
          <tr><td colspan="5" class="fila-media">Todavía no hay solicitudes.</td></tr>
        <?php endif; ?>
        <?php foreach ($recientes as $r): ?>
          <tr>
            <td><?= e($r['apellido'] . ', ' . $r['nombre']) ?></td>
            <td><?= (int) $r['anio_postulado'] ?>° año</td>
            <td><span class="badge <?= $badges[$r['estado']][0] ?>"><?= $badges[$r['estado']][1] ?></span></td>
            <td class="fila-media"><?= e(fecha_es($r['fecha_presentacion'], true)) ?></td>
            <td class="acciones-fila">
              <a class="btn btn-pequeno btn-primario" href="<?= url('secretaria/revision.php?id=' . (int) $r['id']) ?>">Revisar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>