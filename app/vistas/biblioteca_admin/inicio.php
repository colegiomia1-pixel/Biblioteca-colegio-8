<?php
/** Vista: Inicio del panel de administración de la biblioteca. */
?>
<div class="encabezado-pagina">
  <h2>Panel de Administración — Biblioteca</h2>
  <p class="texto-atenuado">Gestión general del catálogo, préstamos, reservas y usuarios.</p>
</div>

<!-- ACCESOS RÁPIDOS Y NAVEGACIÓN -->
<div class="d-flex gap-2 flex-wrap" style="margin-bottom: 2.2rem !important;">
    <a href="libros.php" class="btn btn-primario">
        <i class="bi bi-journal-bookmark-fill me-1"></i> Gestión de Libros / Catálogo
    </a>
    <a href="prestamos.php" class="btn btn-primario">
        <i class="bi bi-book-half me-1"></i> Préstamos Activos
    </a>
    <a href="pedidos.php" class="btn btn-primario">
        <i class="bi bi-inbox-fill me-1"></i> Pedidos de Lectores
    </a>
    <a href="reservas.php" class="btn btn-fantasma">
        <i class="bi bi-pin-angle-fill me-1"></i> Reservas y Reseñas
    </a>
    <a href="morosidad.php" class="btn btn-fantasma">
        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Morosos / Vencidos
    </a>
    <a href="reportes.php" class="btn btn-fantasma">
        <i class="bi bi-bar-chart-line-fill me-1"></i> Reportes
    </a>
</div>

<!-- TARJETAS DE RESUMEN / ESTADÍSTICAS RÁPIDAS -->
<div class="rejilla rejilla-3">
  <div class="tarjeta">
    <h3>Libros en Catálogo</h3>
    <p class="texto-atenuado" style="font-size: 1.8rem; font-weight: bold; margin-top: 0.5rem;">
      <?= (int) ($totalLibros ?? $libros ?? 0) ?>
    </p>
    <a href="libros.php" class="enlace">Ver catálogo completo →</a>
  </div>

  <div class="tarjeta">
    <h3>Préstamos Activos</h3>
    <p class="texto-atenuado" style="font-size: 1.8rem; font-weight: bold; margin-top: 0.5rem; color: var(--exito, #10b981);">
      <?= (int) ($totalPrestamos ?? $prestamosActivos ?? 0) ?>
    </p>
    <a href="prestamos.php" class="enlace">Ver préstamos →</a>
  </div>

  <div class="tarjeta">
    <h3>Reservas Pendientes</h3>
    <p class="texto-atenuado" style="font-size: 1.8rem; font-weight: bold; margin-top: 0.5rem; color: var(--alerta, #f59e0b);">
      <?= (int) ($totalReservas ?? $reservas ?? 0) ?>
    </p>
    <a href="resenas-reservas.php" class="enlace">Gestionar reservas →</a>
  </div>
</div>