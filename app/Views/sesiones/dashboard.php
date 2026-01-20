<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiempo de Uso - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        .stat-card {
            border-radius: 12px;
            border: none;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .tiempo-formato {
            font-size: 1.8rem;
            font-weight: 600;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <!-- Usuario en sesion -->
        <div class="text-end mb-2">
            <span class="badge bg-primary fs-6">
                <i class="bi bi-person-circle me-1"></i>
                <?= esc(session()->get('nombre_completo')) ?>
            </span>
        </div>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <?= view('components/back_to_dashboard') ?>
                <h1 class="h3 mb-0"><i class="bi bi-clock-history me-2"></i>Tiempo de Uso</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('sesiones/activas') ?>" class="btn btn-outline-success">
                    <i class="bi bi-broadcast me-1"></i> Sesiones Activas
                </a>
                <a href="<?= base_url('sesiones/exportar') ?>?fecha_desde=<?= $filtros['fecha_desde'] ?>&fecha_hasta=<?= $filtros['fecha_hasta'] ?>" class="btn btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Exportar CSV
                </a>
            </div>
        </div>

        <!-- Filtros de fecha -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Fecha Desde</label>
                        <input type="text" name="fecha_desde" id="fecha_desde" class="form-control"
                               value="<?= $filtros['fecha_desde'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Fecha Hasta</label>
                        <input type="text" name="fecha_hasta" id="fecha_hasta" class="form-control"
                               value="<?= $filtros['fecha_hasta'] ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                        <a href="<?= base_url('sesiones/dashboard') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                    <div class="col-md-3 text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="?fecha_desde=<?= date('Y-m-d') ?>&fecha_hasta=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">Hoy</a>
                            <a href="?fecha_desde=<?= date('Y-m-d', strtotime('-7 days')) ?>&fecha_hasta=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">7 dias</a>
                            <a href="?fecha_desde=<?= date('Y-m-d', strtotime('-30 days')) ?>&fecha_hasta=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">30 dias</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tarjetas de estadisticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Tiempo Total</div>
                                <div class="tiempo-formato text-primary">
                                    <?= formatearTiempo($estadisticas['tiempo_total']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="bi bi-people"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Usuarios Unicos</div>
                                <div class="tiempo-formato text-success">
                                    <?= $estadisticas['usuarios_unicos'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Total Sesiones</div>
                                <div class="tiempo-formato text-info">
                                    <?= $estadisticas['total_sesiones'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                                <i class="bi bi-broadcast"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Sesiones Activas</div>
                                <div class="tiempo-formato text-warning">
                                    <?= $estadisticas['sesiones_activas'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Grafico de uso por dia -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Sesiones por Dia</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartUsoPorDia"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Promedio y estadisticas adicionales -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Promedios</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Duracion promedio por sesion:</span>
                            </div>
                            <div class="fs-4 fw-semibold text-primary">
                                <?= formatearTiempo($estadisticas['promedio_duracion']) ?>
                            </div>
                        </div>
                        <?php if ($estadisticas['usuarios_unicos'] > 0): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Tiempo promedio por usuario:</span>
                            </div>
                            <div class="fs-4 fw-semibold text-success">
                                <?= formatearTiempo(round($estadisticas['tiempo_total'] / $estadisticas['usuarios_unicos'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($estadisticas['total_sesiones'] > 0 && $estadisticas['usuarios_unicos'] > 0): ?>
                        <div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Sesiones por usuario:</span>
                            </div>
                            <div class="fs-4 fw-semibold text-info">
                                <?= round($estadisticas['total_sesiones'] / $estadisticas['usuarios_unicos'], 1) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de uso por usuario -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Tiempo por Usuario</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th class="text-center">Total Sesiones</th>
                                <th class="text-center">Tiempo Total</th>
                                <th class="text-center">Primera Sesion</th>
                                <th class="text-center">Ultima Sesion</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($porUsuario as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= esc($u['nombre_completo']) ?></strong>
                                </td>
                                <td class="text-muted"><?= esc($u['correo']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $u['total_sesiones'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold"><?= formatearTiempo($u['tiempo_total_segundos']) ?></span>
                                </td>
                                <td class="text-center small">
                                    <?= $u['primera_sesion'] ? date('d/m/Y H:i', strtotime($u['primera_sesion'])) : '-' ?>
                                </td>
                                <td class="text-center small">
                                    <?= $u['ultima_sesion'] ? date('d/m/Y H:i', strtotime($u['ultima_sesion'])) : '-' ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('sesiones/usuario/' . $u['id_usuario']) ?>"
                                       class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($porUsuario)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No hay datos de sesiones en este periodo
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        // Flatpickr para fechas
        flatpickr('#fecha_desde, #fecha_hasta', {
            locale: 'es',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true
        });

        // Grafico de sesiones por dia
        const datosGrafico = <?= json_encode($porDia) ?>;

        if (datosGrafico.length > 0) {
            const ctx = document.getElementById('chartUsoPorDia').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datosGrafico.map(d => {
                        const fecha = new Date(d.fecha);
                        return fecha.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
                    }),
                    datasets: [{
                        label: 'Sesiones',
                        data: datosGrafico.map(d => d.sesiones),
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Usuarios',
                        data: datosGrafico.map(d => d.usuarios),
                        backgroundColor: 'rgba(25, 135, 84, 0.7)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>

<?php
// Helper local para formatear tiempo
function formatearTiempo($segundos) {
    if ($segundos < 60) {
        return $segundos . 's';
    }
    if ($segundos < 3600) {
        return round($segundos / 60) . 'min';
    }
    $horas = floor($segundos / 3600);
    $minutos = round(($segundos % 3600) / 60);
    return $horas . 'h ' . $minutos . 'min';
}
?>
