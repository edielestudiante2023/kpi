<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Parte de Fórmula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- jQuery (requerido por Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</head>

<body>
    <div class="container mt-5">
        <h3 class="mb-4">✏️ Editar Parte de Fórmula</h3>

        <form action="<?= site_url('partesformula/editpost/' . $parte['id_parte_formula']) ?>" method="post">
            <div class="mb-3">
                <label for="id_indicador" class="form-label">Indicador</label>
                <select class="form-select" name="id_indicador" id="id_indicador" required>
                    <option value="">-- Selecciona un indicador --</option>
                    <?php foreach ($indicadores as $ind): ?>
                        <option value="<?= $ind['id_indicador'] ?>" <?= $parte['id_indicador'] == $ind['id_indicador'] ? 'selected' : '' ?>>
                            <?= esc($ind['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="mb-3">
                <label for="tipo_parte" class="form-label">Tipo de Parte</label>
                <select class="form-select" id="tipo_parte" name="tipo_parte" required>
                    <option value="">-- Selecciona --</option>
                    <option value="dato" <?= $parte['tipo_parte'] === 'dato' ? 'selected' : '' ?>>Dato</option>
                    <option value="operador" <?= $parte['tipo_parte'] === 'operador' ? 'selected' : '' ?>>Operador</option>
                    <option value="constante" <?= $parte['tipo_parte'] === 'constante' ? 'selected' : '' ?>>Constante</option>
                    <option value="paréntesis_apertura" <?= $parte['tipo_parte'] === 'paréntesis_apertura' ? 'selected' : '' ?>>( apertura</option>
                    <option value="paréntesis_cierre" <?= $parte['tipo_parte'] === 'paréntesis_cierre' ? 'selected' : '' ?>>) cierre</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="valor" class="form-label">Valor</label>
                <input type="text" class="form-control" id="valor" name="valor" value="<?= esc($parte['valor']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="orden" class="form-label">Orden en la Fórmula</label>
                <input type="number" class="form-control" id="orden" name="orden" value="<?= esc($parte['orden']) ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar Parte</button>
            <a href="<?= site_url('partesformula/list') ?>" class="btn btn-secondary ms-2">Cancelar</a>
        </form>
    </div>
</body>

<script>
    $(document).ready(function() {
        $('#id_indicador').select2({
            placeholder: "Busca y selecciona un indicador",
            allowClear: true,
            width: '100%'
        });
    });
</script>


</html>