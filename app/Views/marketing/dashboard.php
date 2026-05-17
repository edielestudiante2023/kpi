<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Marketing – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .kpi-card { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .kpi-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-value { font-size: 1.7rem; font-weight: 700; color: #2c3e50; line-height: 1.1; }
        .kpi-sub { font-size: 0.72rem; color: #6c757d; margin-top: 2px; }
        .chart-card { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); height: 100%; }
        .chart-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 12px; color: #2c3e50; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php
$delta = $leadsEstaSemana - $leadsSemanaPasada;
$pctDelta = $leadsSemanaPasada > 0 ? round($delta / $leadsSemanaPasada * 100, 0) : ($leadsEstaSemana > 0 ? 100 : 0);
?>

<div class="container-fluid py-3 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Dashboard de Marketing</h1>
            <small class="text-muted">Los 5 KPIs que importan para una empresa pequeña — capturar leads, mover el embudo, medir qué funciona.</small>
        </div>
        <div>
            <a href="<?= base_url('marketing/leads') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-people me-1"></i> Leads
            </a>
            <a href="<?= base_url('marketing/acciones') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-journal-text me-1"></i> Diario
            </a>
            <a href="<?= base_url('marketing/leads/nuevo') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nuevo lead
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Leads esta semana</div>
            <div class="kpi-value text-primary"><?= $leadsEstaSemana ?></div>
            <div class="kpi-sub">
                Semana pasada: <?= $leadsSemanaPasada ?>
                <?php if ($delta !== 0): ?>
                    <span class="<?= $delta > 0 ? 'text-success' : 'text-danger' ?>">
                        (<?= $delta > 0 ? '▲ +' : '▼ ' ?><?= $pctDelta ?>%)
                    </span>
                <?php endif; ?>
            </div>
        </div></div>

        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Tasa de calificación</div>
            <div class="kpi-value text-success"><?= $tasaCalificacion ?>%</div>
            <div class="kpi-sub">Calificados / total leads</div>
        </div></div>

        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Acciones este mes</div>
            <div class="kpi-value"><?= $totalAccionesMes ?></div>
            <div class="kpi-sub">
                <?php if ($costoTotalMes > 0): ?>
                    Costo total: $<?= number_format($costoTotalMes, 0, ',', '.') ?>
                <?php else: ?>
                    Sin costos registrados
                <?php endif; ?>
            </div>
        </div></div>

        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">CAC del mes</div>
            <div class="kpi-value <?= $cacInformal ? 'text-warning' : 'text-muted' ?>">
                <?php if ($cacInformal !== null): ?>
                    $<?= number_format($cacInformal, 0, ',', '.') ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </div>
            <div class="kpi-sub">
                <?php if ($cacInformal !== null): ?>
                    Costo total / <?= $leadsMes ?> leads
                <?php else: ?>
                    Falta registrar costos o leads
                <?php endif; ?>
            </div>
        </div></div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mb-4">
        <div class="col-md-7">
            <div class="chart-card">
                <div class="chart-title">Leads por semana (últimas 8)</div>
                <canvas id="chSemanas" height="90"></canvas>
            </div>
        </div>
        <div class="col-md-5">
            <div class="chart-card">
                <div class="chart-title">Leads por estado</div>
                <?php if ($totalLeads === 0): ?>
                    <div class="text-muted small text-center py-4">Aún no hay leads.</div>
                <?php else: ?>
                    <canvas id="chEstado" height="120"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-7">
            <div class="chart-card">
                <div class="chart-title">Top fuentes por calificación</div>
                <?php if (empty($porFuente)): ?>
                    <div class="text-muted small text-center py-4">Sin datos aún.</div>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Fuente</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Calificados</th>
                                <th class="text-end">% Calif.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($porFuente as $f): ?>
                            <tr>
                                <td class="small"><?= esc($f['fuente_nombre']) ?></td>
                                <td class="text-end"><?= (int) $f['total'] ?></td>
                                <td class="text-end fw-bold text-success"><?= (int) $f['calificados'] ?></td>
                                <td class="text-end"><?= (float) $f['tasa_calif'] ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="small text-muted mt-2 mb-0">
                        💡 Mira la columna <strong>Calificados</strong>, no Total — la fuente que <em>convierte mejor</em> vale más que la que trae más volumen.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-5">
            <div class="chart-card">
                <div class="chart-title">Acciones del mes por tipo</div>
                <?php if (empty($accionesMes)): ?>
                    <div class="text-muted small text-center py-4">
                        Sin acciones este mes.<br>
                        <a href="<?= base_url('marketing/acciones/nueva') ?>" class="small">Registrar la primera</a>
                    </div>
                <?php else: ?>
                    <canvas id="chAcciones" height="160"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="alert alert-info py-2 small">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Disciplina semanal:</strong> registra cada acción que hagas (post, evento, llamada, etc.) en el
        <a href="<?= base_url('marketing/acciones') ?>">diario</a>. Sin acciones registradas, los KPIs no se pueden interpretar.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chSemanas'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($s) => $s['label'], $seriesSemanas)) ?>,
        datasets: [{
            label: 'Leads nuevos',
            data: <?= json_encode(array_map(fn($s) => (int) $s['cantidad'], $seriesSemanas)) ?>,
            backgroundColor: '#0d6efd',
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

<?php if ($totalLeads > 0): ?>
new Chart(document.getElementById('chEstado'), {
    type: 'doughnut',
    data: {
        labels: ['Nuevo', 'Contactado', 'Calificado', 'Descartado'],
        datasets: [{
            data: [<?= $porEstado['nuevo'] ?>, <?= $porEstado['contactado'] ?>, <?= $porEstado['calificado'] ?>, <?= $porEstado['descartado'] ?>],
            backgroundColor: ['#0dcaf0', '#fd7e14', '#198754', '#6c757d'],
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
    }
});
<?php endif; ?>

<?php if (!empty($accionesMes)): ?>
new Chart(document.getElementById('chAcciones'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($a) => $a['tipo_nombre'], $accionesMes)) ?>,
        datasets: [{
            label: 'Cantidad',
            data: <?= json_encode(array_map(fn($a) => (int) $a['cantidad'], $accionesMes)) ?>,
            backgroundColor: <?= json_encode(array_map(fn($a) => $a['tipo_color'], $accionesMes)) ?>,
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
<?php endif; ?>
</script>
</body>
</html>
