<?php
/**
 * Colegio8 — Lógica del módulo de inscripciones (asignación de cupos).
 * Requiere datos cargados: db(), auth (no necesario), helpers.
 */

declare(strict_types=1);

/** "1° A · mañana" */
function curso_titulo(array $v, bool $conTurno = true): string
{
    $txt = (int) $v['anio'] . '° ' . $v['division'];
    if ($conTurno) {
        $txt .= ' · ' . ($v['turno'] === 'manana' ? 'mañana' : 'tarde');
    }
    return $txt;
}

/**
 * Cupo ocupado real de una vacante. ÚNICA fuente de verdad: se cuenta en el
 * momento cuántos alumnos tienen asignado ese curso (alumnos.vacante_id).
 */
function cupo_ocupado_vacante(int $vacanteId): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM alumnos WHERE vacante_id = ?');
    $st->execute([$vacanteId]);
    return (int) $st->fetchColumn();
}

/**
 * Mapa vacante_id => alumnos asignados en UNA consulta (para listas).
 */
function cupos_ocupados_todos(): array
{
    $filas = db()->query(
        'SELECT vacante_id, COUNT(*) AS n FROM alumnos WHERE vacante_id IS NOT NULL GROUP BY vacante_id'
    )->fetchAll();
    $map = [];
    foreach ($filas as $f) {
        $map[(int) $f['vacante_id']] = (int) $f['n'];
    }
    return $map;
}

/** Retorna la lista de vacantes activas para un año dado. */
function vacantes_por_anio(int $anio): array
{
    $st = db()->prepare('SELECT * FROM vacantes WHERE activo = 1 AND anio = ? ORDER BY turno DESC, division');
    $st->execute([$anio]);
    return $st->fetchAll();
}

function vacante_existe_activa(int $vacanteId): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM vacantes WHERE id = ? AND activo = 1');
    $st->execute([$vacanteId]);
    return (int) $st->fetchColumn() > 0;
}

/**
 * Asigna un alumno aprobado a una vacante. Si está llena, lo agrega
 * a la lista de espera con su posición numérica (nunca falla).
 *
 * @return array ['ok' => bool, 'tipo' => 'asignado'|'espera'|'error', 'detalle' => string]
 */
function asignar_alumno_a_vacante(int $alumnoId, int $vacanteId, ?string $usuario = null): array
{
    $db = db();
    $db->beginTransaction();
    try {
        // Bloquear la fila de la vacante como mutex (serializa asignaciones
        // concurrentes al mismo curso; evita sobrepasar el cupo).
        $stV = $db->prepare('SELECT * FROM vacantes WHERE id = ? AND activo = 1 FOR UPDATE');
        $stV->execute([$vacanteId]);
        $vac = $stV->fetch();
        if (!$vac) {
            $db->rollBack();
            return ['ok' => false, 'tipo' => 'error', 'detalle' => 'El curso indicado no existe o está inactivo.'];
        }

        // Quitar de la lista de espera anterior si estaba.
        $db->prepare('DELETE FROM lista_espera WHERE alumno_id = ?')->execute([$alumnoId]);

        // Ocupación REAL del curso, contada en el momento.
        $ocupado = (int) $db->query('SELECT COUNT(*) FROM alumnos WHERE vacante_id = ' . (int) $vacanteId)->fetchColumn();

        if ($ocupado < (int) $vac['cupo_total']) {
            $db->prepare('UPDATE alumnos SET vacante_id = ? WHERE id = ?')->execute([$vacanteId, $alumnoId]);
            $db->commit();
            return ['ok' => true, 'tipo' => 'asignado', 'detalle' => 'Alumno asignado a ' . curso_titulo($vac) . '.'];
        }

        // Curso lleno → lista de espera.
        $stP = $db->prepare('SELECT COALESCE(MAX(posicion),0) + 1 FROM lista_espera WHERE vacante_id = ?');
        $stP->execute([$vacanteId]);
        $pos = (int) $stP->fetchColumn();
        $db->prepare('INSERT INTO lista_espera (alumno_id, vacante_id, posicion) VALUES (?, ?, ?)')
            ->execute([$alumnoId, $vacanteId, $pos]);
        $db->prepare('UPDATE alumnos SET vacante_id = NULL WHERE id = ?')->execute([$alumnoId]);
        $db->commit();
        return ['ok' => true, 'tipo' => 'espera', 'detalle' => 'El curso ' . curso_titulo($vac) . ' está lleno: el alumno entró a la lista de espera en la posición ' . $pos . '.'];
    } catch (Throwable $t) {
        $db->rollBack();
        return ['ok' => false, 'tipo' => 'error', 'detalle' => 'No se pudo asignar: ' . $t->getMessage()];
    }
}

/**
 * Libera la vacante de un alumno (quitar de la lista del curso).
 *
 * NO promueve automáticamente al primer alumno de la lista de espera:
 * el cupo liberado se asigna de forma explícita desde la pantalla de
 * asignación (asignar.php / curso.php). El orden de la lista de espera
 * se mantiene igual, solo queda disponible la vacante.
 */
function liberar_vacante_alumno(int $alumnoId): array
{
    $db = db();
    $db->beginTransaction();
    try {
        $stA = $db->prepare('SELECT vacante_id FROM alumnos WHERE id = ? FOR UPDATE');
        $stA->execute([$alumnoId]);
        $vacanteId = $stA->fetchColumn();
        if (!$vacanteId) {
            $db->rollBack();
            return ['ok' => false, 'tipo' => 'error', 'detalle' => 'El alumno no tiene una división asignada.'];
        }

        $db->prepare('UPDATE alumnos SET vacante_id = NULL WHERE id = ?')->execute([$alumnoId]);

        $db->commit();
        return ['ok' => true, 'tipo' => 'ok', 'detalle' => 'Alumno retirado del curso. El cupo quedó disponible para asignar manualmente desde la lista de espera.'];
    } catch (Throwable $t) {
        $db->rollBack();
        return ['ok' => false, 'tipo' => 'error', 'detalle' => 'No se pudo retirar: ' . $t->getMessage()];
    }
}

/** Cambio de curso: libera el actual y asigna el destino. */
function mover_alumno_de_curso(int $alumnoId, int $vacanteDestino): array
{
    $r1 = liberar_vacante_alumno($alumnoId);
    if (!$r1['ok']) {
        return $r1;
    }
    return asignar_alumno_a_vacante($alumnoId, $vacanteDestino);
}

/* ------------------------------------------------------------------ */
/* Bloqueo de revisión de secretaría (estado "en_revision")          */
/*                                                                     */
/* El expediente queda "en revisión" solo mientras el secretario tiene */
/* el expediente abierto: se renueva con un latido (heartbeat) cada    */
/* ~30 s y vence a los 90 s de inactividad (si cierra la ventana o se  */
/* va sin resolver). Al vencer, la solicitud vuelve a "recibida"       */
/* (para ser revisada) y otra secretaria puede tomarla.                */
/* ------------------------------------------------------------------ */

/** Segundos de validez del bloqueo "en revisión". */
const REVISION_BLOQUEO_SEG = 90;

/**
 * Vuelve a 'recibida' las solicitudes 'en_revision' cuyo bloqueo venció
 * (o que quedaron sin vencimiento de versiones anteriores). Devuelve la
 * cantidad de solicitudes liberadas.
 */
function liberar_revisiones_vencidas(): int
{
    $db = db();
    $cond = "estado = 'en_revision' AND (en_revision_hasta IS NULL OR en_revision_hasta < NOW())";
    $afectadas = (int) $db->query('SELECT COUNT(*) FROM solicitudes WHERE ' . $cond)->fetchColumn();
    if ($afectadas > 0) {
        $db->prepare(
            "INSERT INTO historial_solicitudes (solicitud_id, estado, motivo)
             SELECT id, 'recibida', 'Liberada: venció el tiempo de revisión sin actividad.'
             FROM solicitudes WHERE " . $cond
        )->execute();
        $db->query("UPDATE solicitudes SET estado = 'recibida', secretaria_id = NULL, en_revision_hasta = NULL WHERE " . $cond);
    }
    return $afectadas;
}