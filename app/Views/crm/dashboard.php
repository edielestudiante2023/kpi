<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard CRM – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .kpi-card { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .kpi-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-value { font-size: 1.6rem; font-weight: 700; color: #2c3e50; line-height: 1.1; }
        .kpi-sub { font-size: 0.7rem; color: #6c757d; margin-top: 2px; }
        .chart-card { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); height: 100%; }
        .chart-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 12px; color: #2c3e50; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php
$abiertas       = (int) ($metricas['abiertas'] ?? 0);
$valorPipeline  = (float) ($metricas['valor_pipeline'] ?? 0);
$ganadas        = (int) ($metricas['ganadas'] ?? 0);
$valorGanado    = (float) ($metricas['valor_ganado'] ?? 0);
$perdidas       = (int) ($metricas['perdidas'] ?? 0);
$tasaConversion = (float) ($metricas['tasa_conversion'] ?? 0);

// Datos para charts
$funnelLabels = array_map(fn($x) => $x['nombre'], $funnel);
$funnelCants  = array_map(fn($x) => (int) $x['cantidad'], $funnel);
$funnelVals   = array_map(fn($x) => (float) $x['valor_total'], $funnel);
$funnelCols   = array_map(fn($x) => $x['color'], $funnel);

$wlLabels   = array_map(fn($x) => $x['periodo'], $wonLost);
$wlGanadas  = array_map(fn($x) => (int) $x['ganadas'], $wonLost);
$wlPerdidas = array_map(fn($x) => (int) $x['perdidas'], $wonLost);
?>

<div class="container-fluid py-3 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard CRM</h1>
        <div>
            <a href="<?= base_url('crm/oportunidades/kanban') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-kanban me-1"></i> Pipeline
            </a>
            <a href="<?= base_url('crm/oportunidades/lista') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i> Lista
            </a>
            <a href="<?= base_url('crm/oportunidades/nueva') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nueva
            </a>
        </div>
    </div>
    <?php if (!$esAdmin): ?>
        <div class="alert alert-info py-2 small">Mostrando solo tus oportunidades. Los administradores CRM ven todo.</div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Pipeline abierto</div>
            <div class="kpi-value text-primary"><?= $abiertas ?></div>
            <div class="kpi-sub">$<?= number_format($valorPipeline, 0, ',', '.') ?> potenciales</div>
        </div></div>
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Ganadas</div>
            <div class="kpi-value text-success"><?= $ganadas ?></div>
            <div class="kpi-sub">$<?= number_format($valorGanado, 0, ',', '.') ?> cerrados</div>
        </div></div>
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Perdidas</div>
            <div class="kpi-value text-danger"><?= $perdidas ?></div>
            <div class="kpi-sub">&nbsp;</div>
        </div></div>
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Tasa de conversión</div>
            <div class="kpi-value"><?= $tasaConversion ?>%</div>
            <div class="kpi-sub">Ganadas / cerradas</div>
        </div></div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="chart-card">
                <div class="chart-title">Funnel — cantidad por etapa</div>
                <canvas id="chFunnel" height="80"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-card">
                <div class="chart-title">Valor por etapa</div>
                <canvas id="chDonut" height="120"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-7">
            <div class="chart-card">
                <div class="chart-title">Cierres últimos 6 meses</div>
                <canvas id="chWonLost" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-5">
            <div class="chart-card">
                <div class="chart-title">Ranking por responsable (valor ganado)</div>
                <?php if (empty($ranking)): ?>
                    <div class="text-muted small text-center py-4">Sin datos todavía.</div>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Vendedor</th><th class="text-end">Ganado</th><th class="text-center">G/A</th></tr></thead>
                        <tbody>
                        <?php foreach ($ranking as $r): ?>
                            <tr>
                                <td class="small"><?= esc($r['nombre_completo']) ?></td>
                                <td class="text-end fw-bold text-success">$<?= number_format((float) $r['valor_ganado'], 0, ',', '.') ?></td>
                                <td class="text-center small text-muted"><?= (int) $r['ganadas'] ?>/<?= (int) $r['abiertas'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tareas pendientes del usuario -->
    <div class="chart-card">
        <div class="chart-title"><i class="bi bi-list-task me-1"></i>Mis tareas pendientes</div>
        <?php if (empty($tareasPendientes)): ?>
            <div class="text-muted small text-center py-3">Sin tareas pendientes. 👍</div>
        <?php else: ?>
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>Tipo</th><th>Asunto</th><th>Programada</th><th>Recordatorio</th></tr>
                </thead>
                <tbody>
                <?php foreach ($tareasPendientes as $t): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= esc($t['tipo']) ?></span></td>
                        <td><?= esc($t['asunto']) ?></td>
                        <td class="small"><?= !empty($t['fecha_programada']) ? date('d/m/Y H:i', strtotime($t['fecha_programada'])) : '—' ?></td>
                        <td class="small"><?= !empty($t['recordatorio_at']) ? date('d/m/Y H:i', strtotime($t['recordatorio_at'])) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const FUNNEL = {
    labels: <?= json_encode($funnelLabels) ?>,
    cants:  <?= json_encode($funnelCants) ?>,
    vals:   <?= json_encode($funnelVals) ?>,
    colors: <?= json_encode($funnelCols) ?>
};
const WL = {
    labels:  <?= json_encode($wlLabels) ?>,
    ganadas: <?= json_encode($wlGanadas) ?>,
    perdidas:<?= json_encode($wlPerdidas) ?>
};

// Funnel: barra horizontal por etapa
new Chart(document.getElementById('chFunnel'), {
    type: 'bar',
    data: {
        labels: FUNNEL.labels,
        datasets: [{
            label: 'Cantidad',
            data: FUNNEL.cants,
            backgroundColor: FUNNEL.colors,
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// Donut: valor por etapa
new Chart(document.getElementById('chDonut'), {
    type: 'doughnut',
    data: {
        labels: FUNNEL.labels,
        datasets: [{ data: FUNNEL.vals, backgroundColor: FUNNEL.colors }]
    },
    options: {
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ctx.label + ': $' + Number(ctx.parsed).toLocaleString('es-CO') } }
        }
    }
});

// Won/Lost por mes
new Chart(document.getElementById('chWonLost'), {
    type: 'line',
    data: {
        labels: WL.labels,
        datasets: [
            { label: 'Ganadas', data: WL.ganadas, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', fill: true, tension: 0.3 },
            { label: 'Perdidas', data: WL.perdidas, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)', fill: true, tension: 0.3 }
        ]
    },
    options: {
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
</body>
</html>
