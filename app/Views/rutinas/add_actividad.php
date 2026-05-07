<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Actividad – Kpi Cycloid</title>
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
        <h1 class="h3 mb-0">Nueva Actividad de Rutina</h1>
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
            <form method="post" action="<?= base_url('rutinas/actividades/add') ?>">
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required
                               value="<?= old('nombre') ?>" placeholder="Ej: Revisar correo Propiedad Horizontal">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Categoria *</label>
                        <input type="text" name="categoria" class="form-control" list="categorias-list" required
                               value="<?= old('categoria', 'General') ?>" placeholder="Ej: Operativa, Comercial, SST">
                        <datalist id="categorias-list">
                            <option value="Operativa">
                            <option value="Comercial">
                            <option value="SST">
                            <option value="Bitacora">
                            <option value="Reportes">
                            <option value="General">
                        </datalist>
                        <div class="form-text">Agrupa actividades por tipo</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Descripcion</label>
                    <textarea name="descripcion" class="form-control" rows="3"
                              placeholder="Detalle de la actividad..."><?= old('descripcion') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Frecuencia *</label>
                        <select name="frecuencia" class="form-select" required>
                            <option value="L-V" <?= old('frecuencia') === 'L-V' ? 'selected' : '' ?>>L-V (Lunes a Viernes)</option>
                            <option value="diaria" <?= old('frecuencia') === 'diaria' ? 'selected' : '' ?>>Diaria (incluye fines de semana)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Peso *</label>
                        <input type="number" name="peso" class="form-control" step="0.01" min="0.01"
                               value="<?= old('peso', '1.00') ?>" required>
                        <div class="form-text">Peso relativo para el puntaje de cumplimiento</div>
                    </div>
                </div>

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
