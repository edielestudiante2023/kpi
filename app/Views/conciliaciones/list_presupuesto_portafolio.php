<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuestos por Portafolio – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .input-monto {
            text-align:right;
            font-family: monospace;
            min-width: 110px;
        }
        .input-monto.dirty { background:#fff3cd; }
        .input-monto.saved { background:#d1e7dd; transition: background 0.6s; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div class="toast show align-items-center text-white bg-success border-0">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-check-circle me-1"></i><?= session()->getFlashdata('success') ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('errors')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div class="toast show align-items-center text-white bg-danger border-0">
        <div class="d-flex">
            <div class="toast-body">
                <?php foreach (session()->getFlashdata('errors') as $e): ?><?= esc($e) ?><br><?php endforeach; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><i class="bi bi-cash-stack me-2"></i>Presupuestos por Portafolio — <?= $anioActual ?></h1>
        <div class="d-flex gap-2">
            <form method="get" class="d-flex gap-2">
                <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($anios as $a): ?>
                        <option value="<?= $a ?>" <?= $anioActual == $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                    <?php
                    // Permitir cargar un año que aún no existe
                    $extras = [date('Y'), date('Y') + 1];
                    foreach ($extras as $a) {
                        if (! in_array($a, $anios)) {
                            echo '<option value="' . $a . '">' . $a . ' (sin datos)</option>';
                        }
                    }
                    ?>
                </select>
            </form>
            <a href="<?= base_url('conciliaciones/dashboard-portafolio') ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-bar-chart-line"></i> Ver Dashboard
            </a>
        </div>
    </div>

    <!-- Tabla matriz -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size:0.85rem;">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="min-width:120px;">Portafolio</th>
                            <?php $mesesNombre = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']; ?>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <th><?= $mesesNombre[$m] ?></th>
                            <?php endfor; ?>
                            <th>Total año</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($portafolios as $p):
                        $nombre = $p['portafolio'];
                        $idPort = (int) $p['id_portafolio'];
                        $tieneDatos = isset($matriz[$nombre]);
                        $totalAnio = 0;
                        if ($tieneDatos) {
                            foreach ($matriz[$nombre] as $cel) $totalAnio += $cel['valor'];
                        }
                    ?>
                        <tr>
                            <td class="fw-bold align-middle"><?= esc($nombre) ?></td>
                            <?php for ($m = 1; $m <= 12; $m++):
                                $cel = $matriz[$nombre][$m] ?? null;
                            ?>
                                <td class="p-1">
                                    <?php if ($cel): ?>
                                        <input type="text"
                                               class="form-control form-control-sm input-monto"
                                               data-id="<?= $cel['id'] ?>"
                                               data-orig="<?= (int) $cel['valor'] ?>"
                                               value="<?= number_format($cel['valor'], 0, ',', '.') ?>">
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td class="text-end align-middle fw-bold">
                                <span data-total="<?= $idPort ?>">$<?= number_format($totalAnio, 0, ',', '.') ?></span>
                            </td>
                            <td class="text-center align-middle">
                                <?php if ($tieneDatos): ?>
                                    <button class="btn btn-sm btn-outline-primary"
                                            onclick="aplicarTodos(<?= $idPort ?>, '<?= esc($nombre) ?>')"
                                            title="Aplicar mismo valor a los 12 meses">
                                        <i class="bi bi-arrows-fullscreen"></i>
                                    </button>
                                    <a href="<?= base_url("conciliaciones/presupuestos/eliminar/{$idPort}/{$anioActual}") ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Eliminar todos los meses de <?= esc($nombre) ?> en <?= $anioActual ?>?')"
                                       title="Eliminar año">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success"
                                            onclick="inicializar(<?= $idPort ?>, '<?= esc($nombre) ?>')">
                                        <i class="bi bi-plus-lg"></i> Crear año
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            <i class="bi bi-info-circle me-1"></i>
            Edita cualquier celda y haz clic afuera para guardar automáticamente. Los presupuestos cambian de año en año (ej: SST pasó de $16M a $21M en 2026).
        </div>
    </div>
</div>

<!-- Modal inicializar año -->
<div class="modal fade" id="modalInicializar" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('conciliaciones/presupuestos/inicializar') ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Crear presupuesto año <?= $anioActual ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_portafolio" id="iniIdPort">
        <input type="hidden" name="anio" value="<?= $anioActual ?>">
        <p>Se crearán los 12 meses de <strong id="iniNombre"></strong> en <strong><?= $anioActual ?></strong> con el mismo valor base.</p>
        <label class="form-label">Valor mensual ($)</label>
        <input type="number" name="presupuesto_base" class="form-control" required min="0" step="1" value="0">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success">Crear 12 meses</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const URL_UPDATE   = "<?= base_url('conciliaciones/presupuestos/actualizar') ?>";
const URL_APLICAR  = "<?= base_url('conciliaciones/presupuestos/aplicar-todos') ?>";
const ANIO_ACTUAL  = <?= (int) $anioActual ?>;

function parseMonto(str) {
    return parseInt(String(str).replace(/[^\d-]/g, ''), 10) || 0;
}
function fmtMonto(n) {
    return new Intl.NumberFormat('es-CO').format(n);
}

document.querySelectorAll('.input-monto').forEach(inp => {
    inp.addEventListener('focus', e => { e.target.select(); });
    inp.addEventListener('input', e => {
        const n = parseMonto(e.target.value);
        e.target.value = fmtMonto(n);
        const orig = parseInt(e.target.dataset.orig, 10);
        e.target.classList.toggle('dirty', n !== orig);
    });
    inp.addEventListener('blur', async e => {
        const n = parseMonto(e.target.value);
        const orig = parseInt(e.target.dataset.orig, 10);
        if (n === orig) return;

        const fd = new FormData();
        fd.append('id_presupuesto', e.target.dataset.id);
        fd.append('presupuesto', n);

        const r = await fetch(URL_UPDATE, { method: 'POST', body: fd });
        const j = await r.json();
        if (j.ok) {
            e.target.dataset.orig = n;
            e.target.classList.remove('dirty');
            e.target.classList.add('saved');
            setTimeout(() => e.target.classList.remove('saved'), 800);
            // Sin recargar: no actualizo total inline (se ve al recargar)
        } else {
            alert('Error: ' + (j.error || 'desconocido'));
        }
    });
});

function inicializar(idPort, nombre) {
    document.getElementById('iniIdPort').value = idPort;
    document.getElementById('iniNombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalInicializar')).show();
}

async function aplicarTodos(idPort, nombre) {
    const valor = prompt(`Valor mensual a aplicar a todos los meses de ${nombre} en ${ANIO_ACTUAL}:`, '');
    if (valor === null) return;
    const n = parseMonto(valor);
    if (n < 0) { alert('Valor inválido.'); return; }
    if (!confirm(`Se sobrescribirán los 12 meses de ${nombre} (${ANIO_ACTUAL}) con $${fmtMonto(n)}. ¿Continuar?`)) return;

    const fd = new FormData();
    fd.append('id_portafolio', idPort);
    fd.append('anio', ANIO_ACTUAL);
    fd.append('presupuesto', n);

    const r = await fetch(URL_APLICAR, { method: 'POST', body: fd });
    const j = await r.json();
    if (j.ok) { location.reload(); } else { alert('Error: ' + (j.error || 'desconocido')); }
}
</script>
</body>
</html>
