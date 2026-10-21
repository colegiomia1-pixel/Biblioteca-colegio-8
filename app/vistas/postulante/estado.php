<?php
/** Vista: estado de la solicitud del postulante. */
$alu         = $alu ?? null;
$sol         = $sol ?? null;
$tut         = $tut ?? null;
$vacante     = $vacante ?? null;
$espera      = $espera ?? null;
$hayBorrador = $hayBorrador ?? false;
$docs        = $docs ?? [];
$estados     = $estados ?? [];

$tituloTurno = fn($t) => $t === 'manana' ? 'mañana' : 'tarde';
$claseEstado = [
    'recibida'    => 'badge-gris',
    'en_revision' => 'badge-ambar',
    'aprobada'    => 'badge-verde',
    'rechazada'   => 'badge-rojo',
][$sol['estado'] ?? 'recibida'] ?? 'badge-gris';
?>

<?php if (!$alu || !$sol): ?>
  <div class="tarjeta">
    <h2 style="font-size:1.2rem">Todavía no presentaste una solicitud</h2>
    <p style="color:var(--gris-500)">
      Completá la solicitud de vacante para que secretaría pueda revisarla.
      <?= $hayBorrador ? 'Hay un borrador en progreso: podés continuar donde lo dejaste.' : '' ?>
    </p>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.6rem">
      <a class="btn btn-primario" href="<?= url('postulante/aplicacion.php') ?>">
        <?= $hayBorrador ? 'Continuar borrador' : 'Crear solicitud' ?>
      </a>
      <a class="btn btn-fantasma" href="<?= url('login.php?modulo=lector&auto=1') ?>">Ir a biblioteca</a>
    </div>
  </div>

<?php else: ?>

  <div class="rejilla rejilla-2">
    <div class="tarjeta">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;flex-wrap:wrap">
        <h2 style="font-size:1.15rem;margin:0"><?= e($alu['nombre'] . ' ' . $alu['apellido']) ?></h2>
        <span class="badge <?= $claseEstado ?>"><?= e($estados[$sol['estado']]['0'] ?? $sol['estado']) ?></span>
      </div>
      <p style="color:var(--gris-500);margin-top:.3rem">
        Postula a: <strong><?= (int) $alu['anio_postulado'] ?>° año</strong> ·
        Presentada el <?= e(fecha_es($sol['fecha_presentacion'], true)) ?>
      </p>

      <?php if ($sol['motivo_rechazo']): ?>
        <div class="aviso-error" role="alert">
          <strong>Motivo del rechazo:</strong><br>
          <?= e($sol['motivo_rechazo']) ?>
        </div>
        <a class="btn btn-peligro" href="<?= url('postulante/aplicacion.php') ?>">Corregir datos y reenviar</a>
      <?php else: ?>
        <a class="btn btn-fantasma" href="<?= url('postulante/aplicacion.php') ?>" aria-disabled="true">Editar datos</a>
      <?php endif; ?>

      <!-- Línea de tiempo -->
      <h3 class="titulo-seccion">Progreso</h3>
      <ol class="timeline">
        <?php
            $orden = ['recibida', 'en_revision'];
            $lista = $orden;
            if ($sol['estado'] !== 'rechazada') {
                $lista[] = $sol['estado'];
            }
            foreach ($lista as $est) {
                [$etq, $desc] = $estados[$est];
                $ec = $sol['estado'] === $est ? 'actual' : 'hecho';
                if ($est === 'en_revision' && !in_array($sol['estado'], ['en_revision', 'aprobada'], true)) {
                    $ec = '';
                }
                echo '<li class="' . $ec . '">' . e($etq)
                   . '<div style="color:var(--gris-500);font-size:.9rem">' . e($desc) . '</div>'
                   . '<div class="tl-fecha">' . e(fecha_es($sol['fecha_resolucion'], true)) . '</div></li>';
            }
            if ($sol['estado'] === 'rechazada') {
                echo '<li class="actual" style="border-color:var(--rojo)"><strong style="color:var(--rojo)">Rechazada</strong>'
                   . '<div style="color:var(--gris-500);font-size:.9rem">' . e($estados['rechazada'][1]) . '</div>'
                   . '<div class="tl-fecha">' . e(fecha_es($sol['fecha_resolucion'], true)) . '</div></li>';
            }
        ?>
      </ol>
    </div>

    <div class="tarjeta">
      <h2 style="font-size:1.05rem">Cupo / división asignada</h2>
      <?php if ($vacante): ?>
        <p class="stats-num" style="font-size:1.7rem;color:var(--verde-900);margin:.3rem 0">
          <?= (int) $vacante['anio'] ?>° <?= e($vacante['division']) ?>
        </p>
        <p style="color:var(--gris-500)">Turno <?= e($tituloTurno((string) $vacante['turno'])) ?> ·
          Cupo: <?= (int) $vacante['cupo_ocupado'] ?> / <?= (int) $vacante['cupo_total'] ?></p>
        <span class="badge badge-verde">Inscripto</span>
      <?php elseif ($espera): ?>
        <div class="aviso-info">
          <strong>Lista de espera — posición <?= (int) $espera['posicion'] ?></strong><br>
          Estás en espera para <strong><?= (int) $espera['anio'] ?>° <?= e($espera['division']) ?></strong>
          (turno <?= e($tituloTurno((string) $espera['turno'])) ?>), que se encuentra lleno.
        </div>
      <?php elseif ($sol['estado'] === 'aprobada'): ?>
        <div class="aviso-info">Solicitud aprobada. Secretaría todavía no te asignó una división; cuando lo haga lo vas a ver acá.</div>
      <?php else: ?>
        <p style="color:var(--gris-500)">La división se definirá cuando la solicitud sea aprobada y asignada por secretaría.</p>
      <?php endif; ?>

      <div style="margin-top:1.2rem;border-top:var(--borde);padding-top:1rem">
        <a class="btn btn-fantasma" href="<?= url('login.php?modulo=lector&auto=1') ?>">Ir a la biblioteca</a>
      </div>
    </div>
  </div>

  <div class="tarjeta" style="margin-top:1.2rem">
    <h2 style="font-size:1.05rem">Documentación (4 fotos del DNI)</h2>
    <div class="rejilla rejilla-2 doc-grupo" style="margin-top:.7rem">
      <?php foreach (tipos_dni_documentos() as $campoDoc): ?>
        <?php $docF = $docs[$campoDoc] ?? null; ?>
        <div class="doc-miniatura">
          <span class="doc-ico" aria-hidden="true">🪪</span>
          <span style="flex:1;font-size:.85rem">
            <strong><?= e(doc_etiqueta_tipo($campoDoc)) ?></strong><br>
            <?php if ($docF): ?>
              <?= e($docF['nombre_original']) ?>
            <?php else: ?>
              <em style="color:var(--rojo)">No cargado</em>
            <?php endif; ?>
          </span>
          <?php if ($docF): ?>
            <button type="button" class="btn btn-fantasma btn-pequeno"
                    data-doc-preview="documento.php?id=<?= (int) $docF['id'] ?>"
                    data-doc-nombre="<?= e(doc_etiqueta_tipo($campoDoc)) ?>"
                    <?= es_img_documento((string) $docF['nombre_original']) ? 'data-doc-img="1"' : '' ?>>Ver</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

<?php endif; ?>