<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Movimiento Bancario Crudo – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <?= view('components/back_to_dashboard') ?>
        <h1 class="h3 mb-0">Cargar Movimiento Bancario Crudo (CSV)</h1>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><p class="mb-0"><?= esc($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('import_errors')): ?>
        <div class="alert alert-warning">
            <strong>Filas con errores:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach (array_slice(session()->getFlashdata('import_errors'), 0, 20) as $e): ?>
                    <li><?= esc($e) ?></li>
                <?php endforeach; ?>
                <?php if (count(session()->getFlashdata('import_errors')) > 20): ?>
                    <li>... y <?= count(session()->getFlashdata('import_errors')) - 20 ?> errores más.</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Movimientos</h5>
                    <p class="display-6 fw-bold"><?= number_format($totalRegistros ?? 0, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-muted">Última carga</h5>
                    <p class="display-6 fw-bold" style="font-size:1.4rem;">
                        <?= $ultimaCarga ? date('d/m/Y H:i', strtotime($ultimaCarga)) : 'Sin datos' ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-muted">Por Cuenta</h5>
                    <?php if (!empty($porCuenta)): ?>
                        <?php foreach ($porCuenta as $pc): ?>
                            <p class="mb-0"><strong><?= esc($pc['nombre_cuenta']) ?></strong>: <?= number_format($pc['total'], 0, ',', '.') ?></p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Sin datos</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-upload me-1"></i> Subir CSV de Movimiento Bancario
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <strong>Instrucciones:</strong>
                <ol class="mb-1">
                    <li>Descargue el extracto de movimientos del banco.</li>
                    <li>Elimine encabezados y pie de tabla combinados.</li>
                    <li>Guarde como <strong>CSV delimitado por comas</strong>.</li>
                    <li>Seleccione la cuenta bancaria y suba el archivo aquí.</li>
                </ol>
                <a href="<?= base_url('plantillas/plantilla_movimiento_bancario.csv') ?>" class="btn btn-sm btn-outline-primary" download>
                    <i class="bi bi-download me-1"></i> Descargar plantilla CSV de ejemplo
                </a>
            </div>
            <form action="<?= base_url('conciliaciones/cruda/bancario') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cuenta Bancaria</label>
                        <select name="id_cuenta_banco" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($cuentas as $cu): ?>
                                <option value="<?= $cu['id_cuenta_banco'] ?>"><?= esc($cu['nombre_cuenta']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Archivo CSV (.csv)</label>
                        <input type="file" name="archivo_csv" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Importando...'; this.form.submit();">
                    <i class="bi bi-cloud-upload me-1"></i> Importar
                </button>
            </form>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('conciliaciones/cruda/bancario/list') ?>" class="btn btn-outline-primary">
            <i class="bi bi-table me-1"></i> Ver movimientos
        </a>
        <?php foreach ($cuentas ?? [] as $cu): ?>
        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTruncar<?= $cu['id_cuenta_banco'] ?>">
            <i class="bi bi-trash me-1"></i> Vaciar <?= esc($cu['nombre_cuenta']) ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<?php foreach ($cuentas ?? [] as $cu): ?>
<div class="modal fade" id="modalTruncar<?= $cu['id_cuenta_banco'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar vaciado – <?= esc($cu['nombre_cuenta']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Esto eliminará todos los movimientos crudos de <strong><?= esc($cu['nombre_cuenta']) ?></strong>.</p>
                <p class="text-danger fw-bold">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="<?= base_url('conciliaciones/cruda/bancario/truncar/'.$cu['id_cuenta_banco']) ?>" class="btn btn-danger">Sí, vaciar</a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
