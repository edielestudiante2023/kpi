<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Actividades - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

        /* Stat cards */
        .stat-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.15s, box-shadow 0.15s;
            cursor: pointer;
            text-decoration: none;
            user-select: none;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .stat-card.active {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.8);
            transform: translateY(-1px);
        }
        .stat-card.active .stat-label { color: #0d6efd; font-weight: 600; }
        .stat-icon {
            width: 38px; height: 38px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .stat-number { font-size: 1.3rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.4px; }

        /* Filtro fecha */
        .filtro-fecha-card {
            border-radius: 10px;
            background: white;
        }

        /* ========== RESPONSIVE MOBILE ========== */
        @media (max-width: 768px) {
            .d-flex.justify-content-between.align-items-center.mb-4 {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
            .d-flex.justify-content-between.align-items-center.mb-4 > .d-flex.gap-2:last-child {
                width: 100%; display: flex;
            }
            .d-flex.justify-content-between.align-items-center.mb-4 > .d-flex.gap-2:last-child .btn {
                flex: 1; font-size: 0.85rem;
            }
            h1.h3 { font-size: 1.25rem; }
            .nav-tabs .nav-link { font-size: 0.85rem; padding: 0.5rem 0.75rem; }
            .nav-tabs .nav-link .badge { font-size: 0.7rem; }
            #tablaAsignadas th:nth-child(5), #tablaAsignadas td:nth-child(5),
            #tablaAsignadas th:nth-child(6), #tablaAsignadas td:nth-child(6),
            #tablaCreadas th:nth-child(5), #tablaCreadas td:nth-child(5),
            #tablaCreadas th:nth-child(6), #tablaCreadas td:nth-child(6) { display: none; }
            .table td, .table th { padding: 0.5rem; font-size: 0.85rem; }
            .table code { font-size: 0.75rem; }
            .badge-estado { padding: 0.2rem 0.4rem; font-size: 0.7rem; }
            .text-end.mb-2 .badge { font-size: 0.8rem !important; }
            .stat-number { font-size: 1.1rem; }
            .stat-label { font-size: 0.65rem; }
        }

        @media (max-width: 480px) {
            .container { padding-left: 0.75rem; padding-right: 0.75rem; }
            #tablaAsignadas th:nth-child(4), #tablaAsignadas td:nth-child(4),
            #tablaCreadas th:nth-child(4), #tablaCreadas td:nth-child(4) { display: none; }
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <?php
    // Estadísticas para "Asignadas a mí"
    $statsA = ['total' => count($asignadas), 'pendiente' => 0, 'en_progreso' => 0, 'en_revision' => 0, 'completada' => 0, 'cancelada' => 0, 'vencidas' => 0];
    $statsAP = ['urgente' => 0, 'alta' => 0, 'media' => 0, 'baja' => 0];
    foreach ($asignadas as $a) {
        if (isset($statsA[$a['estado']])) $statsA[$a['estado']]++;
        if (isset($statsAP[$a['prioridad']])) $statsAP[$a['prioridad']]++;
        if (!empty($a['fecha_limite']) && ($a['dias_restantes'] ?? 1) < 0 && !in_array($a['estado'], ['completada', 'cancelada'])) {
            $statsA['vencidas']++;
        }
    }

    // Estadísticas para "Creadas por mí"
    $statsC = ['total' => count($creadas), 'pendiente' => 0, 'en_progreso' => 0, 'en_revision' => 0, 'completada' => 0, 'cancelada' => 0, 'vencidas' => 0];
    $statsCP = ['urgente' => 0, 'alta' => 0, 'media' => 0, 'baja' => 0];
    foreach ($creadas as $a) {
        if (isset($statsC[$a['estado']])) $statsC[$a['estado']]++;
        if (isset($statsCP[$a['prioridad']])) $statsCP[$a['prioridad']]++;
        if (!empty($a['fecha_limite']) && ($a['dias_restantes'] ?? 1) < 0 && !in_array($a['estado'], ['completada', 'cancelada'])) {
            $statsC['vencidas']++;
        }
    }
    ?>

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

        <!-- Filtro de rango de fechas -->
        <div class="card filtro-fecha-card shadow-sm mb-4 px-3 py-2">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="text-muted small fw-semibold"><i class="bi bi-calendar-range me-1"></i>Rango de fecha límite</span>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" id="inputFechaDesde" class="form-control form-control-sm" placeholder="Desde" style="width:130px">
                    <span class="text-muted">—</span>
                    <input type="text" id="inputFechaHasta" class="form-control form-control-sm" placeholder="Hasta" style="width:130px">
                </div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-warning" id="btnVencidas" title="Mostrar vencidas">
                        <i class="bi bi-exclamation-triangle me-1"></i>Vencidas
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLimpiarFecha">
                        <i class="bi bi-x-lg me-1"></i>Limpiar
                    </button>
                </div>
                <div class="d-flex gap-1 ms-auto flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-acceso-rapido"
                            data-desde="<?= date('Y-m-d') ?>" data-hasta="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        Próx. 7 días
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-acceso-rapido"
                            data-desde="<?= date('Y-m-01') ?>" data-hasta="<?= date('Y-m-t') ?>">
                        Este mes
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-acceso-rapido"
                            data-desde="<?= date('Y-m-01', strtotime('first day of last month')) ?>"
                            data-hasta="<?= date('Y-m-t', strtotime('last day of last month')) ?>">
                        Mes anterior
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-0" id="misTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="asignadas-tab" data-bs-toggle="tab" data-bs-target="#asignadas" type="button">
                    <i class="bi bi-inbox me-1"></i> Asignadas a mí
                    <span class="badge bg-primary ms-1" id="badgeAsig"><?= count($asignadas) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="creadas-tab" data-bs-toggle="tab" data-bs-target="#creadas" type="button">
                    <i class="bi bi-pencil-square me-1"></i> Creadas por mí
                    <span class="badge bg-secondary ms-1" id="badgeCrea"><?= count($creadas) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="misTabsContent">

            <!-- ====== TAB: Asignadas ====== -->
            <div class="tab-pane fade show active" id="asignadas" role="tabpanel">

                <!-- Cards por Estado -->
                <div class="card shadow-sm border-0 rounded-0 border-bottom px-3 pt-3 pb-2">
                    <div class="row g-2 mb-2">
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="estado" data-valor="">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-dark text-white"><i class="bi bi-list-task"></i></div>
                                    <div><div class="stat-number"><?= $statsA['total'] ?></div><div class="stat-label">Total</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="estado" data-valor="pendiente">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-secondary text-white"><i class="bi bi-clock"></i></div>
                                    <div><div class="stat-number"><?= $statsA['pendiente'] ?></div><div class="stat-label">Pendientes</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="estado" data-valor="en_progreso">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-primary text-white"><i class="bi bi-play-circle"></i></div>
                                    <div><div class="stat-number"><?= $statsA['en_progreso'] ?></div><div class="stat-label">En Progreso</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="estado" data-valor="en_revision">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon text-white" style="background:#6f42c1"><i class="bi bi-eye"></i></div>
                                    <div><div class="stat-number" style="color:#6f42c1"><?= $statsA['en_revision'] ?></div><div class="stat-label">En Revisión</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="estado" data-valor="completada">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-success text-white"><i class="bi bi-check-circle"></i></div>
                                    <div><div class="stat-number text-success"><?= $statsA['completada'] ?></div><div class="stat-label">Completadas</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100 border-danger" data-tabla="asig" data-filtro="vencidas" data-valor="1">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-danger text-white"><i class="bi bi-exclamation-triangle"></i></div>
                                    <div><div class="stat-number text-danger"><?= $statsA['vencidas'] ?></div><div class="stat-label">Vencidas</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cards por Prioridad -->
                    <div class="row g-2 pb-1">
                        <div class="col-6 col-sm-3">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="prioridad" data-valor="urgente">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-danger text-white"><i class="bi bi-flag-fill"></i></div>
                                    <div><div class="stat-number text-danger"><?= $statsAP['urgente'] ?></div><div class="stat-label">Urgente</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="prioridad" data-valor="alta">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon text-white" style="background:#fd7e14"><i class="bi bi-flag-fill"></i></div>
                                    <div><div class="stat-number" style="color:#fd7e14"><?= $statsAP['alta'] ?></div><div class="stat-label">Alta</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="prioridad" data-valor="media">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon text-dark" style="background:#ffc107"><i class="bi bi-flag-fill"></i></div>
                                    <div><div class="stat-number" style="color:#ffc107"><?= $statsAP['media'] ?></div><div class="stat-label">Media</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card card shadow-sm h-100" data-tabla="asig" data-filtro="prioridad" data-valor="baja">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-success text-white"><i class="bi bi-flag-fill"></i></div>
                                    <div><div class="stat-number text-success"><?= $statsAP['baja'] ?></div><div class="stat-label">Baja</div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla asignadas -->
                <div class="card shadow-sm rounded-top-0">
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
                                            <?php
                                            $dias = $act['dias_restantes'] ?? 0;
                                            $esVencida = !empty($act['fecha_limite']) && $dias < 0 && !in_array($act['estado'], ['completada', 'cancelada']);
                                            $fechaIso = $act['fecha_limite'] ?? '';
                                            ?>
                                            <tr data-estado="<?= esc($act['estado']) ?>"
                                                data-prioridad="<?= esc($act['prioridad']) ?>"
                                                data-fecha="<?= esc($fechaIso) ?>"
                                                data-vencida="<?= $esVencida ? '1' : '0' ?>">
                                                <td><code><?= esc($act['codigo']) ?></code></td>
                                                <td><?= esc($act['titulo']) ?></td>
                                                <td>
                                                    <span class="badge badge-estado estado-<?= $act['estado'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $act['estado'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="prioridad-<?= $act['prioridad'] ?>">
                                                        <i class="bi bi-flag-fill me-1"></i><?= ucfirst($act['prioridad']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($act['fecha_limite']): ?>
                                                        <span class="<?= $esVencida ? 'fecha-vencida' : '' ?>">
                                                            <?= date('d/m/Y', strtotime($act['fecha_limite'])) ?>
                                                            <?php if ($esVencida): ?><i class="bi bi-exclamation-circle ms-1" title="Vencida"></i><?php endif; ?>
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

            <!-- ====== TAB: Creadas ====== -->
            <div class="tab-pane fade" id="creadas" role="tabpanel">

                <!-- Cards por Estado -->
                <div class="card shadow-sm border-0 rounded-0 border-bottom px-3 pt-3 pb-2">
                    <div class="row g-2 mb-2">
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="estado" data-valor="">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-dark text-white"><i class="bi bi-list-task"></i></div>
                                    <div><div class="stat-number"><?= $statsC['total'] ?></div><div class="stat-label">Total</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="estado" data-valor="pendiente">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-secondary text-white"><i class="bi bi-clock"></i></div>
                                    <div><div class="stat-number"><?= $statsC['pendiente'] ?></div><div class="stat-label">Pendientes</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="estado" data-valor="en_progreso">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-primary text-white"><i class="bi bi-play-circle"></i></div>
                                    <div><div class="stat-number"><?= $statsC['en_progreso'] ?></div><div class="stat-label">En Progreso</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="estado" data-valor="en_revision">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon text-white" style="background:#6f42c1"><i class="bi bi-eye"></i></div>
                                    <div><div class="stat-number" style="color:#6f42c1"><?= $statsC['en_revision'] ?></div><div class="stat-label">En Revisión</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="estado" data-valor="completada">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-success text-white"><i class="bi bi-check-circle"></i></div>
                                    <div><div class="stat-number text-success"><?= $statsC['completada'] ?></div><div class="stat-label">Completadas</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="stat-card card shadow-sm h-100 border-danger" data-tabla="crea" data-filtro="vencidas" data-valor="1">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-danger text-white"><i class="bi bi-exclamation-triangle"></i></div>
                                    <div><div class="stat-number text-danger"><?= $statsC['vencidas'] ?></div><div class="stat-label">Vencidas</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cards por Prioridad -->
                    <div class="row g-2 pb-1">
                        <div class="col-6 col-sm-3">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="prioridad" data-valor="urgente">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-danger text-white"><i class="bi bi-flag-fill"></i></div>
                                    <div><div class="stat-number text-danger"><?= $statsCP['urgente'] ?></div><div class="stat-label">Urgente</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="prioridad" data-valor="alta">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon text-white" style="background:#fd7e14"><i class="bi bi-flag-fill"></i></div>
                                    <div><div class="stat-number" style="color:#fd7e14"><?= $statsCP['alta'] ?></div><div class="stat-label">Alta</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="prioridad" data-valor="media">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon text-dark" style="background:#ffc107"><i class="bi bi-flag-fill"></i></div>
                                    <div><div class="stat-number" style="color:#ffc107"><?= $statsCP['media'] ?></div><div class="stat-label">Media</div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card card shadow-sm h-100" data-tabla="crea" data-filtro="prioridad" data-valor="baja">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-success text-white"><i class="bi bi-flag-fill"></i></div>
                                    <div><div class="stat-number text-success"><?= $statsCP['baja'] ?></div><div class="stat-label">Baja</div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla creadas -->
                <div class="card shadow-sm rounded-top-0">
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
                                            <?php
                                            $dias = $act['dias_restantes'] ?? 0;
                                            $esVencida = !empty($act['fecha_limite']) && $dias < 0 && !in_array($act['estado'], ['completada', 'cancelada']);
                                            $fechaIso = $act['fecha_limite'] ?? '';
                                            ?>
                                            <tr data-estado="<?= esc($act['estado']) ?>"
                                                data-prioridad="<?= esc($act['prioridad']) ?>"
                                                data-fecha="<?= esc($fechaIso) ?>"
                                                data-vencida="<?= $esVencida ? '1' : '0' ?>">
                                                <td><code><?= esc($act['codigo']) ?></code></td>
                                                <td><?= esc($act['titulo']) ?></td>
                                                <td>
                                                    <span class="badge badge-estado estado-<?= $act['estado'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $act['estado'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="prioridad-<?= $act['prioridad'] ?>">
                                                        <i class="bi bi-flag-fill me-1"></i><?= ucfirst($act['prioridad']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $act['nombre_asignado'] ? esc($act['nombre_asignado']) : '<span class="text-muted">Sin asignar</span>' ?>
                                                </td>
                                                <td>
                                                    <?php if ($act['fecha_limite']): ?>
                                                        <span class="<?= $esVencida ? 'fecha-vencida' : '' ?>">
                                                            <?= date('d/m/Y', strtotime($act['fecha_limite'])) ?>
                                                            <?php if ($esVencida): ?><i class="bi bi-exclamation-circle ms-1" title="Vencida"></i><?php endif; ?>
                                                        </span>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
    $(document).ready(function() {

        // ── Estado de filtros activos ─────────────────────────────────
        var filtros = {
            asig: { estado: '', prioridad: '', vencidas: false },
            crea: { estado: '', prioridad: '', vencidas: false }
        };
        var fechaDesde = '';
        var fechaHasta = '';

        // ── Custom DataTables search (aplica a ambas tablas) ─────────
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            var tableId = settings.nTable.id; // 'tablaAsignadas' o 'tablaCreadas'
            var clave = tableId === 'tablaAsignadas' ? 'asig' : 'crea';
            var f = filtros[clave];

            // Leer data-* del nodo TR
            var nTr = settings.aoData[dataIndex].nTr;
            if (!nTr) return true;
            var estado    = nTr.dataset.estado    || '';
            var prioridad = nTr.dataset.prioridad || '';
            var fecha     = nTr.dataset.fecha     || '';
            var vencida   = nTr.dataset.vencida   || '0';

            // Filtro estado
            if (f.estado && estado !== f.estado) return false;

            // Filtro prioridad
            if (f.prioridad && prioridad !== f.prioridad) return false;

            // Filtro vencidas (local o global desde botón header)
            if (f.vencidas && vencida !== '1') return false;

            // Filtro fecha desde / hasta
            if (fechaDesde || fechaHasta) {
                if (!fecha) return false; // sin fecha = excluir cuando hay rango
                if (fechaDesde && fecha < fechaDesde) return false;
                if (fechaHasta && fecha > fechaHasta) return false;
            }

            return true;
        });

        // ── Inicializar DataTables ────────────────────────────────────
        var tableAsig = $('#tablaAsignadas').DataTable({
            pageLength: 15,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            order: [[4, 'asc']]
        });

        var tableCrea = $('#tablaCreadas').DataTable({
            pageLength: 15,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            order: [[5, 'asc']]
        });

        // ── Helper: redibujar y actualizar badges ─────────────────────
        function redraw() {
            tableAsig.draw();
            tableCrea.draw();
            $('#badgeAsig').text(tableAsig.page.info().recordsDisplay);
            $('#badgeCrea').text(tableCrea.page.info().recordsDisplay);
        }

        // ── Clicks en stat cards ──────────────────────────────────────
        $(document).on('click', '.stat-card', function() {
            var clave    = $(this).data('tabla');   // 'asig' o 'crea'
            var tipoFilt = $(this).data('filtro');  // 'estado', 'prioridad', 'vencidas'
            var valor    = String($(this).data('valor') || '');

            // Toggle: si ya está activo, se desactiva
            if (tipoFilt === 'vencidas') {
                filtros[clave].vencidas = !filtros[clave].vencidas;
                filtros[clave].estado = '';
            } else if (tipoFilt === 'estado') {
                var esToggle = filtros[clave].estado === valor;
                filtros[clave].estado   = esToggle ? '' : valor;
                filtros[clave].vencidas = false;
            } else if (tipoFilt === 'prioridad') {
                var esToggleP = filtros[clave].prioridad === valor;
                filtros[clave].prioridad = esToggleP ? '' : valor;
            }

            // Actualizar estado visual de cards del mismo tab
            $('#' + (clave === 'asig' ? 'asignadas' : 'creadas') + ' .stat-card').each(function() {
                var $c = $(this);
                var tf = $c.data('filtro');
                var tv = String($c.data('valor') || '');
                var activo = false;
                if (tf === 'vencidas') activo = filtros[clave].vencidas;
                else if (tf === 'estado') activo = (filtros[clave].estado === tv && tv !== '');
                else if (tf === 'prioridad') activo = (filtros[clave].prioridad === tv);
                $c.toggleClass('active', activo);
            });

            redraw();
        });

        // ── Flatpickr ─────────────────────────────────────────────────
        var fpDesde = flatpickr('#inputFechaDesde', {
            locale: 'es', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y',
            allowInput: true,
            onChange: function(sel, dateStr) {
                fechaDesde = dateStr;
                redraw();
            }
        });

        var fpHasta = flatpickr('#inputFechaHasta', {
            locale: 'es', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y',
            allowInput: true,
            onChange: function(sel, dateStr) {
                fechaHasta = dateStr;
                redraw();
            }
        });

        // ── Botón Vencidas (header) ───────────────────────────────────
        $('#btnVencidas').on('click', function() {
            var tabActiva = $('#misTabs .nav-link.active').attr('id') === 'creadas-tab' ? 'crea' : 'asig';
            filtros[tabActiva].vencidas = !filtros[tabActiva].vencidas;
            if (filtros[tabActiva].vencidas) filtros[tabActiva].estado = '';

            // Sincronizar visual de cards en el tab activo
            $('#' + (tabActiva === 'asig' ? 'asignadas' : 'creadas') + ' .stat-card[data-filtro="vencidas"]')
                .toggleClass('active', filtros[tabActiva].vencidas);
            $('#' + (tabActiva === 'asig' ? 'asignadas' : 'creadas') + ' .stat-card[data-filtro="estado"]')
                .toggleClass('active', false);

            $(this).toggleClass('btn-warning btn-outline-warning', !filtros[tabActiva].vencidas)
                   .toggleClass('btn-warning', filtros[tabActiva].vencidas)
                   .toggleClass('btn-outline-warning', !filtros[tabActiva].vencidas);
            redraw();
        });

        // ── Botón Limpiar fecha ───────────────────────────────────────
        $('#btnLimpiarFecha').on('click', function() {
            fpDesde.clear();
            fpHasta.clear();
            fechaDesde = '';
            fechaHasta = '';
            redraw();
        });

        // ── Accesos rápidos de fecha ──────────────────────────────────
        $('.btn-acceso-rapido').on('click', function() {
            var desde = $(this).data('desde');
            var hasta = $(this).data('hasta');
            fpDesde.setDate(desde, true);
            fpHasta.setDate(hasta, true);
            fechaDesde = desde;
            fechaHasta = hasta;
            redraw();
        });

        // Guardar URL antes de navegar a una actividad
        $(document).on('click', 'a[href*="/ver/"]', function() {
            sessionStorage.setItem('actividadesTableroBack', window.location.href);
        });

    });
    </script>
</body>
</html>
