<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Indicadores – Kpi Cycloid</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
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
        <div class="mb-3">
            <a href="<?= base_url('trabajador/trabajadordashboard') ?>" class="btn btn-primary">
                <i class="bi bi-house-door me-1"></i>Dashboard
            </a>
        </div>

        <h1 class="h3 mb-4">Mis Indicadores – Periodo <?= esc($periodo) ?></h1>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php elseif (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

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
                                <td><?= esc($i['meta_descripcion']) ?></td>
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
    </div>

    <?= $this->include('partials/logout') ?>

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