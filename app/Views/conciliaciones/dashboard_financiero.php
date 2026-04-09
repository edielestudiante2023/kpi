<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financiero – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <?= view('components/back_to_dashboard') ?>
            <h1 class="h3 mb-0">Dashboard Financiero</h1>
        </div>

        <!-- Filtros -->
        <form method="get" class="d-flex gap-2">
            <select name="anio" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
                <?php foreach ($anios as $a): ?>
                    <option value="<?= $a['anio'] ?>" <?= $a['anio'] == $anioActual ? 'selected' : '' ?>><?= $a['anio'] ?></option>
                <?php endforeach; ?>
            </select>
            <select name="mes" class="form-select form-select-sm" style="width:140px;" onchange="this.form.submit()">
                <option value="">Todo el año</option>
                <?php
                $mesesNombre = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                for ($m = 1; $m <= 12; $m++):
                ?>
                    <option value="<?= $m ?>" <?= $mesActual == $m ? 'selected' : '' ?>><?= $mesesNombre[$m] ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <!-- Período -->
    <p class="text-muted mb-4">
        <i class="bi bi-calendar me-1"></i>
        <?= $mesActual ? $mesesNombre[(int)$mesActual] . ' ' . $anioActual : 'Año ' . $anioActual ?>
    </p>

    <!-- Estado de Resultados -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h6 class="text-muted">Ingresos</h6>
                    <p class="h4 fw-bold text-success">$<?= number_format($ingresos, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h6 class="text-muted">Costos Fijos</h6>
                    <p class="h4 fw-bold text-primary">-$<?= number_format($costosFijos, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h6 class="text-muted">Costos Variables</h6>
                    <p class="h4 fw-bold text-warning">-$<?= number_format($costosVariables, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?= $utilidadOperativa >= 0 ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' ?>">
                <div class="card-body text-center">
                    <h6 class="text-muted">Utilidad Operativa</h6>
                    <p class="h4 fw-bold <?= $utilidadOperativa >= 0 ? 'text-success' : 'text-danger' ?>">
                        $<?= number_format($utilidadOperativa, 0, ',', '.') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Saldos Bancarios -->
    <div class="row mb-4">
        <?php foreach ($cuentasBanco as $cb): ?>
        <div class="col-md-<?= count($cuentasBanco) > 2 ? '4' : '6' ?>">
            <div class="card border-dark">
                <div class="card-body text-center">
                    <h6 class="text-muted">Banco <?= esc($cb['nombre']) ?></h6>
                    <p class="h4 fw-bold <?= $cb['saldo_actual'] >= 0 ? 'text-dark' : 'text-danger' ?>">
                        $<?= number_format($cb['saldo_actual'], 0, ',', '.') ?>
                    </p>
                    <small class="text-muted">
                        Inicial: $<?= number_format($cb['saldo_inicial'], 0, ',', '.') ?> |
                        Mov: $<?= number_format($cb['movimientos'], 0, ',', '.') ?>
                    </small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Cartera + Deudas + Posición Neta -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-dark">
                <div class="card-body text-center">
                    <h6 class="text-muted">Saldo Total Bancos</h6>
                    <p class="h4 fw-bold">$<?= number_format($saldoTotalBancos, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h6 class="text-muted">Cartera por Cobrar</h6>
                    <p class="h4 fw-bold text-info">$<?= number_format($cartera, 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $facturasPendientes ?> facturas pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h6 class="text-muted">Deudas (pasivo)</h6>
                    <p class="h4 fw-bold text-danger">-$<?= number_format($deudaSaldo, 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $totalObligaciones ?> obligaciones activas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?= $posicionNeta >= 0 ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' ?>">
                <div class="card-body text-center">
                    <h6 class="text-muted">Posición Neta</h6>
                    <p class="h3 fw-bold <?= $posicionNeta >= 0 ? 'text-success' : 'text-danger' ?>">
                        $<?= number_format($posicionNeta, 0, ',', '.') ?>
                    </p>
                    <small class="text-muted">Utilidad + Cartera + Bancos - Deudas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><strong>Evolución Mensual <?= $anioActual ?></strong></div>
                <div class="card-body">
                    <canvas id="chartEvolucion" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>Distribución</strong></div>
                <div class="card-body">
                    <canvas id="chartDistribucion" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Desglose por categoría -->
    <div class="card">
        <div class="card-header"><strong>Desglose por Categoría</strong></div>
        <div class="card-body">
            <table class="table table-sm table-striped" style="font-size:0.9rem;">
                <thead class="table-dark">
                    <tr>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Movimientos</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($desglose as $d):
                    $badges = ['fijo' => 'bg-primary', 'variable' => 'bg-warning text-dark', 'ingreso' => 'bg-success'];
                ?>
                    <tr>
                        <td><?= esc($d['categoria']) ?></td>
                        <td><span class="badge <?= $badges[$d['tipo']] ?? 'bg-secondary' ?>"><?= strtoupper($d['tipo']) ?></span></td>
                        <td class="text-end <?= (float)$d['total_valor'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            $<?= number_format((float)$d['total_valor'], 0, ',', '.') ?>
                        </td>
                        <td class="text-end"><?= number_format($d['movimientos']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Evolución mensual
var mesesLabels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
var dataIngresos = <?= json_encode(array_map(fn($m) => $m['ingreso'], $evolucionMensual)) ?>;
var dataFijos = <?= json_encode(array_map(fn($m) => abs($m['fijo']), $evolucionMensual)) ?>;
var dataVariables = <?= json_encode(array_map(fn($m) => abs($m['variable']), $evolucionMensual)) ?>;

new Chart(document.getElementById('chartEvolucion'), {
    type: 'bar',
    data: {
        labels: mesesLabels,
        datasets: [
            { label: 'Ingresos', data: Object.values(dataIngresos), backgroundColor: 'rgba(25,135,84,0.7)' },
            { label: 'Costos Fijos', data: Object.values(dataFijos), backgroundColor: 'rgba(13,110,253,0.7)' },
            { label: 'Costos Variables', data: Object.values(dataVariables), backgroundColor: 'rgba(255,193,7,0.7)' }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { ticks: { callback: v => '$' + v.toLocaleString('es-CO') } }
        }
    }
});

// Distribución
new Chart(document.getElementById('chartDistribucion'), {
    type: 'doughnut',
    data: {
        labels: ['Ingresos', 'Costos Fijos', 'Costos Variables'],
        datasets: [{
            data: [<?= $ingresos ?>, <?= $costosFijos ?>, <?= $costosVariables ?>],
            backgroundColor: ['rgba(25,135,84,0.8)', 'rgba(13,110,253,0.8)', 'rgba(255,193,7,0.8)']
        }]
    },
    options: { responsive: true }
});
</script>
</body>
</html>
