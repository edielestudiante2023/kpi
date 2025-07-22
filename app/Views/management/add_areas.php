<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Área – Afilogro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <h1 class="h3 mb-4">Crear Nueva Área</h1>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><p><?= esc($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="<?= base_url('areas/add') ?>" method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Área</label>
            <input type="text" name="nombre_area" class="form-control" value="<?= old('nombre_area') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion_area" class="form-control"><?= old('descripcion_area') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado_area" class="form-select" required>
                <option value="1" <?= old('estado_area')=='1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= old('estado_area')=='0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Guardar Área</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
