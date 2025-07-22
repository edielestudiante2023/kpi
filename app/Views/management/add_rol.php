<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Rol – Afilogro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <h1 class="h3 mb-4">Crear Nuevo Rol</h1>
    <?php if(session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach(session()->getFlashdata('errors') as $e): ?><p><?= esc($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="<?= base_url('roles/add') ?>" method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Rol</label>
            <input type="text" name="nombre_rol" class="form-control" value="<?= old('nombre_rol') ?>" required>
        </div>
        <button type="submit" class="btn btn-success">Guardar Rol</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>