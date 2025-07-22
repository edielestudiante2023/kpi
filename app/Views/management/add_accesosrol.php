<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Acceso por Rol – Afilogro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <h1 class="h4 mb-4">Registrar Nuevo Acceso</h1>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <p><?= esc($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('accesosrol/add') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label">Rol</label>
            <select name="id_roles" class="form-select" required>
                <option value="">-- Selecciona un rol --</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id_roles'] ?>"><?= esc($r['nombre_rol']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Detalle del Acceso</label>
            <input type="text" name="detalle" class="form-control" value="<?= old('detalle') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Enlace (URL relativa)</label>
            <input type="text" name="enlace" class="form-control" placeholder="/ruta/ejemplo" value="<?= old('enlace') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select" required>
                <option value="activo" selected>Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>

        <div class="d-flex justify-content-start">
            <button type="submit" class="btn btn-success me-2">Guardar</button>
            <a href="<?= base_url('accesosrol') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
