<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero de Actividades - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        /* Cards de resumen */
        .stat-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .stat-card.active {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.5);
        }
        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Kanban */
        .kanban-container {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .kanban-column {
            flex: 1;
            min-width: 200px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }
        .kanban-header {
            padding: 0.75rem 1rem;
            border-radius: 8px 8px 0 0;
            color: white;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .kanban-header .badge {
            background: rgba(255,255,255,0.3);
        }
        .kanban-body {
            padding: 0.5rem;
            flex: 1;
            min-height: 400px;
            max-height: calc(100vh - 450px);
            overflow-y: auto;
        }
        .kanban-card {
            background: white;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: grab;
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #dee2e6;
            position: relative;
        }
        .kanban-card::before {
            content: '\F3FE';
            font-family: 'bootstrap-icons';
            position: absolute;
            right: 8px;
            top: 8px;
            color: #dee2e6;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .kanban-card:hover::before {
            color: #6c757d;
        }
        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .kanban-card.dragging {
            opacity: 0.5;
            cursor: grabbing;
        }
        .tip-arrastrar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .kanban-card.prioridad-urgente { border-left-color: #dc3545; }
        .kanban-card.prioridad-alta { border-left-color: #fd7e14; }
        .kanban-card.prioridad-media { border-left-color: #ffc107; }
        .kanban-card.prioridad-baja { border-left-color: #198754; }

        .card-codigo {
            font-size: 0.7rem;
            color: #6c757d;
            font-family: monospace;
        }
        .card-titulo {
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0.25rem 0;
            color: #212529;
        }
        .card-meta {
            font-size: 0.75rem;
            color: #6c757d;
        }
        .card-footer-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }
        .avatar-sm {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-prioridad {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }
        .fecha-vencida { color: #dc3545; font-weight: 600; }
        .fecha-proxima { color: #fd7e14; }

        /* Colores de columnas */
        .col-pendiente .kanban-header { background: #6c757d; }
        .col-en_progreso .kanban-header { background: #0d6efd; }
        .col-en_revision .kanban-header { background: #6f42c1; }
        .col-completada .kanban-header { background: #198754; }
        .col-cancelada .kanban-header { background: #dc3545; }

        .drop-zone-active {
            background: #e3f2fd !important;
            border: 2px dashed #0d6efd;
        }
        /* Filtros colapsables */
        .filtros-avanzados {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .filtros-avanzados.show {
            max-height: 200px;
        }
        /* Responsable cards */
        .responsable-card {
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            background: #f8f9fa;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .responsable-card:hover {
            background: #e9ecef;
        }
        .responsable-card.active {
            background: #0d6efd;
            color: white;
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <?= view('components/back_to_dashboard') ?>
                <div>
                    <h1 class="h3 mb-0"><i class="bi bi-kanban me-2"></i>Tablero de Actividades</h1>
                    <div class="d-flex align-items-center gap-3 mt-1">
                        <span class="tip-arrastrar">
                            <i class="bi bi-hand-index-thumb"></i>
                            Arrastra las tarjetas para cambiar estado
                        </span>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('actividades/dashboard') ?>" class="btn btn-outline-info">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
                <a href="<?= base_url('actividades/responsable') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-people me-1"></i> Por Responsable
                </a>
                <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
                </a>
            </div>
        </div>

        <!-- Cards de Resumen (clickeables) -->
        <div class="row g-3 mb-4">
            <!-- Total -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= base_url('actividades/tablero') ?>" class="stat-card card shadow-sm h-100 d-block <?= empty($filtros['estado']) && empty($filtros['vencidas']) ? 'active' : '' ?>">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-dark text-white me-2">
                                <i class="bi bi-list-task"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= $resumen['total'] ?></div>
                                <div class="stat-label">Total</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Pendientes -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= base_url('actividades/tablero?estado=pendiente') ?>" class="stat-card card shadow-sm h-100 d-block <?= ($filtros['estado'] ?? '') === 'pendiente' ? 'active' : '' ?>">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-secondary text-white me-2">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= $resumen['por_estado']['pendiente'] ?></div>
                                <div class="stat-label">Pendientes</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- En Progreso -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= base_url('actividades/tablero?estado=en_progreso') ?>" class="stat-card card shadow-sm h-100 d-block <?= ($filtros['estado'] ?? '') === 'en_progreso' ? 'active' : '' ?>">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary text-white me-2">
                                <i class="bi bi-play-circle"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= $resumen['por_estado']['en_progreso'] ?></div>
                                <div class="stat-label">En Progreso</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Completadas -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= base_url('actividades/tablero?estado=completada') ?>" class="stat-card card shadow-sm h-100 d-block <?= ($filtros['estado'] ?? '') === 'completada' ? 'active' : '' ?>">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success text-white me-2">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= $resumen['por_estado']['completada'] ?></div>
                                <div class="stat-label">Completadas</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Vencidas -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= base_url('actividades/tablero?vencidas=1') ?>" class="stat-card card shadow-sm h-100 d-block border-danger <?= ($filtros['vencidas'] ?? '') ? 'active' : '' ?>">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger text-white me-2">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div>
                                <div class="stat-number text-danger"><?= $resumen['vencidas'] ?></div>
                                <div class="stat-label">Vencidas</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Proximas a vencer -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= base_url('actividades/tablero?proximas=1') ?>" class="stat-card card shadow-sm h-100 d-block border-warning">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning text-dark me-2">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="stat-number text-warning"><?= $resumen['proximas_vencer'] ?></div>
                                <div class="stat-label">Por vencer</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-2">
                <form method="get" id="formFiltros">
                    <!-- Fila principal de filtros -->
                    <div class="row g-2 align-items-end">
                        <!-- Busqueda -->
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Buscar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="busqueda" class="form-control"
                                       placeholder="Codigo o titulo..."
                                       value="<?= esc($filtros['busqueda'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Responsable -->
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Responsable</label>
                            <select name="responsable" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= $u['id_users'] ?>" <?= ($filtros['id_asignado'] ?? '') == $u['id_users'] ? 'selected' : '' ?>>
                                        <?= esc($u['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Prioridad -->
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Prioridad</label>
                            <select name="prioridad" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                <option value="urgente" <?= ($filtros['prioridad'] ?? '') === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                                <option value="alta" <?= ($filtros['prioridad'] ?? '') === 'alta' ? 'selected' : '' ?>>Alta</option>
                                <option value="media" <?= ($filtros['prioridad'] ?? '') === 'media' ? 'selected' : '' ?>>Media</option>
                                <option value="baja" <?= ($filtros['prioridad'] ?? '') === 'baja' ? 'selected' : '' ?>>Baja</option>
                            </select>
                        </div>
                        <!-- Categoria -->
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Categoria</label>
                            <select name="categoria" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['id_categoria'] ?>" <?= ($filtros['id_categoria'] ?? '') == $c['id_categoria'] ? 'selected' : '' ?>>
                                        <?= esc($c['nombre_categoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Botones -->
                        <div class="col-md-3">
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnFiltrosAvanzados">
                                    <i class="bi bi-sliders me-1"></i> Fechas
                                </button>
                                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                                    <i class="bi bi-funnel me-1"></i> Filtrar
                                </button>
                                <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros avanzados (fechas) -->
                    <div class="filtros-avanzados mt-3 <?= (!empty($filtros['fecha_limite_desde']) || !empty($filtros['fecha_limite_hasta'])) ? 'show' : '' ?>" id="filtrosAvanzados">
                        <div class="row g-2 pt-2 border-top">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha limite desde</label>
                                <input type="text" name="fecha_desde" class="form-control form-control-sm datepicker"
                                       value="<?= esc($filtros['fecha_limite_desde'] ?? '') ?>"
                                       placeholder="Seleccionar...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha limite hasta</label>
                                <input type="text" name="fecha_hasta" class="form-control form-control-sm datepicker"
                                       value="<?= esc($filtros['fecha_limite_hasta'] ?? '') ?>"
                                       placeholder="Seleccionar...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Accesos rapidos</label>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="<?= base_url('actividades/tablero?vencidas=1') ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Vencidas
                                    </a>
                                    <a href="<?= base_url('actividades/tablero?fecha_desde=' . date('Y-m-d') . '&fecha_hasta=' . date('Y-m-d', strtotime('+7 days'))) ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-clock-history me-1"></i>Proximos 7 dias
                                    </a>
                                    <a href="<?= base_url('actividades/tablero?fecha_desde=' . date('Y-m-01') . '&fecha_hasta=' . date('Y-m-t')) ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-calendar-month me-1"></i>Este mes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campos ocultos para mantener filtros de cards -->
                    <?php if (!empty($filtros['estado'])): ?>
                        <input type="hidden" name="estado" value="<?= esc($filtros['estado']) ?>">
                    <?php endif; ?>
                    <?php if (!empty($filtros['vencidas'])): ?>
                        <input type="hidden" name="vencidas" value="1">
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Filtros activos -->
        <?php
        $hayFiltrosActivos = !empty($filtros['busqueda']) || !empty($filtros['id_asignado']) ||
                            !empty($filtros['prioridad']) || !empty($filtros['id_categoria']) ||
                            !empty($filtros['fecha_limite_desde']) || !empty($filtros['fecha_limite_hasta']) ||
                            !empty($filtros['estado']) || !empty($filtros['vencidas']);
        ?>
        <?php if ($hayFiltrosActivos): ?>
            <div class="mb-3">
                <span class="text-muted small me-2">Filtros activos:</span>
                <?php if (!empty($filtros['busqueda'])): ?>
                    <span class="badge bg-secondary me-1">Busqueda: <?= esc($filtros['busqueda']) ?></span>
                <?php endif; ?>
                <?php if (!empty($filtros['estado'])): ?>
                    <span class="badge bg-primary me-1">Estado: <?= ucfirst(str_replace('_', ' ', $filtros['estado'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($filtros['vencidas'])): ?>
                    <span class="badge bg-danger me-1">Solo vencidas</span>
                <?php endif; ?>
                <?php if (!empty($filtros['id_asignado'])): ?>
                    <?php
                    $nombreResp = '';
                    foreach ($usuarios as $u) {
                        if ($u['id_users'] == $filtros['id_asignado']) {
                            $nombreResp = $u['nombre_completo'];
                            break;
                        }
                    }
                    ?>
                    <span class="badge bg-info me-1">Responsable: <?= esc($nombreResp) ?></span>
                <?php endif; ?>
                <?php if (!empty($filtros['fecha_limite_desde']) || !empty($filtros['fecha_limite_hasta'])): ?>
                    <span class="badge bg-warning text-dark me-1">
                        Fecha: <?= $filtros['fecha_limite_desde'] ?? '...' ?> - <?= $filtros['fecha_limite_hasta'] ?? '...' ?>
                    </span>
                <?php endif; ?>
                <a href="<?= base_url('actividades/tablero') ?>" class="badge bg-dark text-decoration-none">
                    <i class="bi bi-x"></i> Limpiar todo
                </a>
            </div>
        <?php endif; ?>

        <!-- Alertas -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tablero Kanban -->
        <div class="kanban-container">
            <?php
            $columnas = [
                'pendiente'   => ['nombre' => 'Pendiente', 'icono' => 'bi-clock'],
                'en_progreso' => ['nombre' => 'En Progreso', 'icono' => 'bi-play-circle'],
                'en_revision' => ['nombre' => 'En Revision', 'icono' => 'bi-eye'],
                'completada'  => ['nombre' => 'Completada', 'icono' => 'bi-check-circle'],
                'cancelada'   => ['nombre' => 'Cancelada', 'icono' => 'bi-x-circle']
            ];

            // Si hay filtro de estado, solo mostrar esa columna expandida
            $estadoFiltrado = $filtros['estado'] ?? '';
            ?>

            <?php foreach ($columnas as $estado => $config): ?>
                <?php
                // Contar vencidas en esta columna
                $vencidasEnColumna = 0;
                foreach ($tablero[$estado] ?? [] as $act) {
                    if (isset($act['dias_restantes']) && $act['dias_restantes'] < 0 && !in_array($estado, ['completada', 'cancelada'])) {
                        $vencidasEnColumna++;
                    }
                }
                ?>
                <div class="kanban-column col-<?= $estado ?>" data-estado="<?= $estado ?>">
                    <div class="kanban-header">
                        <span>
                            <i class="bi <?= $config['icono'] ?> me-2"></i><?= $config['nombre'] ?>
                            <?php if ($vencidasEnColumna > 0): ?>
                                <span class="badge bg-danger ms-1" title="<?= $vencidasEnColumna ?> vencidas">
                                    <i class="bi bi-exclamation-triangle"></i> <?= $vencidasEnColumna ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span class="badge"><?= count($tablero[$estado] ?? []) ?></span>
                    </div>
                    <div class="kanban-body" data-estado="<?= $estado ?>">
                        <?php foreach ($tablero[$estado] ?? [] as $act): ?>
                            <?php
                            $esVencida = isset($act['dias_restantes']) && $act['dias_restantes'] < 0 && !in_array($estado, ['completada', 'cancelada']);
                            ?>
                            <div class="kanban-card prioridad-<?= $act['prioridad'] ?> <?= $esVencida ? 'border-danger' : '' ?>"
                                 draggable="true"
                                 data-id="<?= $act['id_actividad'] ?>">
                                <div class="card-codigo"><?= esc($act['codigo']) ?></div>
                                <div class="card-titulo"><?= esc($act['titulo']) ?></div>

                                <?php if ($act['nombre_categoria']): ?>
                                    <span class="badge" style="background-color: <?= $act['color_categoria'] ?>; font-size: 0.65rem;">
                                        <?= esc($act['nombre_categoria']) ?>
                                    </span>
                                <?php endif; ?>

                                <div class="card-footer-custom">
                                    <div>
                                        <?php if ($act['nombre_asignado']): ?>
                                            <span class="avatar-sm" title="<?= esc($act['nombre_asignado']) ?>">
                                                <?= strtoupper(substr($act['nombre_asignado'], 0, 2)) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="avatar-sm bg-secondary" title="Sin asignar">?</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-meta">
                                        <?php if ($act['fecha_limite']): ?>
                                            <?php
                                            $dias = $act['dias_restantes'];
                                            $claseVencimiento = '';
                                            if ($dias < 0) $claseVencimiento = 'fecha-vencida';
                                            elseif ($dias <= 2) $claseVencimiento = 'fecha-proxima';
                                            ?>
                                            <span class="<?= $claseVencimiento ?>">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= date('d/m', strtotime($act['fecha_limite'])) ?>
                                                <?php if ($dias < 0 && !in_array($estado, ['completada', 'cancelada'])): ?>
                                                    <i class="bi bi-exclamation-circle" title="Vencida hace <?= abs($dias) ?> dias"></i>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($act['total_comentarios'] > 0): ?>
                                            <span class="ms-2">
                                                <i class="bi bi-chat-dots"></i> <?= $act['total_comentarios'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <a href="<?= base_url('actividades/ver/' . $act['id_actividad']) ?>"
                                   class="stretched-link"></a>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($tablero[$estado])): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                <small>Sin actividades</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Resumen por Responsable (colapsable) -->
        <div class="mt-4">
            <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#resumenResponsables">
                <i class="bi bi-people me-1"></i> Ver resumen por responsable
            </button>
            <div class="collapse" id="resumenResponsables">
                <div class="card card-body">
                    <div class="row g-2">
                        <?php
                        $responsablesTop = array_slice($resumen['por_responsable'], 0, 8, true);
                        foreach ($responsablesTop as $resp):
                            if ($resp['total'] == 0) continue;
                        ?>
                            <div class="col-6 col-md-3 col-lg-2">
                                <a href="<?= base_url('actividades/tablero?responsable=' . $resp['id']) ?>"
                                   class="responsable-card d-block <?= ($filtros['id_asignado'] ?? '') == $resp['id'] ? 'active' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-truncate small"><?= esc($resp['nombre']) ?></span>
                                        <span class="badge bg-primary"><?= $resp['activas'] ?></span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toastNotificacion" class="toast" role="alert" data-bs-autohide="true" data-bs-delay="20000">
            <div class="toast-header">
                <i class="bi bi-info-circle me-2" id="toastIcono"></i>
                <strong class="me-auto" id="toastTitulo">Notificacion</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMensaje"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        // Inicializar datepickers
        flatpickr('.datepicker', {
            locale: 'es',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true
        });

        // Toggle filtros avanzados
        document.getElementById('btnFiltrosAvanzados').addEventListener('click', function() {
            document.getElementById('filtrosAvanzados').classList.toggle('show');
        });

        // Toast helper
        function mostrarToast(titulo, mensaje, tipo = 'info') {
            const toast = document.getElementById('toastNotificacion');
            const toastTitulo = document.getElementById('toastTitulo');
            const toastMensaje = document.getElementById('toastMensaje');
            const toastIcono = document.getElementById('toastIcono');

            toastTitulo.textContent = titulo;
            toastMensaje.innerHTML = mensaje;

            toast.classList.remove('border-success', 'border-danger', 'border-warning', 'border-info');
            toastIcono.className = 'bi me-2';

            if (tipo === 'success') {
                toast.classList.add('border-success');
                toastIcono.classList.add('bi-check-circle-fill', 'text-success');
            } else if (tipo === 'error') {
                toast.classList.add('border-danger');
                toastIcono.classList.add('bi-x-circle-fill', 'text-danger');
            } else if (tipo === 'warning') {
                toast.classList.add('border-warning');
                toastIcono.classList.add('bi-exclamation-triangle-fill', 'text-warning');
            } else {
                toast.classList.add('border-info');
                toastIcono.classList.add('bi-info-circle-fill', 'text-info');
            }

            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }

        // Drag and Drop
        const cards = document.querySelectorAll('.kanban-card');
        const columns = document.querySelectorAll('.kanban-body');
        const csrfName = '<?= csrf_token() ?>';
        const csrfHash = '<?= csrf_hash() ?>';

        const nombresEstado = {
            'pendiente': 'Pendiente',
            'en_progreso': 'En Progreso',
            'en_revision': 'En Revision',
            'completada': 'Completada',
            'cancelada': 'Cancelada'
        };

        cards.forEach(card => {
            card.addEventListener('dragstart', () => {
                card.classList.add('dragging');
            });

            card.addEventListener('dragend', () => {
                card.classList.remove('dragging');
            });
        });

        columns.forEach(column => {
            column.addEventListener('dragover', e => {
                e.preventDefault();
                column.classList.add('drop-zone-active');
            });

            column.addEventListener('dragleave', () => {
                column.classList.remove('drop-zone-active');
            });

            column.addEventListener('drop', e => {
                e.preventDefault();
                column.classList.remove('drop-zone-active');

                const card = document.querySelector('.dragging');
                if (!card) return;

                const nuevoEstado = column.dataset.estado;
                const idActividad = card.dataset.id;
                const tituloActividad = card.querySelector('.card-titulo')?.textContent || 'Actividad';

                column.appendChild(card);

                $.ajax({
                    url: '<?= base_url('actividades/cambiar-estado') ?>',
                    method: 'POST',
                    data: {
                        id_actividad: idActividad,
                        estado: nuevoEstado,
                        [csrfName]: csrfHash
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (!data.success) {
                            mostrarToast(
                                'Error',
                                'No se pudo cambiar el estado: ' + (data.message || 'Error desconocido'),
                                'error'
                            );
                            setTimeout(() => location.reload(), 3000);
                        } else {
                            updateCounters();
                            mostrarToast(
                                'Estado actualizado',
                                `<strong>${tituloActividad}</strong> se movio a <strong>${nombresEstado[nuevoEstado]}</strong>.`,
                                'success'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', status, error, xhr.responseText);
                        mostrarToast(
                            'Error de conexion',
                            'No se pudo conectar con el servidor.<br><small>' + error + '</small>',
                            'error'
                        );
                        setTimeout(() => location.reload(), 3000);
                    }
                });
            });
        });

        function updateCounters() {
            document.querySelectorAll('.kanban-column').forEach(col => {
                const count = col.querySelectorAll('.kanban-card').length;
                col.querySelector('.kanban-header .badge:last-child').textContent = count;
            });
        }

        // Mostrar tip inicial (solo una vez)
        if (!localStorage.getItem('kanban_tip_shown')) {
            setTimeout(() => {
                mostrarToast(
                    'Tip: Tablero Kanban',
                    '<i class="bi bi-hand-index-thumb me-1"></i> Puedes <strong>arrastrar las tarjetas</strong> de una columna a otra para cambiar el estado rapidamente.',
                    'info'
                );
                localStorage.setItem('kanban_tip_shown', 'true');
            }, 1500);
        }
    </script>
</body>
</html>
