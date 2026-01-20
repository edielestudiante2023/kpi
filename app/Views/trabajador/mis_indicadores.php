<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Indicadores – Kpi Cycloid</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .indicador-card {
            transition: all 0.2s ease;
            border-left: 4px solid #0d6efd;
        }
        .indicador-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .indicador-header {
            cursor: pointer;
        }
        .indicador-header:hover {
            background-color: #f8f9fa;
        }
        .meta-badge {
            font-size: 0.85rem;
        }
        .formula-display {
            font-family: 'Consolas', 'Monaco', monospace;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .formula-display .variable {
            color: #0d6efd;
            font-weight: 600;
        }
        .info-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-weight: 500;
        }
        .collapse-icon {
            transition: transform 0.2s ease;
        }
        .collapsed .collapse-icon {
            transform: rotate(-90deg);
        }
        .input-result {
            max-width: 150px;
        }
        .detail-section {
            background: #fafbfc;
            border-radius: 8px;
            padding: 1rem;
        }
    </style>
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <?= view('components/back_to_dashboard') ?>
                <h1 class="h3 mb-0">Mis Indicadores</h1>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <?= view('components/alert', ['type' => 'success', 'message' => session()->getFlashdata('success')]) ?>
        <?php elseif (session()->getFlashdata('error')): ?>
            <?= view('components/alert', ['type' => 'danger', 'message' => session()->getFlashdata('error')]) ?>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?= view('components/empty_state', [
                        'icon' => 'bi-bar-chart-line',
                        'title' => 'Sin indicadores asignados',
                        'message' => 'No tienes indicadores asignados a tu perfil de cargo actual.',
                        'actionUrl' => base_url('trabajador/trabajadordashboard'),
                        'actionText' => 'Volver al Dashboard',
                        'actionIcon' => 'bi-house-door',
                        'actionClass' => 'btn-secondary'
                    ]) ?>
                </div>
            </div>
        <?php else: ?>

        <!-- Fecha de corte -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label for="periodo" class="form-label fw-semibold">
                            <i class="bi bi-calendar-event me-1"></i>Fecha de corte
                        </label>
                        <input type="date" name="periodo_global" id="periodo" class="form-control"
                               value="<?= esc($periodo) ?>">
                    </div>
                    <div class="col-md-8">
                        <div class="alert alert-info mb-0 py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Selecciona la fecha real de corte a la que corresponde el resultado que vas a registrar.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de indicadores -->
        <div class="accordion" id="indicadoresAccordion">
            <?php foreach ($items as $index => $i): ?>
            <div class="card indicador-card shadow-sm mb-3">
                <!-- Header del indicador -->
                <div class="card-header bg-white indicador-header p-0" id="heading<?= $index ?>">
                    <div class="d-flex align-items-center justify-content-between p-3"
                         data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>"
                         aria-expanded="false" aria-controls="collapse<?= $index ?>">
                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                            <i class="bi bi-chevron-down collapse-icon text-muted"></i>
                            <div>
                                <h6 class="mb-1 fw-bold"><?= esc($i['nombre_indicador']) ?></h6>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-primary meta-badge">
                                        Meta: <?= esc($i['meta_valor']) ?> <?= esc($i['unidad']) ?>
                                    </span>
                                    <span class="badge bg-secondary meta-badge">
                                        <?= esc($i['periodicidad']) ?>
                                    </span>
                                    <span class="badge bg-info meta-badge">
                                        <?= esc($i['ponderacion']) ?>%
                                    </span>
                                    <span class="badge bg-outline-dark border meta-badge text-dark">
                                        <?= ucfirst(str_replace('_', ' ', $i['tipo_meta'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?= base_url('trabajador/formula/' . $i['id_indicador']) ?>"
                               class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation();">
                                <i class="bi bi-calculator me-1"></i>Calcular
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contenido expandible -->
                <div id="collapse<?= $index ?>" class="collapse" aria-labelledby="heading<?= $index ?>"
                     data-bs-parent="#indicadoresAccordion">
                    <div class="card-body border-top">
                        <div class="row">
                            <!-- Columna izquierda: Detalles -->
                            <div class="col-lg-7">
                                <div class="detail-section mb-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="info-label">Meta Descripcion</div>
                                            <div class="info-value"><?= esc($i['meta_descripcion']) ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Objetivo de Proceso</div>
                                            <div class="info-value"><?= esc($i['objetivo_proceso']) ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Objetivo de Calidad</div>
                                            <div class="info-value"><?= esc($i['objetivo_calidad']) ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Tipo de Aplicacion</div>
                                            <div class="info-value"><?= ucfirst($i['tipo_aplicacion']) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formula -->
                                <?php if (isset($formulas[$i['id_indicador']]) && !empty($formulas[$i['id_indicador']])): ?>
                                <div class="mb-3">
                                    <div class="info-label mb-1">Formula</div>
                                    <div class="formula-display">
                                        <?php foreach ($formulas[$i['id_indicador']] as $parte): ?>
                                            <?php if ($parte['tipo_parte'] === 'dato'): ?>
                                                <span class="variable"><?= esc($parte['valor']) ?></span>
                                            <?php else: ?>
                                                <span><?= esc($parte['valor']) ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="mb-3">
                                    <div class="info-label mb-1">Metodo de Calculo</div>
                                    <div class="formula-display">
                                        <code><?= esc($i['metodo_calculo']) ?></code>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Columna derecha: Formulario de registro -->
                            <div class="col-lg-5">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">
                                            <i class="bi bi-pencil-square me-1"></i>Registrar Resultado
                                        </h6>
                                        <form method="post" action="<?= base_url('trabajador/saveIndicadores') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="periodo" class="periodo-input" value="<?= esc($periodo) ?>">

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Resultado</label>
                                                <div class="input-group">
                                                    <input type="text"
                                                           name="resultado_real[<?= $i['id_indicador_perfil'] ?>]"
                                                           class="form-control"
                                                           placeholder="Ingresa el valor">
                                                    <span class="input-group-text"><?= esc($i['unidad']) ?></span>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Comentario (opcional)</label>
                                                <textarea name="comentario[<?= $i['id_indicador_perfil'] ?>]"
                                                          class="form-control" rows="2"
                                                          placeholder="Observaciones..."></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="bi bi-check-lg me-1"></i>Guardar Resultado
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS Bundle y jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Sincronizar fecha de corte global con todos los formularios
            $('#periodo').on('change', function() {
                var fecha = $(this).val();
                $('.periodo-input').val(fecha);
            });

            // Rotar icono al expandir/colapsar
            $('.indicador-header').on('click', function() {
                $(this).toggleClass('collapsed');
            });

            // Inicializar estado de iconos
            $('.indicador-header').addClass('collapsed');
        });
    </script>
</body>

</html>
