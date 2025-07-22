<!-- app/Views/equipos/edit.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo – Afilogro</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Select2 Bootstrap 5 Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        /* Match Bootstrap form-control height */
        .select2-container .select2-selection--single {
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <h1 class="h3 mb-4">Editar Asignación de Equipo</h1>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session()->getFlashdata('errors') as $e): ?>
                    <p><?= esc($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('equipos/edit/' . $equipo['id_equipos']) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="id_jefe" class="form-label">Jefe</label>
                    <select id="id_jefe" name="id_jefe" class="form-select select2" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= esc($u['id_users']) ?>"
                                <?= set_select('id_jefe', $u['id_users'], $equipo['id_jefe'] == $u['id_users']) ?>>
                                <?= esc($u['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="id_subordinado" class="form-label">Subordinado</label>
                    <select id="id_subordinado" name="id_subordinado" class="form-select select2" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= esc($u['id_users']) ?>"
                                <?= set_select('id_subordinado', $u['id_users'], $equipo['id_subordinado'] == $u['id_users']) ?>>
                                <?= esc($u['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </form>
    </div>

    <!-- Scripts: jQuery, Bootstrap, Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Seleccione --',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</body>
</html>
