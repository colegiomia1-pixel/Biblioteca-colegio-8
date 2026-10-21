<?php
/** Vista: Gestión de reservas y reseñas (biblioteca_admin). */
$reservas = $reservas ?? [];
$resenas = $resenas ?? [];
?>
<div class="encabezado-pagina">
  <h2>Gestión de Reservas y Moderación de Reseñas</h2>
</div>

<!-- RESERVAS PENDIENTES -->
<div class="tarjeta" style="margin-bottom: 1.5rem;">
  <h3 class="titulo-seccion">Lista de Espera / Reservas Pendientes</h3>
  <?php if (empty($reservas)): ?>
    <p class="texto-atenuado">No hay reservas pendientes en este momento.</p>
  <?php else: ?>
    <table class="tabla" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>Libro</th>
          <th>Solicitante</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reservas as $r): ?>
          <tr>
            <td>
              <strong><?= e($r['libro_titulo']) ?></strong>
              <?php if (!empty($r['codigo_interno'])): ?>
                <br><small class="texto-atenuado">Cód: <?= e($r['codigo_interno']) ?></small>
              <?php endif; ?>
            </td>
            <td><?= e($r['solicitante'] ?? 'Desconocido') ?></td>
            <td><?= date('d/m/Y H:i', strtotime($r['fecha_creacion'])) ?></td>
            <td>
              <form method="post" style="display:inline-block;">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="completar_reserva">
                <input type="hidden" name="reserva_id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-exito btn-sm" type="submit">Completar / Entregado</button>
              </form>
              <form method="post" style="display:inline-block;">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="cancelar_reserva">
                <input type="hidden" name="reserva_id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-peligro btn-sm" type="submit" onclick="return confirm('¿Cancelar esta reserva?');">Cancelar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- MODERACIÓN DE RESEÑAS -->
<div class="tarjeta">
  <h3 class="titulo-seccion">Moderación de Reseñas y Calificaciones</h3>
  <?php if (empty($resenas)): ?>
    <p class="texto-atenuado">No hay reseñas publicadas aún.</p>
  <?php else: ?>
    <table class="tabla" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>Libro</th>
          <th>Usuario</th>
          <th>Puntuación</th>
          <th>Comentario</th>
          <th>Fecha</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($resenas as $res): ?>
          <tr>
            <td><strong><?= e($res['libro_titulo']) ?></strong></td>
            <td><?= e($res['usuario'] ?? 'Anónimo') ?></td>
            <td><?= str_repeat('⭐', (int)$res['puntuacion']) ?> (<?= (int)$res['puntuacion'] ?>/5)</td>
            <td><?= e($res['comentario'] ?: '— Sin comentario —') ?></td>
            <td><?= date('d/m/Y', strtotime($res['fecha_creacion'])) ?></td>
            <td>
              <form method="post" style="display:inline-block;">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="eliminar_resena">
                <input type="hidden" name="resena_id" value="<?= (int)$res['id'] ?>">
                <button class="btn btn-peligro btn-sm" type="submit" onclick="return confirm('¿Eliminar esta reseña?');">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>