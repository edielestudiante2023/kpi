<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Completar Usuario – Kpi Cycloid</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .75rem + 2px);
        }
    </style>
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Completar datos del usuario</h1>
            <a href="<?= base_url('users') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al listado
            </a>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="<?= base_url("users/completar/{$user['id_users']}") ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="row">
                        <!-- Perfil de Cargo -->
                        <div class="col-md-6 mb-3">
                            <label for="id_perfil_cargo" class="form-label">Perfil de Cargo</label>
                            <select id="id_perfil_cargo" name="id_perfil_cargo" class="form-select select2" data-placeholder="Seleccione un perfil de cargo" required>
                                <option value=""></option>
                                <?php foreach ($perfiles_cargo as $p): ?>
                                    <option value="<?= esc($p['id_perfil_cargo']) ?>"
                                        <?= $user['id_perfil_cargo'] == $p['id_perfil_cargo'] ? 'selected' : '' ?>>
                                        <?= esc($p['nombre_cargo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Jefe Inmediato -->
                        <div class="col-md-6 mb-3">
                            <label for="id_jefe" class="form-label">Jefe Inmediato</label>
                            <select id="id_jefe" name="id_jefe" class="form-select select2" data-placeholder="Seleccione un jefe inmediato">
                                <option value=""></option>
                                <?php foreach ($jefes as $j): ?>
                                    <option value="<?= esc($j['id_users']) ?>"
                                        <?= $user['id_jefe'] == $j['id_users'] ? 'selected' : '' ?>>
                                        <?= esc($j['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Guardar y finalizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</body>

</html>