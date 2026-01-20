<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial de Mis Indicadores – Jefatura</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        .resultado-card {
            transition: all 0.2s ease;
            border-left: 4px solid #6f42c1;
        }
        .resultado-card.cumple-si {
            border-left-color: #198754;
        }
        .resultado-card.cumple-no {
            border-left-color: #dc3545;
        }
        .resultado-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .resultado-header {
            cursor: pointer;
        }
        .resultado-header:hover {
            background-color: #f8f9fa;
        }
        .meta-badge {
            font-size: 0.8rem;
        }
        .formula-display {
            font-family: 'Consolas', 'Monaco', monospace;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .formula-display .variable {
            color: #6f42c1;
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
        .detail-section {
            background: #fafbfc;
            border-radius: 8px;
            padding: 1rem;
        }
        .resultado-grande {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .filter-card {
            background: linear-gradient(135deg, #6f42c1 0%, #9c27b0 100%);
            color: white;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border: none;
        }
    </style>
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <?= view('components/back_to_dashboard') ?>
                <h1 class="h3 mb-0">Historial de Mis Indicadores</h1>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <?= view('components/alert', ['type' => 'success', 'message' => session()->getFlashdata('success')]) ?>
        <?php endif; ?>

        <?php if (empty($historial)): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?= view('components/empty_state', [
                        'icon' => 'bi-clock-history',
                        'title' => 'Sin historial de resultados',
                        'message' => 'Aun no has registrado ningun resultado de indicadores como jefatura.',
                        'actionUrl' => base_url('jefatura/misindicadorescomojefe'),
                        'actionText' => 'Registrar Resultados',
                        'actionIcon' => 'bi-bar-chart-line'
                    ]) ?>
                </div>
            </div>
        <?php else: ?>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4 filter-card">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-funnel me-1"></i>Filtrar por Indicador
                        </label>
                        <select id="filtroIndicador" class="form-select">
                            <option value="">Todos los indicadores</option>
                            <?php
                            $indicadores = array_unique(array_column($historial, 'nombre_indicador'));
                            foreach ($indicadores as $ind): ?>
                                <option value="<?= esc($ind) ?>"><?= esc($ind) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-check-circle me-1"></i>Cumplimiento
                        </label>
                        <select id="filtroCumple" class="form-select">
                            <option value="">Todos</option>
                            <option value="1">Cumple</option>
                            <option value="0">No Cumple</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-calendar me-1"></i>Periodo
                        </label>
                        <select id="filtroPeriodo" class="form-select">
                            <option value="">Todos los periodos</option>
                            <?php
                            $periodos = array_unique(array_column($historial, 'periodo'));
                            rsort($periodos);
                            foreach ($periodos as $per): ?>
                                <option value="<?= esc($per) ?>"><?= esc($per) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="limpiarFiltros" class="btn btn-outline-light w-100">
                            <i class="bi bi-x-circle me-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h2 class="mb-0 text-primary" id="totalResultados"><?= count($historial) ?></h2>
                        <small class="text-muted">Resultados Registrados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success bg-opacity-10 border-0">
                    <div class="card-body text-center">
                        <h2 class="mb-0 text-success" id="totalCumple">
                            <?= count(array_filter($historial, fn($r) => $r['cumple'] === '1')) ?>
                        </h2>
                        <small class="text-muted">Cumplen Meta</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger bg-opacity-10 border-0">
                    <div class="card-body text-center">
                        <h2 class="mb-0 text-danger" id="totalNoCumple">
                            <?= count(array_filter($historial, fn($r) => $r['cumple'] === '0')) ?>
                        </h2>
                        <small class="text-muted">No Cumplen</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de resultados -->
        <div class="accordion" id="historialAccordion">
            <?php foreach ($historial as $index => $r):
                $cumpleClass = $r['cumple'] === '1' ? 'cumple-si' : ($r['cumple'] === '0' ? 'cumple-no' : '');
            ?>
            <div class="card resultado-card shadow-sm mb-3 <?= $cumpleClass ?>"
                 data-indicador="<?= esc($r['nombre_indicador']) ?>"
                 data-cumple="<?= esc($r['cumple']) ?>"
                 data-periodo="<?= esc($r['periodo']) ?>">
                <!-- Header -->
                <div class="card-header bg-white resultado-header p-0" id="heading<?= $index ?>">
                    <div class="d-flex align-items-center justify-content-between p-3"
                         data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>"
                         aria-expanded="false" aria-controls="collapse<?= $index ?>">
                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                            <i class="bi bi-chevron-down collapse-icon text-muted"></i>
                            <div>
                                <h6 class="mb-1 fw-bold"><?= esc($r['nombre_indicador']) ?></h6>
                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                    <span class="badge meta-badge" style="background-color: #6f42c1;">
                                        Meta: <?= esc($r['meta_valor']) ?> <?= esc($r['unidad']) ?>
                                    </span>
                                    <span class="badge bg-secondary meta-badge">
                                        <?= esc($r['periodicidad']) ?>
                                    </span>
                                    <span class="badge bg-info meta-badge">
                                        <?= esc($r['ponderacion']) ?>%
                                    </span>
                                    <span class="text-muted small">
                                        <i class="bi bi-calendar3 me-1"></i><?= esc($r['periodo']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="resultado-grande <?= $r['cumple'] === '1' ? 'text-success' : ($r['cumple'] === '0' ? 'text-danger' : 'text-secondary') ?>">
                                    <?= esc($r['resultado_real']) ?>
                                </div>
                                <small class="text-muted"><?= esc($r['unidad']) ?></small>
                            </div>
                            <?php if ($r['cumple'] === '1'): ?>
                                <span class="badge bg-success fs-6 px-3 py-2">
                                    <i class="bi bi-check-circle me-1"></i>Cumple
                                </span>
                            <?php elseif ($r['cumple'] === '0'): ?>
                                <span class="badge bg-danger fs-6 px-3 py-2">
                                    <i class="bi bi-x-circle me-1"></i>No Cumple
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary fs-6 px-3 py-2">
                                    <i class="bi bi-dash-circle me-1"></i>Sin Evaluar
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contenido expandible -->
                <div id="collapse<?= $index ?>" class="collapse" aria-labelledby="heading<?= $index ?>"
                     data-bs-parent="#historialAccordion">
                    <div class="card-body border-top">
                        <div class="row">
                            <!-- Detalles del indicador -->
                            <div class="col-lg-8">
                                <div class="detail-section mb-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="info-label">Meta Descripcion</div>
                                            <div class="info-value"><?= esc($r['meta_texto']) ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Tipo de Meta</div>
                                            <div class="info-value"><?= ucfirst(str_replace('_', ' ', $r['tipo_meta'])) ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Objetivo de Proceso</div>
                                            <div class="info-value"><?= esc($r['objetivo_proceso']) ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Objetivo de Calidad</div>
                                            <div class="info-value"><?= esc($r['objetivo_calidad']) ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-label">Tipo de Aplicacion</div>
                                            <div class="info-value"><?= ucfirst($r['tipo_aplicacion']) ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-label">Creado en</div>
                                            <div class="info-value"><?= esc($r['creado_en']) ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-label">Fecha de Registro</div>
                                            <div class="info-value"><?= esc($r['fecha_registro']) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formula -->
                                <div class="mb-3">
                                    <div class="info-label mb-1">Formula / Calculo</div>
                                    <div class="formula-display">
                                        <?php
                                        $orig = $formulasHist[$r['id_indicador']] ?? [];
                                        if (!empty($orig)):
                                            echo '<div class="mb-2"><small class="text-muted">Formula:</small> <code>';
                                            echo esc(implode('', array_column($orig, 'valor')));
                                            echo '</code></div>';
                                        else:
                                            echo '<div class="mb-2"><small class="text-muted">Metodo:</small> <code>' . esc($r['metodo_calculo']) . '</code></div>';
                                        endif;

                                        $json = json_decode($r['valores_json'], true);
                                        $parts = $formulasHist[$r['id_indicador']] ?? [];
                                        if (isset($json['formula_partes']) && $parts):
                                            echo '<div><small class="text-muted">Valores usados:</small> ';
                                            foreach ($parts as $p):
                                                if ($p['tipo_parte'] === 'dato'):
                                                    echo '<span class="variable">' . esc($json['formula_partes'][$p['valor']] ?? '') . '</span>';
                                                else:
                                                    echo '<span>' . esc($p['valor']) . '</span>';
                                                endif;
                                            endforeach;
                                            echo '</div>';
                                        else:
                                            echo '<div><em class="text-muted small">Dato ingresado directamente</em></div>';
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumen del resultado -->
                            <div class="col-lg-4">
                                <div class="card <?= $r['cumple'] === '1' ? 'bg-success' : ($r['cumple'] === '0' ? 'bg-danger' : 'bg-secondary') ?> bg-opacity-10 border-0 h-100">
                                    <div class="card-body text-center d-flex flex-column justify-content-center">
                                        <div class="info-label">Resultado Obtenido</div>
                                        <div class="display-4 fw-bold <?= $r['cumple'] === '1' ? 'text-success' : ($r['cumple'] === '0' ? 'text-danger' : 'text-secondary') ?>">
                                            <?= esc($r['resultado_real']) ?>
                                        </div>
                                        <div class="text-muted mb-3"><?= esc($r['unidad']) ?></div>

                                        <hr>

                                        <div class="info-label">Meta</div>
                                        <div class="h4 text-dark"><?= esc($r['meta_valor']) ?> <?= esc($r['unidad']) ?></div>

                                        <?php if (!empty($r['comentario'])): ?>
                                        <hr>
                                        <div class="info-label">Comentario</div>
                                        <div class="info-value small"><?= esc($r['comentario']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Mensaje cuando no hay resultados filtrados -->
        <div id="noResultados" class="alert alert-info d-none">
            <i class="bi bi-info-circle me-2"></i>No hay resultados que coincidan con los filtros seleccionados.
        </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS Bundle y jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Rotar icono al expandir/colapsar
            $('.resultado-header').on('click', function() {
                $(this).toggleClass('collapsed');
            });

            // Inicializar estado de iconos
            $('.resultado-header').addClass('collapsed');

            // Filtros
            function aplicarFiltros() {
                var indicador = $('#filtroIndicador').val();
                var cumple = $('#filtroCumple').val();
                var periodo = $('#filtroPeriodo').val();
                var visibles = 0;

                $('.resultado-card').each(function() {
                    var card = $(this);
                    var mostrar = true;

                    if (indicador && card.data('indicador') !== indicador) {
                        mostrar = false;
                    }
                    if (cumple !== '' && card.data('cumple') != cumple) {
                        mostrar = false;
                    }
                    if (periodo && card.data('periodo') !== periodo) {
                        mostrar = false;
                    }

                    if (mostrar) {
                        card.removeClass('d-none');
                        visibles++;
                    } else {
                        card.addClass('d-none');
                    }
                });

                // Mostrar mensaje si no hay resultados
                if (visibles === 0) {
                    $('#noResultados').removeClass('d-none');
                } else {
                    $('#noResultados').addClass('d-none');
                }

                // Actualizar contadores
                $('#totalResultados').text(visibles);
            }

            $('#filtroIndicador, #filtroCumple, #filtroPeriodo').on('change', aplicarFiltros);

            $('#limpiarFiltros').on('click', function() {
                $('#filtroIndicador, #filtroCumple, #filtroPeriodo').val('');
                aplicarFiltros();
                $('#totalResultados').text($('.resultado-card').length);
            });
        });
    </script>
</body>

</html>
