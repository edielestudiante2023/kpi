<!-- app/Views/management/edit_indicador.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Indicador – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <h1 class="h3 mb-4">Editar Indicador</h1>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session()->getFlashdata('errors') as $e): ?>
                    <p><?= esc($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('indicadores/edit/' . $indicador['id_indicador']) ?>" method="post">
            <?= csrf_field() ?>

            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control"
                    value="<?= old('nombre', esc($indicador['nombre'])) ?>" required>
            </div>

            <!-- Periodicidad -->
            <div class="mb-3">
                <label class="form-label">Periodicidad</label>
                <input type="text" name="periodicidad" class="form-control"
                    value="<?= old('periodicidad', esc($indicador['periodicidad'])) ?>" required>
            </div>

            <!-- Ponderación (%) -->
            <div class="mb-3">
                <label class="form-label">Ponderación (%)</label>
                <input type="number" name="ponderacion" class="form-control" min="0" max="100"
                    value="<?= old('ponderacion', esc($indicador['ponderacion'])) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo de Meta</label>
                <select name="tipo_meta" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    <option value="mayor_igual" <?= old('tipo_meta', $indicador['tipo_meta']) == 'mayor_igual' ? 'selected' : '' ?>>
                        Cumple si el resultado es mayor o igual a la meta
                    </option>
                    <option value="menor_igual" <?= old('tipo_meta', $indicador['tipo_meta']) == 'menor_igual' ? 'selected' : '' ?>>
                        Cumple si el resultado es menor o igual a la meta
                    </option>
                    <option value="igual" <?= old('tipo_meta', $indicador['tipo_meta']) == 'igual' ? 'selected' : '' ?>>
                        Cumple si el resultado es igual a la meta
                    </option>
                    <option value="comparativa" <?= old('tipo_meta', $indicador['tipo_meta']) == 'comparativa' ? 'selected' : '' ?>>
                        Comparativa (la meta es el valor del periodo anterior)
                    </option>
                </select>

            </div>

            <!-- Meta Valor -->
            <div class="mb-3">
                <label class="form-label">Meta Valor</label>
                <input type="text" name="meta_valor" class="form-control"
                    value="<?= old('meta_valor', esc($indicador['meta_valor'] ?? '')) ?>" required>
            </div>

            <!-- Meta Descripción -->
            <div class="mb-3">
                <label class="form-label">Meta Descripción</label>
                <textarea name="meta_descripcion" class="form-control" rows="2" required><?= old('meta_descripcion', esc($indicador['meta_descripcion'] ?? '')) ?></textarea>
            </div>

            <!-- Tipo de Meta -->
            

            <!-- Método de Cálculo -->
            <div class="mb-3">
                <label class="form-label">Método de Cálculo</label>
                <select name="metodo_calculo" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    <option value="formula" <?= old('metodo_calculo', $indicador['metodo_calculo']) == 'formula' ? 'selected' : '' ?>>Fórmula</option>
                    <option value="manual" <?= old('metodo_calculo', $indicador['metodo_calculo']) == 'manual' ? 'selected' : '' ?>>Manual</option>
                    <option value="semiautomatico" <?= old('metodo_calculo', $indicador['metodo_calculo']) == 'semiautomatico' ? 'selected' : '' ?>>Semiautomático</option>
                </select>
            </div>

            <!-- Unidad -->
            <div class="mb-3">
                <label class="form-label">Unidad</label>
                <input type="text" name="unidad" class="form-control"
                    value="<?= old('unidad', esc($indicador['unidad'])) ?>" required>
            </div>

            <!-- Objetivo de Proceso -->
            <div class="mb-3">
                <label class="form-label">Objetivo de Proceso</label>
                <textarea name="objetivo_proceso" class="form-control" rows="3" required><?= old('objetivo_proceso', esc($indicador['objetivo_proceso'])) ?></textarea>
            </div>

            <!-- Objetivo de Calidad -->
            <div class="mb-3">
                <label class="form-label">Objetivo de Calidad que Impacta</label>
                <input type="text" name="objetivo_calidad" class="form-control"
                    value="<?= old('objetivo_calidad', esc($indicador['objetivo_calidad'])) ?>" required>
            </div>

            <!-- Tipo de Aplicación -->
            <div class="mb-3">
                <label class="form-label">Aplica a</label>
                <select name="tipo_aplicacion" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    <option value="cargo" <?= old('tipo_aplicacion', $indicador['tipo_aplicacion']) == 'cargo' ? 'selected' : '' ?>>Cargo</option>
                    <option value="area" <?= old('tipo_aplicacion', $indicador['tipo_aplicacion']) == 'area' ? 'selected' : '' ?>>Área</option>
                </select>
            </div>

            <!-- Botones de acción -->


            <div class="d-flex gap-2">
                <button
                    type="submit"
                    name="accion"
                    value="guardar"
                    class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Actualizar Indicador
                </button>

                <a
                    href="<?= base_url('partesformula/add?id_indicador=' . $indicador['id_indicador']) ?>"
                    class="btn btn-outline-secondary">
                    <i class="bi bi-pencil-square me-1"></i> Editar Fórmula
                </a>

                <a href="<?= base_url('indicadores') ?>" class="btn btn-light">Cancelar</a>
            </div>



        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>