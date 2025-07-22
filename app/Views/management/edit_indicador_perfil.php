<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Indicador Asignado – Afilogro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
      .select2-container .select2-selection--single {
        height: calc(1.5em + .75rem + 2px);
        padding: .375rem .75rem;
      }
      .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5em;
      }
    </style>
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <h1 class="h4 mb-4">Editar Asignación de Indicador</h1>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <p><?= esc($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('indicadores_perfil/edit/' . $registro['id_indicador_perfil']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="perfilSelect" class="form-label">Cargo / Perfil</label>
                <select name="id_perfil_cargo" id="perfilSelect" class="form-select" required>
                    <option value="">-- Selecciona un cargo --</option>
                    <?php foreach ($perfiles as $p): ?>
                        <option
                            value="<?= $p['id_perfil_cargo'] ?>"
                            data-area="<?= esc($p['area']) ?>"
                            <?= $p['id_perfil_cargo'] == $registro['id_perfil_cargo'] ? 'selected' : '' ?>
                        >
                            <?= esc($p['nombre_cargo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="areaSelect" class="form-label">Área</label>
                <select name="area" id="areaSelect" class="form-select" required>
                    <option value="">-- Selecciona un área --</option>
                    <?php foreach ($areas as $a): ?>
                        <option
                            value="<?= esc($a['nombre_area']) ?>"
                            <?= esc($a['nombre_area']) == $registro['area'] ? 'selected' : '' ?>
                        >
                            <?= esc($a['nombre_area']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="indicadorSelect" class="form-label">Indicador</label>
                <select name="id_indicador" id="indicadorSelect" class="form-select" required>
                    <option value="">-- Selecciona un indicador --</option>
                    <?php foreach ($indicadores as $ind): ?>
                        <option
                            value="<?= $ind['id_indicador'] ?>"
                            <?= $ind['id_indicador'] == $registro['id_indicador'] ? 'selected' : '' ?>
                        >
                            <?= esc($ind['nombre']) ?> — <?= esc($ind['unidad']) ?> (<?= esc($ind['tipo_meta']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="alert alert-info">
                <strong>Nota:</strong> El <em>valor meta</em>, la <em>unidad</em>, la <em>ponderación</em> y el <em>método de cálculo</em>
                ya están definidos en el indicador y no se deben modificar en esta asignación.
            </div>

            <div class="d-flex justify-content-start">
                <button type="submit" class="btn btn-success me-2">Actualizar</button>
                <a href="<?= base_url('indicadores_perfil') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#perfilSelect, #areaSelect, #indicadorSelect').select2({
            placeholder: 'Seleccione una opción',
            width: '100%'
        });

        $('#perfilSelect').on('change', function() {
            var area = $(this).find('option:selected').data('area') || '';
            $('#areaSelect').val(area).trigger('change');
        });
    });
    </script>
</body>
</html>
