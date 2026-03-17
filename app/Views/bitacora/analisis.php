<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<?php
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

$mesAnterior   = $mes == 1 ? 12 : $mes - 1;
$anioAnterior  = $mes == 1 ? $anio - 1 : $anio;
$mesSiguiente  = $mes == 12 ? 1 : $mes + 1;
$anioSiguiente = $mes == 12 ? $anio + 1 : $anio;
$esMesActual   = ($anio == date('Y') && $mes == date('n'));

$baseUrl      = 'bitacora/analisis';
$usuarioParam = ($esAdmin && $filtroUsuario) ? '?usuario=' . $filtroUsuario : '';
?>

<h6 class="text-muted mb-3">
    <i class="bi bi-bar-chart-line me-1"></i> Análisis de Actividades
</h6>

<!-- Selector de mes -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between">
            <a href="<?= base_url("{$baseUrl}/{$anioAnterior}/{$mesAnterior}{$usuarioParam}") ?>"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span class="fw-bold"><?= $meses[$mes] ?> <?= $anio ?></span>
            <?php if (!$esMesActual): ?>
                <a href="<?= base_url("{$baseUrl}/{$anioSiguiente}/{$mesSiguiente}{$usuarioParam}") ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-secondary disabled">
                    <i class="bi bi-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Selector de usuario (solo admin/superadmin) -->
<?php if ($esAdmin): ?>
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= base_url("{$baseUrl}/{$anio}/{$mes}") ?>">
            <div class="d-flex gap-2 align-items-center">
                <label class="text-muted small mb-0 text-nowrap">
                    <i class="bi bi-person me-1"></i>Usuario:
                </label>
                <select name="usuario" class="form-select form-select-sm"
                        onchange="this.form.submit()">
                    <option value="">Todos los usuarios</option>
                    <?php foreach ($usuariosLista as $u): ?>
                        <option value="<?= $u['id_users'] ?>"
                            <?= $filtroUsuario == $u['id_users'] ? 'selected' : '' ?>>
                            <?= esc($u['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Stat cards -->
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-clock"></i><br>
            <strong><?= formatMinutosHoras($totalMinutos) ?></strong>
            <div class="small" style="opacity:0.7;">Total mes</div>
        </div>
    </div>
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-calendar-check"></i><br>
            <strong><?= $totalDias ?></strong>
            <div class="small" style="opacity:0.7;">Días</div>
        </div>
    </div>
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-list-check"></i><br>
            <strong><?= $totalActividades ?></strong>
            <div class="small" style="opacity:0.7;">Actividades</div>
        </div>
    </div>
</div>

<?php if ($totalMinutos > 0): ?>

<!-- Chart 1: Horas por día -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-title small text-muted mb-2">
            <i class="bi bi-bar-chart me-1"></i> Horas por día
        </h6>
        <div style="position:relative; height:180px;">
            <canvas id="chartDias"></canvas>
        </div>
    </div>
</div>

<!-- Chart 2: Tiempo por Centro de Costo -->
<?php if (!empty(json_decode($chartCCData, true))): ?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-title small text-muted mb-2">
            <i class="bi bi-pie-chart me-1"></i> Tiempo por Centro de Costo
        </h6>
        <div style="position:relative; height:220px;">
            <canvas id="chartCC"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Chart 3: Horas por semana -->
<?php if (!empty(json_decode($chartSemData, true))): ?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-title small text-muted mb-2">
            <i class="bi bi-calendar-week me-1"></i> Horas por semana
        </h6>
        <div style="position:relative; height:180px;">
            <canvas id="chartSemanal"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Chart 4: Top actividades -->
<?php
$topCount = count(json_decode($chartTopLabels, true) ?? []);
if ($topCount > 0):
    $chartTopHeight = max(120, $topCount * 30 + 20);
?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-title small text-muted mb-2">
            <i class="bi bi-list-ol me-1"></i> Top actividades por tiempo
        </h6>
        <div style="position:relative; height:<?= $chartTopHeight ?>px;">
            <canvas id="chartTop"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- Empty state -->
<div class="text-center text-muted py-4">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    Sin registros en <?= $meses[$mes] ?> <?= $anio ?>
</div>
<?php endif; ?>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<?php if ($totalMinutos > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var COLORS = [
        '#0d6efd','#198754','#ffc107','#dc3545',
        '#0dcaf0','#6f42c1','#fd7e14','#20c997','#6c757d'
    ];

    Chart.defaults.font.size = 11;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;

    // --- Chart 1: Horas por día ---
    var ctxDias = document.getElementById('chartDias');
    if (ctxDias) {
        new Chart(ctxDias, {
            type: 'bar',
            data: {
                labels: <?= $chartDiasLabels ?>,
                datasets: [{
                    label: 'Horas',
                    data: <?= $chartDiasData ?>,
                    backgroundColor: 'rgba(13,110,253,0.7)',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    borderRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.parsed.y.toFixed(1) + 'h';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { maxTicksLimit: 12, font: { size: 10 } },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (v) { return v + 'h'; },
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    }

    // --- Chart 2: Centro de Costo (doughnut) ---
    var ctxCC = document.getElementById('chartCC');
    if (ctxCC) {
        var ccLabels = <?= $chartCCLabels ?>;
        new Chart(ctxCC, {
            type: 'doughnut',
            data: {
                labels: ccLabels,
                datasets: [{
                    data: <?= $chartCCData ?>,
                    backgroundColor: COLORS.slice(0, ccLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 8, font: { size: 10 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + ctx.parsed.toFixed(1) + 'h (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // --- Chart 3: Horas por semana ---
    var ctxSem = document.getElementById('chartSemanal');
    if (ctxSem) {
        new Chart(ctxSem, {
            type: 'bar',
            data: {
                labels: <?= $chartSemLabels ?>,
                datasets: [{
                    label: 'Horas',
                    data: <?= $chartSemData ?>,
                    backgroundColor: 'rgba(25,135,84,0.7)',
                    borderColor: '#198754',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { font: { size: 9 } }, grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (v) { return v + 'h'; },
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    }

    // --- Chart 4: Top actividades (horizontal bar) ---
    var ctxTop = document.getElementById('chartTop');
    if (ctxTop) {
        var topLabels = <?= $chartTopLabels ?>;
        var topColors = topLabels.map(function (_, i) { return COLORS[i % COLORS.length]; });
        new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels: topLabels,
                datasets: [{
                    label: 'Horas',
                    data: <?= $chartTopData ?>,
                    backgroundColor: topColors.map(function (c) {
                        return c + 'BF'; // ~75% opacity via hex
                    }),
                    borderColor: topColors,
                    borderWidth: 1,
                    borderRadius: 3
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (v) { return v + 'h'; },
                            font: { size: 10 }
                        }
                    },
                    y: { ticks: { font: { size: 10 } } }
                }
            }
        });
    }
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
