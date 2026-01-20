<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Actividades - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        .badge-estado { padding: 0.3rem 0.6rem; }
        .estado-pendiente { background-color: #6c757d; }
        .estado-en_progreso { background-color: #0d6efd; }
        .estado-en_revision { background-color: #6f42c1; }
        .estado-completada { background-color: #198754; }
        .estado-cancelada { background-color: #dc3545; }

        .prioridad-urgente { color: #dc3545; }
        .prioridad-alta { color: #fd7e14; }
        .prioridad-media { color: #ffc107; }
        .prioridad-baja { color: #198754; }

        .fecha-vencida { color: #dc3545; font-weight: 600; }

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

            /* Tabs más compactos */
            .nav-tabs .nav-link {
                font-size: 0.85rem;
                padding: 0.5rem 0.75rem;
            }
            .nav-tabs .nav-link .badge {
                font-size: 0.7rem;
            }

            /* Tabla responsive - ocultar algunas columnas */
            #tablaAsignadas th:nth-child(5),
            #tablaAsignadas td:nth-child(5),
            #tablaAsignadas th:nth-child(6),
            #tablaAsignadas td:nth-child(6),
            #tablaCreadas th:nth-child(5),
            #tablaCreadas td:nth-child(5),
            #tablaCreadas th:nth-child(6),
            #tablaCreadas td:nth-child(6) {
                display: none;
            }

            /* Celdas de tabla más compactas */
            .table td, .table th {
                padding: 0.5rem;
                font-size: 0.85rem;
            }
            .table code {
                font-size: 0.75rem;
            }
            .badge-estado {
                padding: 0.2rem 0.4rem;
                font-size: 0.7rem;
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
            /* Ocultar más columnas en móvil muy pequeño */
            #tablaAsignadas th:nth-child(4),
            #tablaAsignadas td:nth-child(4),
            #tablaCreadas th:nth-child(4),
            #tablaCreadas td:nth-child(4) {
                display: none;
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
                <h1 class="h3 mb-0"><i class="bi bi-person-badge me-2"></i>Mis Actividades</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-kanban me-1"></i> Ver Tablero
                </a>
                <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
                </a>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="misTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="asignadas-tab" data-bs-toggle="tab" data-bs-target="#asignadas" type="button">
                    <i class="bi bi-inbox me-1"></i> Asignadas a mi
                    <span class="badge bg-primary ms-1"><?= count($asignadas) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="creadas-tab" data-bs-toggle="tab" data-bs-target="#creadas" type="button">
                    <i class="bi bi-pencil-square me-1"></i> Creadas por mi
                    <span class="badge bg-secondary ms-1"><?= count($creadas) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="misTabsContent">
            <!-- Asignadas -->
            <div class="tab-pane fade show active" id="asignadas" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (empty($asignadas)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <h5>No tienes actividades asignadas</h5>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaAsignadas">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Codigo</th>
                                            <th>Titulo</th>
                                            <th>Estado</th>
                                            <th>Prioridad</th>
                                            <th>Fecha Limite</th>
                                            <th>Creado por</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($asignadas as $act): ?>
                                            <tr>
                                                <td><code><?= esc($act['codigo']) ?></code></td>
                                                <td><?= esc($act['titulo']) ?></td>
                                                <td>
                                                    <span class="badge badge-estado estado-<?= $act['estado'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $act['estado'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="prioridad-<?= $act['prioridad'] ?>">
                                                        <i class="bi bi-flag-fill me-1"></i>
                                                        <?= ucfirst($act['prioridad']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($act['fecha_limite']): ?>
                                                        <?php
                                                        $dias = $act['dias_restantes'] ?? 0;
                                                        $clase = ($dias < 0 && !in_array($act['estado'], ['completada', 'cancelada'])) ? 'fecha-vencida' : '';
                                                        ?>
                                                        <span class="<?= $clase ?>">
                                                            <?= date('d/m/Y', strtotime($act['fecha_limite'])) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= esc($act['nombre_creador']) ?></td>
                                                <td>
                                                    <a href="<?= base_url('actividades/ver/' . $act['id_actividad']) ?>"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
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

            <!-- Creadas -->
            <div class="tab-pane fade" id="creadas" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (empty($creadas)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-pencil-square fs-1 d-block mb-2"></i>
                                <h5>No has creado actividades</h5>
                                <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-lg me-1"></i> Crear Actividad
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaCreadas">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Codigo</th>
                                            <th>Titulo</th>
                                            <th>Estado</th>
                                            <th>Prioridad</th>
                                            <th>Asignado a</th>
                                            <th>Fecha Limite</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($creadas as $act): ?>
                                            <tr>
                                                <td><code><?= esc($act['codigo']) ?></code></td>
                                                <td><?= esc($act['titulo']) ?></td>
                                                <td>
                                                    <span class="badge badge-estado estado-<?= $act['estado'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $act['estado'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="prioridad-<?= $act['prioridad'] ?>">
                                                        <i class="bi bi-flag-fill me-1"></i>
                                                        <?= ucfirst($act['prioridad']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $act['nombre_asignado'] ? esc($act['nombre_asignado']) : '<span class="text-muted">Sin asignar</span>' ?>
                                                </td>
                                                <td>
                                                    <?php if ($act['fecha_limite']): ?>
                                                        <?= date('d/m/Y', strtotime($act['fecha_limite'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?= base_url('actividades/ver/' . $act['id_actividad']) ?>"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
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
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tablaAsignadas, #tablaCreadas').DataTable({
                pageLength: 10,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>
