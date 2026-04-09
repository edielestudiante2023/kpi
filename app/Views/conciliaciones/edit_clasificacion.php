<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Clasificación – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="<?= base_url('conciliaciones/clasificacion') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0">Editar Clasificación</h1>
    </div>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><p><?= esc($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="<?= base_url('conciliaciones/clasificacion/edit/'.$clasif['id_clasificacion']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Llave Item</label>
                <input type="text" name="llave_item" class="form-control" value="<?= old('llave_item', esc($clasif['llave_item'])) ?>" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Categoría</label>
                <input type="text" name="categoria" class="form-control" list="categoriasList" value="<?= old('categoria', esc($clasif['categoria'])) ?>" required>
                <datalist id="categoriasList">
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= esc($cat['categoria']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" required>
                    <?php $t = old('tipo', $clasif['tipo']); ?>
                    <option value="fijo" <?= $t === 'fijo' ? 'selected' : '' ?>>Fijo</option>
                    <option value="variable" <?= $t === 'variable' ? 'selected' : '' ?>>Variable</option>
                    <option value="ingreso" <?= $t === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
                    <option value="neutro" <?= $t === 'neutro' ? 'selected' : '' ?>>Neutro</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
