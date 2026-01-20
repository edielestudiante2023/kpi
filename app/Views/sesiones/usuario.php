<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesiones de <?= esc($usuario['nombre_completo']) ?> - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
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
                <h1 class="h3 mb-0">
                    <i class="bi bi-person me-2"></i>
                    Sesiones de <?= esc($usuario['nombre_completo']) ?>
                </h1>
            </div>
            <a href="<?= base_url('sesiones/dashboard') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
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
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen -->
        <?php
        $totalSesiones = count($sesiones);
        $tiempoTotal = array_sum(array_column($sesiones, 'duracion_segundos'));
        ?>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted">Total Sesiones</div>
                        <div class="fs-2 fw-bold text-primary"><?= $totalSesiones ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted">Tiempo Total</div>
                        <div class="fs-2 fw-bold text-success"><?= formatearTiempo($tiempoTotal) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted">Promedio por Sesion</div>
                        <div class="fs-2 fw-bold text-info">
                            <?= $totalSesiones > 0 ? formatearTiempo(round($tiempoTotal / $totalSesiones)) : '-' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de sesiones -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Historial de Sesiones</h6>
            </div>
            <div class="card-body">
                <?php if (empty($sesiones)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        <h5>No hay sesiones en este periodo</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th class="text-center">Duracion</th>
                                    <th>IP</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sesiones as $s): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i:s', strtotime($s['fecha_inicio'])) ?></td>
                                    <td>
                                        <?= $s['fecha_fin'] ? date('d/m/Y H:i:s', strtotime($s['fecha_fin'])) : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            <?= formatearTiempo($s['duracion_segundos']) ?>
                                        </span>
                                    </td>
                                    <td><code><?= esc($s['ip_address'] ?? '-') ?></code></td>
                                    <td class="text-center">
                                        <?php if ($s['activa']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-circle-fill me-1 small"></i> Activa
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cerrada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        flatpickr('#fecha_desde, #fecha_hasta', {
            locale: 'es',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true
        });
    </script>
</body>
</html>

<?php
function formatearTiempo($segundos) {
    if ($segundos < 60) {
        return $segundos . 's';
    }
    if ($segundos < 3600) {
        return round($segundos / 60) . ' min';
    }
    $horas = floor($segundos / 3600);
    $minutos = round(($segundos % 3600) / 60);
    return $horas . 'h ' . $minutos . 'min';
}
?>
