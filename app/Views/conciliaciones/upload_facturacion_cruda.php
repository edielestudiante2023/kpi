<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Facturación CSV – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <?= view('components/back_to_dashboard') ?>
        <h1 class="h3 mb-0">Importar Facturación desde CSV</h1>
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
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-muted">Registros en BD</h5>
                    <p class="display-6 fw-bold"><?= number_format($totalRegistros ?? 0, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-muted">Última carga</h5>
                    <p class="display-6 fw-bold" style="font-size:1.4rem;">
                        <?= $ultimaCarga ? date('d/m/Y H:i', strtotime($ultimaCarga)) : 'Sin datos' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-upload me-1"></i> Subir CSV de Facturación
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <strong>Instrucciones:</strong>
                <ol class="mb-1">
                    <li>Descargue el Libro oficial de ventas del proveedor de facturación electrónica.</li>
                    <li>Elimine las filas de encabezado combinadas y pie de tabla.</li>
                    <li>Guarde como <strong>CSV delimitado por comas</strong>.</li>
                    <li>Suba el archivo aquí.</li>
                </ol>
                <a href="<?= base_url('plantillas/plantilla_facturacion.csv') ?>" class="btn btn-sm btn-outline-primary" download>
                    <i class="bi bi-download me-1"></i> Descargar plantilla CSV de ejemplo
                </a>
            </div>
            <form action="<?= base_url('conciliaciones/cruda/facturacion') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Archivo CSV (.csv)</label>
                    <input type="file" name="archivo_csv" class="form-control" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-success" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Importando...'; this.form.submit();">
                    <i class="bi bi-cloud-upload me-1"></i> Importar
                </button>
            </form>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="<?= base_url('conciliaciones/facturacion') ?>" class="btn btn-outline-primary">
            <i class="bi bi-table me-1"></i> Ver facturación
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
