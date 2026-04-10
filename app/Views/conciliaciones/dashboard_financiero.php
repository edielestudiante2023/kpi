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
        <?php
        $mesesNombre = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $rangos = [
            'todos' => 'Todo el año',
            'mes_actual' => 'Mes actual',
            'mes_anterior' => 'Mes anterior',
            'bimestre_anterior' => 'Bimestre anterior',
            'trimestre_anterior' => 'Trimestre anterior',
            'cuatrimestre_anterior' => 'Cuatrimestre anterior',
            'semestre_anterior' => 'Semestre anterior',
        ];
        ?>
        <form method="get" class="d-flex gap-2">
            <select name="anio" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
                <option value="todos" <?= $anioActual === 'todos' ? 'selected' : '' ?>>Todos</option>
                <?php foreach ($anios as $a): ?>
                    <option value="<?= $a['anio'] ?>" <?= $a['anio'] == $anioActual ? 'selected' : '' ?>><?= $a['anio'] ?></option>
                <?php endforeach; ?>
            </select>
            <select name="rango" class="form-select form-select-sm" style="width:170px;" onchange="this.form.submit()">
                <?php foreach ($rangos as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($rangoActual ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
                <option value="personalizado" <?= ($rangoActual ?? '') === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
                <optgroup label="Mes específico">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= sprintf('%02d', $m) ?>" <?= ($rangoActual ?? '') === sprintf('%02d', $m) ? 'selected' : '' ?>><?= $mesesNombre[$m] ?></option>
                    <?php endfor; ?>
                </optgroup>
            </select>
            <input type="date" name="desde" class="form-control form-control-sm" style="width:140px;" value="<?= $fechaDesde ?? '' ?>" onchange="document.querySelector('[name=rango]').value='personalizado'; this.form.submit()">
            <input type="date" name="hasta" class="form-control form-control-sm" style="width:140px;" value="<?= $fechaHasta ?? '' ?>" onchange="document.querySelector('[name=rango]').value='personalizado'; this.form.submit()">
        </form>
        <a href="<?= base_url('conciliaciones/dashboard') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
            <i class="bi bi-eraser"></i>
        </a>
    </div>

    <!-- Período -->
    <p class="text-muted mb-4">
        <i class="bi bi-calendar me-1"></i>
        <?= $fechaDesde ? date('d/m/Y', strtotime($fechaDesde)) . ' — ' . date('d/m/Y', strtotime($fechaHasta)) : 'Sin filtro de fecha' ?>
    </p>

    <!-- Estado de Resultados -->
    <div class="row mb-4">
        <div class="col">
            <div class="card border-success card-filtro" data-tipo="ingreso" style="cursor:pointer;" data-bs-toggle="tooltip" title="Suma de todos los ingresos bancarios (pagos de clientes, rendimientos, etc.) en el período. Clic para filtrar el desglose.">
                <div class="card-body text-center">
                    <h6 class="text-muted">Ingresos</h6>
                    <p class="h4 fw-bold text-success">$<?= number_format($ingresos, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-primary card-filtro" data-tipo="fijo" style="cursor:pointer;" data-bs-toggle="tooltip" title="Costos fijos: nómina, seguridad social, honorarios, herramientas digitales y otros gastos recurrentes. Clic para filtrar el desglose.">
                <div class="card-body text-center">
                    <h6 class="text-muted">Costos Fijos</h6>
                    <p class="h4 fw-bold text-primary">-$<?= number_format($costosFijos, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-warning card-filtro" data-tipo="variable" style="cursor:pointer;" data-bs-toggle="tooltip" title="Costos variables: impuestos, transporte, exámenes, operativos y otros gastos que varían según la actividad. Clic para filtrar el desglose.">
                <div class="card-body text-center">
                    <h6 class="text-muted">Costos Variables</h6>
                    <p class="h4 fw-bold text-warning">-$<?= number_format($costosVariables, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-danger card-filtro" data-tipo="todos" style="cursor:pointer;" data-bs-toggle="tooltip" title="Suma de costos fijos + variables. Clic para filtrar ambos tipos en el desglose.">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Costos</h6>
                    <p class="h4 fw-bold text-danger">-$<?= number_format($costosFijos + $costosVariables, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card <?= $utilidadOperativa >= 0 ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' ?>" data-bs-toggle="tooltip" title="Utilidad operativa = Ingresos - Costos Fijos - Costos Variables. Indica la ganancia o pérdida operativa del período.">
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
            <div class="card border-dark" data-bs-toggle="tooltip" title="Saldo actual de Banco <?= esc($cb['nombre']) ?>. Saldo inicial ($<?= number_format($cb['saldo_inicial'], 0, ',', '.') ?>) + movimientos del período ($<?= number_format($cb['movimientos'], 0, ',', '.') ?>).">
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
            <div class="card border-dark" data-bs-toggle="tooltip" title="Suma de saldos actuales de todas las cuentas bancarias registradas.">
                <div class="card-body text-center">
                    <h6 class="text-muted">Saldo Total Bancos</h6>
                    <p class="h4 fw-bold">$<?= number_format($saldoTotalBancos, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="<?= base_url('conciliaciones/facturacion?anio=todos&pagado=0') ?>" class="text-decoration-none" target="_blank">
            <div class="card border-info" style="cursor:pointer;" data-bs-toggle="tooltip" title="Total de facturas emitidas aún no pagadas. Clic para ver el detalle en Facturación.">
                <div class="card-body text-center">
                    <h6 class="text-muted">Cartera por Cobrar</h6>
                    <p class="h4 fw-bold text-info">$<?= number_format($cartera, 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $facturasPendientes ?> facturas pendientes</small>
                </div>
            </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= base_url('conciliaciones/deudas') ?>" class="text-decoration-none" target="_blank">
            <div class="card border-danger" style="cursor:pointer;" data-bs-toggle="tooltip" title="Saldo pendiente de todas las obligaciones/deudas activas. Clic para ver el detalle en Deudas.">
                <div class="card-body text-center">
                    <h6 class="text-muted">Deudas (pasivo)</h6>
                    <p class="h4 fw-bold text-danger">-$<?= number_format($deudaSaldo, 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $totalObligaciones ?> obligaciones activas</small>
                </div>
            </div>
            </a>
        </div>
        <div class="col-md-3">
            <div class="card <?= $posicionNeta >= 0 ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' ?>" data-bs-toggle="tooltip" title="Posición financiera neta = Utilidad Operativa + Saldo Bancos + Cartera por Cobrar - Deudas. Indica la salud financiera global.">
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
    <div class="card" id="seccionDesglose">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Desglose por Categoría</strong>
            <span id="filtroActivo" class="badge bg-secondary d-none"></span>
        </div>
        <div class="card-body">
            <table id="desgloseTable" class="table table-sm table-striped" style="font-size:0.9rem;">
                <thead class="table-dark">
                    <tr>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Peso %</th>
                        <th class="text-end">Movimientos</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $badges = ['fijo' => 'bg-primary', 'variable' => 'bg-warning text-dark', 'ingreso' => 'bg-success'];
                    $totalesTipo = ['fijo' => $costosFijos, 'variable' => $costosVariables, 'ingreso' => $ingresos];
                    foreach ($desglose as $d):
                        $totalTipo = abs($totalesTipo[$d['tipo']] ?? 1);
                        $pct = $totalTipo > 0 ? round(abs((float)$d['total_valor']) / $totalTipo * 100, 1) : 0;
                ?>
                    <tr data-tipo="<?= $d['tipo'] ?>">
                        <td><?= esc($d['categoria']) ?></td>
                        <td><span class="badge <?= $badges[$d['tipo']] ?? 'bg-secondary' ?>"><?= strtoupper($d['tipo']) ?></span></td>
                        <td class="text-end <?= (float)$d['total_valor'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            $<?= number_format((float)$d['total_valor'], 0, ',', '.') ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <div class="progress flex-grow-1" style="height:16px; max-width:120px;">
                                    <div class="progress-bar <?= $badges[$d['tipo']] ?? 'bg-secondary' ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                                <span class="fw-bold" style="min-width:45px;"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td class="text-end"><?= number_format($d['movimientos']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

// Activar tooltips de Bootstrap
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

// Cards clickeables → filtran desglose y hacen scroll
var filtroActual = null;
$('.card-filtro').on('click', function() {
    var tipo = $(this).data('tipo');
    var $seccion = $('#seccionDesglose');
    var $badge = $('#filtroActivo');
    var $rows = $('#desgloseTable tbody tr');

    // Toggle: si ya está activo, desactivar
    if (filtroActual === tipo) {
        filtroActual = null;
        $rows.show();
        $badge.addClass('d-none');
        $('.card-filtro').removeClass('shadow-lg');
        return;
    }

    filtroActual = tipo;
    $('.card-filtro').removeClass('shadow-lg');
    $(this).addClass('shadow-lg');

    if (tipo === 'todos') {
        // Total Costos: mostrar fijo + variable
        $rows.each(function() {
            var rowTipo = $(this).data('tipo');
            $(this).toggle(rowTipo === 'fijo' || rowTipo === 'variable');
        });
        $badge.text('COSTOS FIJOS + VARIABLES').removeClass('d-none');
    } else {
        $rows.each(function() {
            $(this).toggle($(this).data('tipo') === tipo);
        });
        var labels = {'ingreso':'INGRESOS','fijo':'COSTOS FIJOS','variable':'COSTOS VARIABLES'};
        $badge.text(labels[tipo] || tipo.toUpperCase()).removeClass('d-none');
    }

    // Scroll al desglose
    $('html, body').animate({ scrollTop: $seccion.offset().top - 80 }, 400);
});
</script>
</body>
</html>
