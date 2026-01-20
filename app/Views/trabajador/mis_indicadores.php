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
        /* Opcional: para que la dropdown no expanda celdas */
        td .dropdown-toggle {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 200px;
        }

        td .dropdown-menu {
            max-width: 400px;
            white-space: normal;
        }
    </style>
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <?= view('components/back_to_dashboard') ?>
                <h1 class="h3 mb-0">Mis Indicadores – Periodo <?= esc($periodo) ?></h1>
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

        <form method="post"
            action="<?= base_url('trabajador/saveIndicadores') ?>">
            <?= csrf_field() ?>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="periodo" class="form-label">Fecha de corte:</label>
                    <input
                        type="date"
                        name="periodo"
                        id="periodo"
                        class="form-control"
                        value="<?= esc($periodo) ?>"
                        required>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <h3 style="color: #6f42c1;">
                        📅 Selecciona la fecha real de corte a la que corresponde el resultado que vas a registrar.
                    </h3>
                </div>
            </div>




            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>Indicador</th>
                            <th>Meta Valor</th>
                            <th>Meta Descripción</th>
                            <th>Tipo de Meta</th>
                            <th>Fórmula</th>
                            <th>Calcular</th>
                            <th>Unidad</th>
                            <th>Objetivo Proceso</th>
                            <th>Objetivo Calidad</th>
                            <th>Tipo Aplicación</th>
                            <th>Creado en</th>
                            <th>Periodicidad</th>
                            <th>Ponderación (%)</th>
                            <th>Resultado</th>
                            <th>Comentario</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i): ?>
                            <tr>
                                <td><strong><?= esc($i['nombre_indicador']) ?></strong></td>
                                <td><?= esc($i['meta_valor']) ?></td>
                                <td>
                                    <?php 
                                    $descripcion = esc($i['meta_descripcion']);
                                    $descripcionCorta = strlen($descripcion) > 50 ? substr($descripcion, 0, 50) . '...' : $descripcion;
                                    ?>
                                    <div class="dropdown">
                                        <button class="btn btn-link btn-sm text-start p-0 dropdown-toggle text-decoration-none" 
                                                type="button" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false"
                                                style="white-space: normal; text-align: left; width: 200px;">
                                            <?= $descripcionCorta ?>
                                        </button>
                                        <div class="dropdown-menu p-3" style="max-width: 400px; white-space: normal;">
                                            <h6 class="dropdown-header">Meta Descripción Completa:</h6>
                                            <p class="mb-0 small"><?= $descripcion ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?= esc($i['tipo_meta']) ?></td>

                                <!-- Fórmula estática -->
                                <td>
                                    <?php if (isset($formulas[$i['id_indicador']])): ?>
                                        <?php foreach ($formulas[$i['id_indicador']] as $parte): ?>
                                            <?php if ($parte['tipo_parte'] === 'dato'): ?>
                                                <span class="text-primary"><?= esc($parte['valor']) ?></span>
                                            <?php else: ?>
                                                <span><?= esc($parte['valor']) ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <code><?= esc($i['metodo_calculo']) ?></code>
                                    <?php endif; ?>
                                </td>


                                <!-- Botón para diligenciar fórmula -->
                                <td class="text-center">
                                    <a href="<?= base_url('trabajador/formula/' . $i['id_indicador']) ?>"
                                        class="btn btn-outline-secondary btn-sm">
                                        Diligenciar
                                    </a>
                                </td>

                                <td><?= esc($i['unidad']) ?></td>
                                <td class="small text-muted">
                                    <?= esc($i['objetivo_proceso']) ?>
                                </td>
                                <td class="small text-muted">
                                    <?= esc($i['objetivo_calidad']) ?>
                                </td>
                                <td><?= esc($i['tipo_aplicacion']) ?></td>
                                <td><?= esc($i['created_at']) ?></td>
                                <td><?= esc($i['periodicidad']) ?></td>
                                <td><?= esc($i['ponderacion']) ?>%</td>

                                <!-- 1) Campo Resultado: vacío -->
                                <td>
                                    <input
                                        type="text"
                                        name="resultado_real[<?= $i['id_indicador_perfil'] ?>]"
                                        class="form-control resultado-input"
                                        data-ip="<?= $i['id_indicador_perfil'] ?>"
                                        placeholder="Ingresa valor" />
                                </td>

                                <!-- 2) Campo Comentario -->
                                <td>
                                    <textarea
                                        name="comentario[<?= $i['id_indicador_perfil'] ?>]"
                                        class="form-control comentario-input"
                                        rows="1"
                                        placeholder="Opcional..."></textarea>
                                </td>

                                <!-- 3) Botón de Guardar, oculto inicialmente -->
                                <td class="text-center">
                                    <button
                                        type="submit"
                                        class="btn btn-success btn-sm save-btn"
                                        data-ip="<?= $i['id_indicador_perfil'] ?>"
                                        style="display:none;">
                                        Guardar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS Bundle y jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Mostrar el botón Guardar solo si el trabajador escribe un resultado
            $('.resultado-input').on('input', function() {
                var val = $(this).val().trim();
                var ip = $(this).data('ip');
                var btn = $('.save-btn[data-ip="' + ip + '"]');

                if (val !== '' && val !== '0') {
                    btn.show();
                } else {
                    btn.hide();
                }
            });
        });
    </script>
</body>

</html>