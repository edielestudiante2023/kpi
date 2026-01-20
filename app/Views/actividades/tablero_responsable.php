<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero por Responsable - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        .responsable-container {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .responsable-column {
            min-width: 320px;
            max-width: 360px;
            flex-shrink: 0;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }
        .responsable-header {
            padding: 0.75rem 1rem;
            border-radius: 8px 8px 0 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .responsable-header.sin-asignar {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        .responsable-body {
            padding: 0.5rem;
            flex: 1;
            min-height: 400px;
            max-height: calc(100vh - 300px);
            overflow-y: auto;
        }
        .actividad-card {
            background: white;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #dee2e6;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .actividad-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .actividad-card.prioridad-urgente { border-left-color: #dc3545; }
        .actividad-card.prioridad-alta { border-left-color: #fd7e14; }
        .actividad-card.prioridad-media { border-left-color: #ffc107; }
        .actividad-card.prioridad-baja { border-left-color: #198754; }

        .card-codigo {
            font-size: 0.7rem;
            color: #6c757d;
            font-family: monospace;
        }
        .card-titulo {
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0.25rem 0;
        }
        .card-meta {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .badge-estado {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
        }
        .estado-pendiente { background-color: #6c757d; }
        .estado-en_progreso { background-color: #0d6efd; }
        .estado-en_revision { background-color: #6f42c1; }
        .estado-completada { background-color: #198754; }
        .estado-cancelada { background-color: #dc3545; }

        .avatar-lg {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .stats-mini {
            display: flex;
            gap: 0.5rem;
            font-size: 0.7rem;
            opacity: 0.9;
        }
        .stats-mini span {
            background: rgba(255,255,255,0.2);
            padding: 0.1rem 0.4rem;
            border-radius: 3px;
        }

        .fecha-vencida { color: #dc3545; font-weight: 600; }
        .fecha-proxima { color: #fd7e14; }

        .progress-mini {
            height: 4px;
            margin-top: 0.5rem;
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
                <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>Tablero por Responsable</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-kanban me-1"></i> Por Estado
                </a>
                <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-2">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="pendiente" <?= ($filtros['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="en_progreso" <?= ($filtros['estado'] ?? '') === 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                            <option value="en_revision" <?= ($filtros['estado'] ?? '') === 'en_revision' ? 'selected' : '' ?>>En Revision</option>
                            <option value="completada" <?= ($filtros['estado'] ?? '') === 'completada' ? 'selected' : '' ?>>Completada</option>
                        </select>
                    </div>
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
                    <div class="col-md-3">
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
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="<?= base_url('actividades/responsable') ?>" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="bi bi-x-lg me-1"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tablero por Responsable -->
        <div class="responsable-container">
            <?php foreach ($porResponsable as $resp): ?>
                <?php
                $actividades = $resp['actividades'];
                $pendientes = count(array_filter($actividades, fn($a) => $a['estado'] === 'pendiente'));
                $enProgreso = count(array_filter($actividades, fn($a) => $a['estado'] === 'en_progreso'));
                $completadas = count(array_filter($actividades, fn($a) => $a['estado'] === 'completada'));
                ?>
                <div class="responsable-column">
                    <div class="responsable-header <?= $resp['id_usuario'] == 0 ? 'sin-asignar' : '' ?>">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-lg">
                                <?= $resp['id_usuario'] == 0 ? '?' : strtoupper(substr($resp['nombre'], 0, 2)) ?>
                            </span>
                            <div>
                                <div class="fw-semibold"><?= esc($resp['nombre']) ?></div>
                                <div class="stats-mini">
                                    <span title="Pendientes"><?= $pendientes ?> pend</span>
                                    <span title="En progreso"><?= $enProgreso ?> prog</span>
                                    <span title="Completadas"><?= $completadas ?> comp</span>
                                </div>
                            </div>
                        </div>
                        <span class="badge bg-light text-dark"><?= count($actividades) ?></span>
                    </div>
                    <div class="responsable-body">
                        <?php foreach ($actividades as $act): ?>
                            <div class="actividad-card prioridad-<?= $act['prioridad'] ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="card-codigo"><?= esc($act['codigo']) ?></div>
                                    <span class="badge badge-estado estado-<?= $act['estado'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $act['estado'])) ?>
                                    </span>
                                </div>

                                <div class="card-titulo"><?= esc($act['titulo']) ?></div>

                                <?php if ($act['nombre_categoria']): ?>
                                    <span class="badge" style="background-color: <?= $act['color_categoria'] ?>; font-size: 0.65rem;">
                                        <?= esc($act['nombre_categoria']) ?>
                                    </span>
                                <?php endif; ?>

                                <div class="card-meta mt-2">
                                    <?php if ($act['fecha_limite']): ?>
                                        <?php
                                        $dias = $act['dias_restantes'];
                                        $claseVencimiento = '';
                                        if ($dias < 0 && !in_array($act['estado'], ['completada', 'cancelada'])) {
                                            $claseVencimiento = 'fecha-vencida';
                                        } elseif ($dias <= 2 && $dias >= 0) {
                                            $claseVencimiento = 'fecha-proxima';
                                        }
                                        ?>
                                        <span class="<?= $claseVencimiento ?>">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= date('d/m/Y', strtotime($act['fecha_limite'])) ?>
                                            <?php if ($dias < 0 && !in_array($act['estado'], ['completada', 'cancelada'])): ?>
                                                (vencida)
                                            <?php elseif ($dias == 0): ?>
                                                (hoy)
                                            <?php elseif ($dias == 1): ?>
                                                (manana)
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($act['total_comentarios'] > 0): ?>
                                        <span class="ms-2">
                                            <i class="bi bi-chat-dots"></i> <?= $act['total_comentarios'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($act['porcentaje_avance'] > 0): ?>
                                    <div class="progress progress-mini">
                                        <div class="progress-bar bg-success" style="width: <?= $act['porcentaje_avance'] ?>%"></div>
                                    </div>
                                <?php endif; ?>

                                <a href="<?= base_url('actividades/ver/' . $act['id_actividad']) ?>"
                                   class="stretched-link"></a>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($actividades)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Sin actividades
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($porResponsable)): ?>
                <div class="text-center text-muted py-5 w-100">
                    <i class="bi bi-kanban fs-1 d-block mb-3"></i>
                    <h5>No hay actividades</h5>
                    <p>Crea una nueva actividad para comenzar</p>
                    <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
