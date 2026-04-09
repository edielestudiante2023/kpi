<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Deuda – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="<?= base_url('conciliaciones/deudas') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0">Editar Deuda</h1>
    </div>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><p><?= esc($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="<?= base_url('conciliaciones/deudas/edit/'.$deuda['id_deuda']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Concepto</label>
                <input type="text" name="concepto" class="form-control" value="<?= old('concepto', esc($deuda['concepto'])) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Acreedor</label>
                <input type="text" name="acreedor" class="form-control" value="<?= old('acreedor', esc($deuda['acreedor'])) ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Monto Original ($)</label>
                <input type="number" step="0.01" name="monto_original" class="form-control" value="<?= old('monto_original', $deuda['monto_original']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Fecha de Registro</label>
                <input type="date" name="fecha_registro" class="form-control" value="<?= old('fecha_registro', $deuda['fecha_registro']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Fecha de Vencimiento (opcional)</label>
                <input type="date" name="fecha_vencimiento" class="form-control" value="<?= old('fecha_vencimiento', $deuda['fecha_vencimiento']) ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Notas (opcional)</label>
            <textarea name="notas" class="form-control" rows="2"><?= old('notas', esc($deuda['notas'])) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar Deuda</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
