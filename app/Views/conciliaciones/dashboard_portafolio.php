<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Portafolio – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-kpi { border-left: 4px solid <?= $colorPrimario ?>; }
        .gauge-wrap { position:relative; height:180px; }
        .gauge-label {
            position:absolute; bottom:30px; left:0; right:0; text-align:center;
        }
        .gauge-label .pct { font-size:1.8rem; font-weight:bold; }
        .seg-pill {
            cursor:pointer; user-select:none;
            padding:4px 10px; border:1px solid #dee2e6; border-radius:16px;
            font-size:0.85rem; background:#fff;
        }
        .seg-pill.active { background: <?= $colorPrimario ?>; color:#fff; border-color: <?= $colorPrimario ?>; }
        .mes-check { width:32px; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-3">
    <!-- Header con segmentadores -->
    <form method="get" class="card mb-3 shadow-sm">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div>
                    <strong style="color: <?= $colorPrimario ?>; font-size:1.3rem;">
                        <?= esc($titulo) ?>
                    </strong>
                    <small class="text-muted ms-2">Dashboard de cumplimiento</small>
                </div>

                <span class="border-start mx-1" style="height:36px;"></span>

                <!-- Portafolio: pills -->
                <div class="d-flex align-items-center gap-1">
                    <small class="text-muted me-1">Portafolio:</small>
                    <a href="?portafolio=framework&anio=<?= $anio ?><?= implode('', array_map(fn($m)=>"&meses[]={$m}", $mesesSel)) ?>"
                       class="seg-pill text-decoration-none <?= $portafolioParam === 'framework' ? 'active' : '' ?>">FRAMEWORK</a>
                    <?php foreach ($portafolios as $p): ?>
                        <a href="?portafolio=<?= esc($p['portafolio']) ?>&anio=<?= $anio ?><?= implode('', array_map(fn($m)=>"&meses[]={$m}", $mesesSel)) ?>"
                           class="seg-pill text-decoration-none <?= $portafolioParam === $p['portafolio'] ? 'active' : '' ?>"><?= esc($p['portafolio']) ?></a>
                    <?php endforeach; ?>
                </div>

                <span class="border-start mx-1" style="height:36px;"></span>

                <!-- Año -->
                <div class="d-flex align-items-center gap-1">
                    <small class="text-muted me-1">Año:</small>
                    <input type="hidden" name="portafolio" value="<?= esc($portafolioParam) ?>">
                    <select name="anio" class="form-select form-select-sm" style="width:110px;" onchange="this.form.submit()">
                        <option value="todos" <?= $anio === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= $a ?>" <?= $anio == $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <span class="border-start mx-1" style="height:36px;"></span>

                <!-- Meses: checkboxes -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <small class="text-muted me-1">Meses:</small>
                    <?php
                    $mesesNombre = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                    for ($m = 1; $m <= 12; $m++):
                        $checked = in_array($m, $mesesSel) ? 'checked' : '';
                    ?>
                        <label class="seg-pill <?= $checked ? 'active' : '' ?>" style="font-size:0.75rem;">
                            <input type="checkbox" name="meses[]" value="<?= $m ?>" <?= $checked ?> onchange="this.form.submit()" style="display:none;">
                            <?= $mesesNombre[$m] ?>
                        </label>
                    <?php endfor; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="marcarTodos(true)">Todos</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodos(false)">Ninguno</button>
                </div>

                <div class="ms-auto">
                    <a href="<?= base_url('conciliaciones/presupuestos') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square"></i> Editar presupuestos
                    </a>
                </div>
            </div>
        </div>
    </form>

    <!-- Cards principales -->
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">PRESUPUESTO <?= esc($titulo) ?></small>
                    <h3 class="mb-0">$<?= number_format($presupuestoTotal, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">FACTURADO <?= esc($titulo) ?> BASE GRAVABLE</small>
                    <h3 class="mb-0">$<?= number_format($facturadoTotal, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">RECAUDO <?= esc($titulo) ?></small>
                    <h3 class="mb-0">$<?= number_format($recaudoTotal, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">CARTERA <?= esc($titulo) ?></small>
                    <h3 class="mb-0">$<?= number_format($carteraTotal, 0, ',', '.') ?></h3>
                    <small class="text-muted">Facturas pendientes acumuladas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gauges -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <small class="text-muted">INDICADOR FACTURACIÓN</small>
                    <div class="gauge-wrap">
                        <canvas id="gaugeFact"></canvas>
                        <div class="gauge-label">
                            <div class="pct" style="color: <?= $colorPrimario ?>;"><?= number_format($indicadorFact, 1, ',', '.') ?> %</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between px-3">
                        <small class="text-muted">0%</small>
                        <small class="text-muted">100%</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <small class="text-muted">INDICADOR RECAUDO</small>
                    <div class="gauge-wrap">
                        <canvas id="gaugeRec"></canvas>
                        <div class="gauge-label">
                            <div class="pct" style="color: <?= $colorPrimario ?>;"><?= number_format($indicadorRecaudo, 1, ',', '.') ?> %</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between px-3">
                        <small class="text-muted">0%</small>
                        <small class="text-muted">100%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts líneas -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-2">FACTURADO <?= esc($titulo) ?> BASE GRAVABLE — por mes (<?= $anio === 'todos' ? 'todos los años' : $anio ?>)</small>
                    <div style="position:relative; height:300px;">
                        <canvas id="chartFact"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-2">RECAUDO <?= esc($titulo) ?> — por mes (<?= $anio === 'todos' ? 'todos los años' : $anio ?>)</small>
                    <div style="position:relative; height:300px;">
                        <canvas id="chartRec"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script>
const COLOR = <?= json_encode($colorPrimario) ?>;
const LABELS = <?= json_encode($serieMeses) ?>;
const DATA_FACT = <?= json_encode($serieFacturado) ?>;
const DATA_REC  = <?= json_encode($serieRecaudo) ?>;
const PCT_FACT = <?= json_encode(min(100, max(0, $indicadorFact))) ?>;
const PCT_REC  = <?= json_encode(min(100, max(0, $indicadorRecaudo))) ?>;

function marcarTodos(estado) {
    document.querySelectorAll('input[name="meses[]"]').forEach(cb => { cb.checked = estado; });
    document.querySelector('form').submit();
}

function gauge(canvasId, pct) {
    return new Chart(document.getElementById(canvasId), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [pct, 100 - pct],
                backgroundColor: [COLOR, '#e9ecef'],
                borderWidth: 0,
            }]
        },
        options: {
            rotation: -90,
            circumference: 180,
            cutout: '70%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            responsive: true,
            maintainAspectRatio: false,
        }
    });
}

function formatCOP(v) {
    return '$' + new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(v);
}

gauge('gaugeFact', PCT_FACT);
gauge('gaugeRec', PCT_REC);

const lineOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: ctx => formatCOP(ctx.parsed.y),
            }
        }
    },
    scales: {
        x: {
            ticks: { autoSkip: true, maxRotation: 45, minRotation: 0 }
        },
        y: {
            beginAtZero: true,
            min: 0,
            grace: '10%',
            ticks: { callback: v => formatCOP(v) }
        }
    }
};

new Chart(document.getElementById('chartFact'), {
    type: 'line',
    data: {
        labels: LABELS,
        datasets: [{
            label: 'Facturado',
            data: DATA_FACT,
            borderColor: COLOR,
            backgroundColor: COLOR + '22',
            tension: 0.25,
            fill: true,
            pointRadius: 4,
        }]
    },
    options: lineOpts,
});

new Chart(document.getElementById('chartRec'), {
    type: 'line',
    data: {
        labels: LABELS,
        datasets: [{
            label: 'Recaudo',
            data: DATA_REC,
            borderColor: COLOR,
            backgroundColor: COLOR + '22',
            tension: 0.25,
            fill: true,
            pointRadius: 4,
        }]
    },
    options: lineOpts,
});
</script>
</body>
</html>
