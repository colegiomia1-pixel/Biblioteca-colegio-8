<?php
/**
 * Colegio8 — Funciones compartidas del módulo de biblioteca (admin y lectores).
 * Se incluyen desde app/core.php.
 */

/** Marcar como vencidos los préstamos activos pasados de fecha. */
function biblioteca_marcar_vencidos(): int
{
    return (int) db()->query("UPDATE biblioteca_prestamos SET estado = 'vencido' WHERE estado = 'activo' AND fecha_limite < CURDATE()")->rowCount();
}

/** Devuelve el filtro de persona [columna, id] para un lector según su sesión. */
function lector_scope(array $lec): array
{
    $col = ($lec['tipo'] ?? '') === 'profesor' ? 'profesor_id' : 'alumno_id';
    return [$col, (int) ($lec['persona_id'] ?? 0)];
}

/** Un lector "visitante" (cuenta sin postulación) no tiene persona asociada. */
function lector_tiene_persona(array $lec): bool
{
    return !empty($lec['persona_id']);
}

/** Nombre leíble de la persona de un préstamo/petición. */
function persona_nombre(array $fila): string
{
    return $fila['apellido'] . ', ' . $fila['nombre'];
}

/** Libros recomendados: género favorito del lector y, si se llenan, novedades. */
function recomendar_libros(array $lec, int $limite = 6): array
{
    [$col, $id] = lector_scope($lec);
    $clave = $col . '_leer';

    // Género más marcado como favorito por este lector.
    $stG = db()->prepare(
        "SELECT l.genero, COUNT(*) AS n
         FROM biblioteca_favoritos f
         JOIN biblioteca_libros l ON l.id = f.libro_id
         WHERE f.$col = ? AND l.genero IS NOT NULL AND l.genero <> '' AND l.activo = 1
         GROUP BY l.genero ORDER BY n DESC, l.genero LIMIT 1"
    );
    $stG->execute([$id]);
    $genero = $stG->fetchColumn();

    $sql = "SELECT l.*, (f.id IS NOT NULL) AS es_favorito
            FROM biblioteca_libros l
            LEFT JOIN biblioteca_favoritos f ON f.libro_id = l.id AND f.$col = ?
            WHERE l.activo = 1 AND l.ejemplares_disponibles > 0 AND f.id IS NULL ";
    $params = [$id];
    if ($genero) {
        $sql .= 'AND l.genero = ? ';
        $params[] = $genero;
    }
    $sql .= 'ORDER BY l.id DESC LIMIT ' . (int) $limite;
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/* ==========================================================================
   NUEVAS FUNCIONES: RESERVAS Y RESEÑAS
   ========================================================================== */

/**
 * Obtener la puntuación promedio y total de reseñas de un libro.
 */
function promedio_puntuacion_libro(int $libro_id): array
{
    try {
        $st = db()->prepare("SELECT AVG(puntuacion) as promedio, COUNT(*) as total FROM biblioteca_resenas WHERE libro_id = ?");
        $st->execute([$libro_id]);
        $res = $st->fetch();
        return [
            'promedio' => (!empty($res['promedio'])) ? round((float)$res['promedio'], 1) : 0,
            'total'    => (int)($res['total'] ?? 0)
        ];
    } catch (\Throwable $e) {
        return ['promedio' => 0, 'total' => 0];
    }
}

/**
 * Crear una reserva para un libro sin stock (lista de espera).
 */
function crear_reserva(int $libro_id, array $lec): bool
{
    [$col, $id] = lector_scope($lec);
    if (!$id) return false;

    try {
        $stCheck = db()->prepare("SELECT id FROM biblioteca_reservas WHERE libro_id = ? AND $col = ? AND estado = 'pendiente'");
        $stCheck->execute([$libro_id, $id]);
        if ($stCheck->fetch()) {
            return false;
        }

        $st = db()->prepare("INSERT INTO biblioteca_reservas (libro_id, $col, estado) VALUES (?, ?, 'pendiente')");
        return $st->execute([$libro_id, $id]);
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Agregar calificación/reseña a un libro.
 */
function agregar_resena(int $libro_id, array $lec, int $puntuacion, string $comentario = ''): bool
{
    [$col, $id] = lector_scope($lec);
    if (!$id || $puntuacion < 1 || $puntuacion > 5) return false;

    try {
        $st = db()->prepare("INSERT INTO biblioteca_resenas (libro_id, $col, puntuacion, comentario) VALUES (?, ?, ?, ?)");
        return $st->execute([$libro_id, $id, $puntuacion, trim($comentario)]);
    } catch (\Throwable $e) {
        return false;
    }
}