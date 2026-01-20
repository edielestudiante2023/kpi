<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Actividad - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        .form-card {
            max-width: 800px;
            margin: 0 auto;
        }
        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 5px;
            border-color: #dee2e6;
        }
        .prioridad-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .prioridad-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .prioridad-dot.urgente { background: #dc3545; }
        .prioridad-dot.alta { background: #fd7e14; }
        .prioridad-dot.media { background: #ffc107; }
        .prioridad-dot.baja { background: #198754; }

        /* ========== RESPONSIVE MOBILE ========== */
        @media (max-width: 768px) {
            .form-card {
                max-width: 100%;
            }

            /* Header más compacto */
            .d-flex.justify-content-between.align-items-center.mb-4 {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
            .d-flex.justify-content-between.align-items-center.mb-4 > .d-flex.gap-2:last-child {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
            }
            .d-flex.justify-content-between.align-items-center.mb-4 > .d-flex.gap-2:last-child .btn {
                flex: 1;
                min-width: 120px;
                font-size: 0.85rem;
            }

            /* Título de página más pequeño */
            h1.h3 {
                font-size: 1.25rem;
            }

            /* Formulario: campos de 2 columnas pasan a 1 columna */
            .card-body .row .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            /* Botones del formulario */
            .d-flex.justify-content-end.gap-2 {
                flex-direction: column;
            }
            .d-flex.justify-content-end.gap-2 .btn {
                width: 100%;
            }

            /* Badge de usuario */
            .text-end.mb-2 .badge {
                font-size: 0.8rem !important;
            }

            /* Textarea más pequeño */
            textarea.form-control {
                min-height: 80px;
            }

            /* Select2 ajustes */
            .select2-container {
                width: 100% !important;
            }
        }

        /* Pantallas muy pequeñas (< 480px) */
        @media (max-width: 480px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            .card-body {
                padding: 1rem;
            }
            .form-label {
                font-size: 0.9rem;
            }
            .form-control, .form-select {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <div class="form-card">
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
                    <h1 class="h3 mb-0"><i class="bi bi-plus-circle me-2"></i>Nueva Actividad</h1>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#aiActividadModal">
                        <i class="bi bi-stars me-1"></i>Crear con IA
                    </button>
                    <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach (session()->getFlashdata('errors') as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= base_url('actividades/nueva') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="row">
                            <!-- Titulo -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Titulo <span class="text-danger">*</span></label>
                                <input type="text" name="titulo" id="titulo" class="form-control"
                                       value="<?= old('titulo') ?>"
                                       placeholder="Describe brevemente la actividad"
                                       required>
                            </div>

                            <!-- Descripcion -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Descripcion</label>
                                <textarea name="descripcion" id="descripcion" class="form-control" rows="3"
                                          placeholder="Detalla la actividad, requisitos o instrucciones adicionales"><?= old('descripcion') ?></textarea>
                            </div>

                            <!-- Categoria y Area -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoria</label>
                                <select name="id_categoria" class="form-select select2">
                                    <option value="">-- Sin categoria --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id_categoria'] ?>"
                                                <?= old('id_categoria') == $cat['id_categoria'] ? 'selected' : '' ?>>
                                            <?= esc($cat['nombre_categoria']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Area</label>
                                <select name="id_area" class="form-select select2">
                                    <option value="">-- Sin area --</option>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?= $area['id_areas'] ?>"
                                                <?= old('id_area') == $area['id_areas'] ? 'selected' : '' ?>>
                                            <?= esc($area['nombre_area']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Responsable -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Asignar a</label>
                                <select name="id_usuario_asignado" class="form-select select2">
                                    <option value="">-- Sin asignar --</option>
                                    <?php foreach ($usuarios as $user): ?>
                                        <option value="<?= $user['id_users'] ?>"
                                                <?= old('id_usuario_asignado') == $user['id_users'] ? 'selected' : '' ?>>
                                            <?= esc($user['nombre_completo']) ?>
                                            <?php if (!empty($user['cargo'])): ?>
                                                (<?= esc($user['cargo']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Prioridad -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prioridad <span class="text-danger">*</span></label>
                                <select name="prioridad" id="prioridad" class="form-select" required>
                                    <option value="baja" <?= old('prioridad', 'media') === 'baja' ? 'selected' : '' ?>>
                                        Baja
                                    </option>
                                    <option value="media" <?= old('prioridad', 'media') === 'media' ? 'selected' : '' ?>>
                                        Media
                                    </option>
                                    <option value="alta" <?= old('prioridad') === 'alta' ? 'selected' : '' ?>>
                                        Alta
                                    </option>
                                    <option value="urgente" <?= old('prioridad') === 'urgente' ? 'selected' : '' ?>>
                                        Urgente
                                    </option>
                                </select>
                            </div>

                            <!-- Fecha limite -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha limite</label>
                                <input type="text" name="fecha_limite" id="fecha_limite" class="form-control datepicker"
                                       value="<?= old('fecha_limite') ?>"
                                       placeholder="Selecciona una fecha">
                            </div>

                            <!-- Observaciones -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2"
                                          placeholder="Notas adicionales (opcional)"><?= old('observaciones') ?></textarea>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Crear Actividad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'default',
                allowClear: true,
                width: '100%'
            });

            flatpickr('.datepicker', {
                locale: 'es',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                minDate: 'today',
                allowInput: true
            });
        });
    </script>

    <!-- Modal IA para Actividades -->
    <?= view('components/ai_generator_modal', ['id' => 'aiActividadModal', 'tipo' => 'actividad']) ?>
</body>
</html>
