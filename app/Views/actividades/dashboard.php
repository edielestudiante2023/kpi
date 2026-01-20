<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Actividades - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            transform: translateY(-4px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }
        .mini-stat {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: #f8f9fa;
        }

        /* ========== RESPONSIVE MOBILE ========== */
        @media (max-width: 768px) {
            /* Header más compacto */
            .d-flex.justify-content-between.align-items-center.mb-4 {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
            .d-flex.justify-content-between.align-items-center.mb-4 > .d-flex.gap-2:last-child {
                width: 100%;
                display: flex;
            }
            .d-flex.justify-content-between.align-items-center.mb-4 > .d-flex.gap-2:last-child .btn {
                flex: 1;
                font-size: 0.85rem;
            }

            /* Título de página */
            h1.h3 {
                font-size: 1.25rem;
            }

            /* Cards de estadísticas - 3 por fila */
            .stat-card .card-body {
                padding: 0.75rem;
            }
            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
            .stat-number {
                font-size: 1.5rem;
            }

            /* Layout columnas */
            .row.g-4 > .col-lg-8,
            .row.g-4 > .col-lg-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            /* Tabla de responsables - ocultar columnas */
            .table th:nth-child(3),
            .table td:nth-child(3),
            .table th:nth-child(5),
            .table td:nth-child(5) {
                display: none;
            }
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            /* Accesos rápidos */
            .d-grid.gap-2 .btn {
                font-size: 0.85rem;
            }

            /* Badge de usuario */
            .text-end.mb-2 .badge {
                font-size: 0.8rem !important;
            }
        }

        /* Pantallas muy pequeñas (< 480px) */
        @media (max-width: 480px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            /* Cards de estadísticas más pequeños */
            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
            .stat-number {
                font-size: 1.25rem;
            }
            .card-body.text-center .text-muted.small {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
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
                <h1 class="h3 mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard de Actividades</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-primary">
                    <i class="bi bi-kanban me-1"></i> Ver Tablero
                </a>
                <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-success">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
                </a>
            </div>
        </div>

        <!-- Resumen por estado -->
        <div class="row g-4 mb-4">
            <?php
            $totales = [
                'pendiente' => 0,
                'en_progreso' => 0,
                'en_revision' => 0,
                'completada' => 0,
                'cancelada' => 0
            ];
            foreach ($tablero as $estado => $acts) {
                $totales[$estado] = count($acts);
            }
            $total = array_sum($totales);
            ?>

            <!-- Total -->
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-dark text-white mx-auto mb-2">
                            <i class="bi bi-list-task"></i>
                        </div>
                        <div class="stat-number"><?= $total ?></div>
                        <div class="text-muted small">Total</div>
                    </div>
                </div>
            </div>

            <!-- Pendientes -->
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-secondary text-white mx-auto mb-2">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="stat-number"><?= $totales['pendiente'] ?></div>
                        <div class="text-muted small">Pendientes</div>
                    </div>
                </div>
            </div>

            <!-- En progreso -->
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-primary text-white mx-auto mb-2">
                            <i class="bi bi-play-circle"></i>
                        </div>
                        <div class="stat-number"><?= $totales['en_progreso'] ?></div>
                        <div class="text-muted small">En Progreso</div>
                    </div>
                </div>
            </div>

            <!-- En revision -->
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-purple text-white mx-auto mb-2" style="background-color: #6f42c1;">
                            <i class="bi bi-eye"></i>
                        </div>
                        <div class="stat-number"><?= $totales['en_revision'] ?></div>
                        <div class="text-muted small">En Revision</div>
                    </div>
                </div>
            </div>

            <!-- Completadas -->
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-success text-white mx-auto mb-2">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-number"><?= $totales['completada'] ?></div>
                        <div class="text-muted small">Completadas</div>
                    </div>
                </div>
            </div>

            <!-- Canceladas -->
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-danger text-white mx-auto mb-2">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="stat-number"><?= $totales['cancelada'] ?></div>
                        <div class="text-muted small">Canceladas</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Estadisticas por usuario -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-people me-2"></i>Actividades por Responsable</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Responsable</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Pendientes</th>
                                        <th class="text-center">En Progreso</th>
                                        <th class="text-center">Completadas</th>
                                        <th class="text-center">Vencidas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($estadisticas)): ?>
                                        <?php foreach ($estadisticas as $est): ?>
                                            <?php if ($est['total_asignadas'] > 0): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= esc($est['nombre_completo']) ?></strong>
                                                    </td>
                                                    <td class="text-center"><?= $est['total_asignadas'] ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary"><?= $est['pendientes'] ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary"><?= $est['en_progreso'] ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-success"><?= $est['completadas'] ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($est['vencidas'] > 0): ?>
                                                            <span class="badge bg-danger"><?= $est['vencidas'] ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No hay actividades asignadas
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accesos rapidos -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Accesos Rapidos</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-outline-primary">
                                <i class="bi bi-kanban me-2"></i> Tablero por Estado
                            </a>
                            <a href="<?= base_url('actividades/responsable') ?>" class="btn btn-outline-primary">
                                <i class="bi bi-people me-2"></i> Tablero por Responsable
                            </a>
                            <a href="<?= base_url('actividades/mis-actividades') ?>" class="btn btn-outline-primary">
                                <i class="bi bi-person me-2"></i> Mis Actividades
                            </a>
                            <a href="<?= base_url('actividades/lista') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-table me-2"></i> Vista de Lista
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Actividades recientes -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recientes</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php
                            $recientes = [];
                            foreach ($tablero as $acts) {
                                $recientes = array_merge($recientes, $acts);
                            }
                            usort($recientes, fn($a, $b) => strtotime($b['fecha_creacion']) - strtotime($a['fecha_creacion']));
                            $recientes = array_slice($recientes, 0, 5);
                            ?>
                            <?php foreach ($recientes as $act): ?>
                                <li class="list-group-item">
                                    <a href="<?= base_url('actividades/ver/' . $act['id_actividad']) ?>" class="text-decoration-none">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-truncate" style="max-width: 200px;">
                                                <?= esc($act['titulo']) ?>
                                            </span>
                                            <small class="text-muted">
                                                <?= date('d/m', strtotime($act['fecha_creacion'])) ?>
                                            </small>
                                        </div>
                                        <small class="text-muted"><?= esc($act['codigo']) ?></small>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($recientes)): ?>
                                <li class="list-group-item text-center text-muted py-3">
                                    Sin actividades recientes
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
