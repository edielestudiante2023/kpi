<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Indicador – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <h1 class="h3 mb-4">Crear Nuevo Indicador</h1>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session()->getFlashdata('errors') as $e): ?>
                    <p><?= esc($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('indicadores/add') ?>" method="post">
            <?= csrf_field() ?>

            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= old('nombre') ?>" required>
            </div>

            <!-- Periodicidad -->
            <div class="mb-3">
                <label class="form-label">Periodicidad</label>
                <input type="text" name="periodicidad" class="form-control" value="<?= old('periodicidad') ?>" required>
            </div>

            <!-- Ponderación -->
            <div class="mb-3">
                <label class="form-label">Ponderación (%)</label>
                <input type="number" name="ponderacion" class="form-control" min="0" max="100" value="<?= old('ponderacion') ?>" required>
            </div>

            <!-- Tipo de Meta -->
            <div class="mb-3">
                <label class="form-label">Tipo de Meta</label>
                <select id="tipoMetaSelect" name="tipo_meta" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    <option value="mayor_igual" <?= old('tipo_meta') == 'mayor_igual' ? 'selected' : '' ?>>
                        Cumple si el resultado es mayor o igual a la meta
                    </option>
                    <option value="menor_igual" <?= old('tipo_meta') == 'menor_igual' ? 'selected' : '' ?>>
                        Cumple si el resultado es menor o igual a la meta
                    </option>
                    <option value="igual" <?= old('tipo_meta') == 'igual' ? 'selected' : '' ?>>
                        Cumple si el resultado es igual a la meta
                    </option>
                    <option value="comparativa" <?= old('tipo_meta') == 'comparativa' ? 'selected' : '' ?>>
                        Comparativa (la meta es el valor del periodo anterior)
                    </option>
                </select>
                <small class="form-text text-muted">
                    El tipo de meta define cómo se evalúa si el resultado cumple. La opción "Comparativa" usará el resultado del periodo anterior como referencia.
                </small>
            </div>

            <!-- Meta Valor -->
            <div class="mb-3" id="metaValorContainer">
                <label class="form-label">Meta Valor</label>
                <input
                    type="number"
                    step="any"
                    name="meta_valor"
                    id="metaValorInput"
                    class="form-control"
                    value="<?= old('meta_valor') ?>"
                    required
                >
            </div>

            <!-- Meta Descripción -->
            <div class="mb-3">
                <label class="form-label">Meta Descripción</label>
                <textarea name="meta_descripcion" class="form-control" rows="2" required><?= old('meta_descripcion') ?></textarea>
            </div>

            <!-- Método de Cálculo -->
            <div class="mb-3">
                <label class="form-label">Método de Cálculo</label>
                <select name="metodo_calculo" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    <option value="formula" <?= old('metodo_calculo') == 'formula' ? 'selected' : '' ?>>Fórmula</option>
                    <option value="manual" <?= old('metodo_calculo') == 'manual' ? 'selected' : '' ?>>Manual</option>
                    <option value="semiautomatico" <?= old('metodo_calculo') == 'semiautomatico' ? 'selected' : '' ?>>Semiautomático</option>
                </select>
            </div>

            <!-- Unidad -->
            <div class="mb-3">
                <label class="form-label">Unidad</label>
                <input type="text" name="unidad" class="form-control" value="<?= old('unidad') ?>" required>
            </div>

            <!-- Objetivo de Proceso -->
            <div class="mb-3">
                <label class="form-label">Objetivo de Proceso</label>
                <textarea name="objetivo_proceso" class="form-control" rows="3" required><?= old('objetivo_proceso') ?></textarea>
            </div>

            <!-- Objetivo de Calidad -->
            <div class="mb-3">
                <label class="form-label">Objetivo de Calidad</label>
                <input type="text" name="objetivo_calidad" class="form-control" value="<?= old('objetivo_calidad') ?>" required>
            </div>

            <!-- Tipo de Aplicación -->
            <div class="mb-3">
                <label class="form-label">Aplica a</label>
                <select name="tipo_aplicacion" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    <option value="cargo" <?= old('tipo_aplicacion') == 'cargo' ? 'selected' : '' ?>>Cargo</option>
                    <option value="area" <?= old('tipo_aplicacion') == 'area' ? 'selected' : '' ?>>Área</option>
                </select>
            </div>

            <!-- Activo -->
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" id="activoSwitch" name="activo" value="1" <?= old('activo', '1') ? 'checked' : '' ?>>
                <label class="form-check-label" for="activoSwitch">Activo</label>
            </div>

            <!-- Botón Guardar y Diseñar -->
            <button type="submit" name="accion" value="guardar_disenar" class="btn btn-success">
                <i class="bi bi-gear me-1"></i> Guardar y Diseñar Fórmula
            </button>
        </form>
    </div>

    <!-- Script para ocultar / resetear Meta Valor -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoMeta = document.getElementById('tipoMetaSelect');
        const contMetaValor = document.getElementById('metaValorContainer');
        const inputMetaValor = document.getElementById('metaValorInput');

        function actualizarMetaValor() {
            if (tipoMeta.value === 'comparativa') {
                contMetaValor.style.display = 'none';
                inputMetaValor.value = 0;
            } else {
                contMetaValor.style.display = 'block';
                // Si quieres restaurar el valor antiguo al desmarcar, descomenta:
                // inputMetaValor.value = '<?= old('meta_valor', '') ?>';
            }
        }

        tipoMeta.addEventListener('change', actualizarMetaValor);
        actualizarMetaValor(); // estado inicial
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
