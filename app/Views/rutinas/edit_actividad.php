<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Actividad – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="<?= base_url('rutinas/actividades') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0">Editar Actividad</h1>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
            <?php foreach (session()->getFlashdata('errors') as $e): ?>
                <li><?= esc($e) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= base_url('rutinas/actividades/edit/' . $actividad['id_actividad']) ?>">
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required
                               value="<?= old('nombre', $actividad['nombre']) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Categoria *</label>
                        <input type="text" name="categoria" class="form-control" list="categorias-list" required
                               value="<?= old('categoria', $actividad['categoria'] ?? 'General') ?>">
                        <datalist id="categorias-list">
                            <option value="Operativa">
                            <option value="Comercial">
                            <option value="SST">
                            <option value="Bitacora">
                            <option value="Reportes">
                            <option value="General">
                        </datalist>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Descripcion</label>
                    <textarea name="descripcion" class="form-control" rows="3"><?= old('descripcion', $actividad['descripcion']) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Frecuencia *</label>
                        <select name="frecuencia" id="frecuencia" class="form-select" required>
                            <option value="L-V" <?= old('frecuencia', $actividad['frecuencia']) === 'L-V' ? 'selected' : '' ?>>📆 L-V</option>
                            <option value="diaria" <?= old('frecuencia', $actividad['frecuencia']) === 'diaria' ? 'selected' : '' ?>>📅 Diaria</option>
                            <option value="semanal" <?= old('frecuencia', $actividad['frecuencia']) === 'semanal' ? 'selected' : '' ?>>🗓️ Semanal</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3" id="campoMetaSemanal" style="display:none;">
                        <label class="form-label fw-bold">Meta semanal *</label>
                        <input type="number" name="meta_semanal" class="form-control" min="1" max="7"
                               value="<?= old('meta_semanal', $actividad['meta_semanal'] ?? 2) ?>" placeholder="Ej: 2">
                        <div class="form-text">Veces por semana</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Peso *</label>
                        <input type="number" name="peso" class="form-control" step="0.01" min="0.01"
                               value="<?= old('peso', $actividad['peso']) ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Estado *</label>
                        <select name="activa" class="form-select" required>
                            <option value="1" <?= old('activa', $actividad['activa']) == 1 ? 'selected' : '' ?>>Activa</option>
                            <option value="0" <?= old('activa', $actividad['activa']) == 0 ? 'selected' : '' ?>>Inactiva</option>
                        </select>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var sel = document.getElementById('frecuencia');
                        var campo = document.getElementById('campoMetaSemanal');
                        function toggle() {
                            campo.style.display = sel.value === 'semanal' ? '' : 'none';
                        }
                        sel.addEventListener('change', toggle);
                        toggle();
                    });
                </script>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                    <a href="<?= base_url('rutinas/actividades') ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
