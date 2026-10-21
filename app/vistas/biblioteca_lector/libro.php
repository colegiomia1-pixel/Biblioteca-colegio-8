<?php
/** Vista: detalle de un libro (lector). */
$lec = $lec ?? [];
$visita = empty($lec['persona_id'] ?? null);
$libro = $libro ?? [];
$similares = $similares ?? [];
$rating = $rating ?? ['promedio' => 0, 'total' => 0];
$tieneStock = (int) ($libro['ejemplares_disponibles'] ?? 0) > 0;
?>
<div class="encabezado-pagina">
  <div>
    <a class="enlace" href="<?= url('biblioteca-lector/catalogo.php') ?>">← Volver al catálogo</a>
    <h2 style="margin-top:.4rem"><?= e($libro['titulo']) ?></h2>
  </div>
</div>

<div class="rejilla rejilla-2" style="align-items:start">
  <div class="libro-delantal">
    <div class="libro-portada libro-portada-grande">
      <?= portada_libro_contenido($libro) ?>
    </div>
    <?php if ($visita): ?>
      <div class="aviso-info" role="status">
        Estás viendo este libro como visitante. Para guardarlo en favoritos o pedirlo
        completá tu <a href="<?= url('postulante/aplicacion.php') ?>">solicitud de inscripción</a>.
      </div>
    <?php else: ?>
    <div class="acciones-fila" style="margin-top:.8rem;justify-content:center">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="favorito">
        <input type="hidden" name="id" value="<?= (int) $libro['id'] ?>">
        <button class="btn <?= $libro['es_favorito'] ? 'btn-ambar' : 'btn-fantasma' ?>" type="submit">
          <?= $libro['es_favorito'] ? '★ En tus favoritos' : '☆ Agregar a favoritos' ?>
        </button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <div class="tarjeta">
    <p class="fila-media"><?= e($libro['autor']) ?>
      <?php if ($libro['genero']): ?><span class="badge badge-gris" style="margin-left:.3rem"><?= e($libro['genero']) ?></span><?php endif; ?>
    </p>

    <!-- Calificación general -->
    <p style="margin:.5rem 0; font-size:1rem;">
      <strong>Calificación:</strong> ⭐ <?= $rating['promedio'] ?> / 5 <small class="texto-atenuado">(<?= $rating['total'] ?> opiniones)</small>
    </p>

    <dl class="datos-detalle" style="margin:0 0 .8rem">
      <dt>Código interno</dt><dd><?= e($libro['codigo_interno']) ?></dd>
      <?php if ($libro['editorial']): ?><dt>Editorial</dt><dd><?= e($libro['editorial']) ?></dd><?php endif; ?>
      <?php if ($libro['isbn']): ?><dt>ISBN</dt><dd><?= e($libro['isbn']) ?></dd><?php endif; ?>
      <dt>Disponibilidad</dt>
      <dd>
        <span class="disp <?= $tieneStock ? 'disp-si' : 'disp-no' ?>">
          <?= (int) $libro['ejemplares_disponibles'] ?> ejemplar(es) disponible(s) de <?= (int) $libro['ejemplares_total'] ?>
        </span>
      </dd>
    </dl>

    <?php if ($libro['sinopsis']): ?>
      <h3 class="titulo-seccion">Sinopsis</h3>
      <p style="font-size:.95rem;line-height:1.5"><?= nl2br(e($libro['sinopsis'])) ?></p>
    <?php endif; ?>

    <h3 class="titulo-seccion">Pedir o reservar este libro</h3>
    <?php if ($visita): ?>
      <div class="aviso-info">Completá tu <a href="<?= url('postulante/aplicacion.php') ?>">solicitud de inscripción</a> para poder pedir este libro.</div>
    <?php elseif ($libro['reserva_pendiente'] ?? false): ?>
      <div class="aviso-info">Ya tenés una reserva pendiente para este libro.</div>
    <?php elseif ($libro['pedido_pendiente']): ?>
      <div class="aviso-info">Ya tenés un pedido pendiente de este libro. <a href="<?= url('biblioteca-lector/pedidos.php') ?>">Ver mis pedidos →</a></div>
    <?php else: ?>
      <?php if ($tieneStock): ?>
        <form method="post" class="formulario" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="pedir">
          <input type="hidden" name="id" value="<?= (int) $libro['id'] ?>">
          <div class="campo">
            <label for="motivo">Motivo de la solicitud (opcional)</label>
            <textarea id="motivo" name="motivo" rows="2" placeholder="Ej.: lectura obligatoria de Lengua, proyecto de ciencias…"></textarea>
          </div>
          <button class="btn btn-primario" type="submit">Enviar pedido</button>
          <p class="ayuda" style="margin-top:.35rem">El bibliotecario responde el pedido: si hay ejemplares, se genera tu préstamo.</p>
        </form>
      <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="reservar">
          <input type="hidden" name="id" value="<?= (int) $libro['id'] ?>">
          <button class="btn btn-ambar" type="submit">📌 Reservar (Entrar en lista de espera)</button>
          <p class="ayuda" style="margin-top:.35rem">No hay ejemplares disponibles. Si reservas, se te asignará prioridad cuando devuelvan un ejemplar.</p>
        </form>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Formulario para dejar Reseña -->
    <?php if (!$visita): ?>
      <hr style="margin:1.5rem 0; border:0; border-top:1px solid var(--borde, #e5e7eb)">
      <h3 class="titulo-seccion">Dejar una valoración</h3>
      <form method="post" class="formulario">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="resena">
        <div class="campo">
          <label for="puntuacion">Puntuación</label>
          <select id="puntuacion" name="puntuacion" class="desplegable" style="padding:.4rem; border-radius:.375rem; border:1px solid #ccc;" required>
            <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
            <option value="4">⭐⭐⭐⭐ (4/5)</option>
            <option value="3">⭐⭐⭐ (3/5)</option>
            <option value="2">⭐⭐ (2/5)</option>
            <option value="1">⭐ (1/5)</option>
          </select>
        </div>
        <div class="campo" style="margin-top:.8rem;">
          <label for="comentario">Comentario (opcional)</label>
          <textarea id="comentario" name="comentario" rows="2" placeholder="¿Qué te pareció este libro?" style="width:100%; padding:.5rem; border-radius:.375rem; border:1px solid #ccc;"></textarea>
        </div>
        <button class="btn btn-fantasma" type="submit" style="margin-top:.8rem;">Publicar opinión</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($similares): ?>
<div class="tarjeta" style="margin-top:1.2rem">
  <h3 class="titulo-seccion">Del mismo género</h3>
  <div class="red-catalogo red-catalogo-4">
    <?php foreach ($similares as $s): ?>
      <a href="<?= url('biblioteca-lector/libro.php?id=' . (int) $s['id']) ?>" class="libro-tarjeta enlace-portada">
        <div class="libro-portada">
          <?= portada_libro_contenido($s) ?>
        </div>
        <div class="libro-cuerpo">
          <span class="libro-titulo" style="font-size:.9rem"><?= e($s['titulo']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>