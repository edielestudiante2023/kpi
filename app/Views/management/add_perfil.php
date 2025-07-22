<!-- app/Views/management/add_perfil.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Perfil – Afilogro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <h1 class="h3 mb-4">Crear Nuevo Perfil</h1>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session()->getFlashdata('errors') as $e): ?>
                    <p><?= esc($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('perfiles/add') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Nombre del Cargo</label>
                <input type="text" name="nombre_cargo" class="form-control" value="<?= old('nombre_cargo') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Área</label>
                <select name="area" class="form-select select2" required>
                    <option value="">Seleccione un área</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= esc($a['nombre_area']) ?>" <?= old('area') == $a['nombre_area'] ? 'selected' : '' ?>>
                            <?= esc($a['nombre_area']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
            $cargosUnicos = array_unique(array_column($jefes, 'cargo'));
            sort($cargosUnicos); // opcional: orden alfabético
            ?>
            <div class="mb-3">
                <label class="form-label">Cargo de Jefe Inmediato</label>
                <select name="jefe_inmediato" class="form-select select2" required>
                    <option value="">Seleccione Cargo del Jefe</option>
                    <?php foreach ($cargosUnicos as $cargo): ?>
                        <option value="<?= esc($cargo) ?>" <?= old('jefe_inmediato') == $cargo ? 'selected' : '' ?>>
                            <?= esc($cargo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>



            <div class="mb-3">
                <label class="form-label">Colaboradores a Cargo</label>
                <textarea name="colaboradores_a_cargo" class="form-control"><?= old('colaboradores_a_cargo') ?></textarea>
            </div>

            <button type="submit" class="btn btn-success">Guardar Perfil</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: 'Seleccione una opción',
                allowClear: true
            });
        });
    </script>
</body>

</html>