<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Categoría - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .color-preview {
            width: 40px;
            height: 38px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .color-option {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .color-option:hover {
            transform: scale(1.1);
        }
        .color-option.selected {
            border-color: #000;
            box-shadow: 0 0 0 2px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-plus-circle me-2"></i>Nueva Categoría</h1>
                <p class="text-muted mb-0">Crear una nueva categoría para actividades</p>
            </div>
            <a href="<?= base_url('categorias-actividad') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>

        <!-- Errores -->
        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="<?= base_url('categorias-actividad/guardar') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="row">
                        <!-- Nombre -->
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nombre de la Categoría <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="nombre_categoria"
                                   class="form-control"
                                   value="<?= old('nombre_categoria') ?>"
                                   placeholder="Ej: Desarrollo, Marketing, Soporte..."
                                   maxlength="100"
                                   required>
                        </div>

                        <!-- Color -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Color <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="color-preview input-group-text p-0 overflow-hidden">
                                    <input type="color"
                                           id="colorPicker"
                                           name="color"
                                           value="<?= old('color', '#6c757d') ?>"
                                           class="border-0 w-100 h-100"
                                           style="cursor: pointer;">
                                </span>
                                <input type="text"
                                       id="colorHex"
                                       class="form-control"
                                       value="<?= old('color', '#6c757d') ?>"
                                       placeholder="#6c757d"
                                       maxlength="20"
                                       readonly>
                            </div>
                        </div>

                        <!-- Colores predefinidos -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Colores sugeridos</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php
                                $colores = [
                                    '#6c757d' => 'Gris',
                                    '#0d6efd' => 'Azul',
                                    '#198754' => 'Verde',
                                    '#dc3545' => 'Rojo',
                                    '#ffc107' => 'Amarillo',
                                    '#0dcaf0' => 'Cyan',
                                    '#6f42c1' => 'Morado',
                                    '#fd7e14' => 'Naranja',
                                    '#d63384' => 'Rosa',
                                    '#20c997' => 'Turquesa',
                                    '#495057' => 'Gris Oscuro',
                                    '#343a40' => 'Negro'
                                ];
                                foreach ($colores as $hex => $nombre):
                                ?>
                                    <div class="color-option <?= old('color', '#6c757d') === $hex ? 'selected' : '' ?>"
                                         style="background-color: <?= $hex ?>;"
                                         data-color="<?= $hex ?>"
                                         title="<?= $nombre ?>"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Descripción opcional de la categoría..."
                                      maxlength="500"><?= old('descripcion') ?></textarea>
                        </div>

                        <!-- Estado -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="activa" <?= old('estado', 'activa') === 'activa' ? 'selected' : '' ?>>Activa</option>
                                <option value="inactiva" <?= old('estado') === 'inactiva' ? 'selected' : '' ?>>Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar Categoría
                        </button>
                        <a href="<?= base_url('categorias-actividad') ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const colorPicker = document.getElementById('colorPicker');
        const colorHex = document.getElementById('colorHex');
        const colorOptions = document.querySelectorAll('.color-option');

        // Sincronizar color picker con input hex
        colorPicker.addEventListener('input', function() {
            colorHex.value = this.value;
            updateSelectedColor(this.value);
        });

        // Click en colores predefinidos
        colorOptions.forEach(option => {
            option.addEventListener('click', function() {
                const color = this.dataset.color;
                colorPicker.value = color;
                colorHex.value = color;
                updateSelectedColor(color);
            });
        });

        function updateSelectedColor(color) {
            colorOptions.forEach(opt => {
                opt.classList.toggle('selected', opt.dataset.color === color);
            });
        }
    </script>
</body>
</html>
