<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Indicador – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Crear Nuevo Indicador</h1>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#aiIndicadorModal">
                <i class="bi bi-stars me-1"></i>Crear con IA
            </button>
        </div>

        <?php if (session()->getFlashdata('errors')): ?>
            <?= view('components/alert', [
                'type' => 'danger',
                'message' => '<ul class="mb-0">' . implode('', array_map(fn($e) => '<li>' . esc($e) . '</li>', session()->getFlashdata('errors'))) . '</ul>',
                'dismissible' => true,
                'icon' => 'bi-exclamation-triangle'
            ]) ?>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <?= view('components/alert', ['type' => 'success', 'message' => session()->getFlashdata('success')]) ?>
        <?php endif; ?>

        <form id="form-add-indicador" action="<?= base_url('indicadores/add') ?>" method="post">
            <?= csrf_field() ?>

            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="nombre_indicador" class="form-control" value="<?= old('nombre') ?>" required>
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
                    id="meta"
                    class="form-control"
                    value="<?= old('meta_valor') ?>"
                    required
                >
            </div>

            <!-- Meta Descripción -->
            <div class="mb-3">
                <label class="form-label">Meta Descripción</label>
                <textarea name="meta_descripcion" id="descripcion" class="form-control" rows="2" required><?= old('meta_descripcion') ?></textarea>
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
                <input type="text" name="unidad" id="unidad_medida" class="form-control" value="<?= old('unidad') ?>" required>
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

            <!-- Botones de accion -->
            <div class="d-flex gap-2">
                <?= view('components/form_submit_button', [
                    'text' => 'Guardar y Disenar Formula',
                    'loadingText' => 'Guardando',
                    'icon' => 'bi-gear',
                    'class' => 'btn-success',
                    'formId' => 'form-add-indicador',
                    'name' => 'accion',
                    'value' => 'guardar_disenar'
                ]) ?>
                <?= view('components/form_submit_button', [
                    'text' => 'Solo Guardar',
                    'loadingText' => 'Guardando',
                    'icon' => 'bi-check-lg',
                    'class' => 'btn-primary',
                    'formId' => 'form-add-indicador',
                    'name' => 'accion',
                    'value' => 'guardar'
                ]) ?>
            </div>
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

    <!-- Modal IA para Indicadores -->
    <?= view('components/ai_generator_modal', ['id' => 'aiIndicadorModal', 'tipo' => 'indicador']) ?>
</body>

</html>
