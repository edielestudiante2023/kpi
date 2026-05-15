<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extractos bancarios – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<?php
$meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
          7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
$esContador = ((int) session()->get('id_roles') === 5);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <?= view('components/back_to_dashboard') ?>
            <h1 class="h3 mb-0"><i class="bi bi-file-earmark-pdf me-2"></i>Extractos bancarios</h1>
        </div>
    </div>
    <p class="text-muted small">
        PDFs de los extractos de los bancos, organizados por cuenta y periodo. La contadora los revisa desde aquí.
    </p>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
                <div><?= esc($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label small mb-1">Año</label>
            <select name="anio" class="form-select form-select-sm">
                <?php foreach ($anios as $a): ?>
                    <option value="<?= $a ?>" <?= $filtroAnio == $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small mb-1">Cuenta bancaria</label>
            <select name="cuenta" class="form-select form-select-sm">
                <option value="">Todas</option>
                <?php foreach ($cuentas as $c): ?>
                    <option value="<?= $c['id_cuenta_banco'] ?>"
                        <?= $filtroCuenta == $c['id_cuenta_banco'] ? 'selected' : '' ?>>
                        <?= esc($c['nombre_cuenta']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-sm btn-outline-secondary w-100" type="submit">Filtrar</button>
        </div>
    </form>

    <!-- Subir extracto (solo si no es contador, por filtro readonly_conciliaciones) -->
    <?php if (!$esContador): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h6 class="mb-2"><i class="bi bi-cloud-upload me-1"></i> Subir extracto</h6>
            <form method="post" action="<?= base_url('conciliaciones/extractos-bancarios/subir') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Cuenta bancaria *</label>
                        <select name="id_cuenta_banco" class="form-select form-select-sm" required>
                            <option value="">—</option>
                            <?php foreach ($cuentas as $c): ?>
                                <option value="<?= $c['id_cuenta_banco'] ?>"><?= esc($c['nombre_cuenta']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Año *</label>
                        <input type="number" name="anio" class="form-control form-control-sm" value="<?= date('Y') ?>" min="2000" max="2100" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Mes *</label>
                        <select name="mes" class="form-select form-select-sm" required>
                            <?php foreach ($meses as $n => $nombre): ?>
                                <option value="<?= $n ?>" <?= $n == (int) date('n') ? 'selected' : '' ?>><?= $nombre ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">PDF *</label>
                        <input type="file" name="archivo" class="form-control form-control-sm" accept="application/pdf" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm w-100" type="submit">
                            <i class="bi bi-cloud-upload-fill me-1"></i> Subir
                        </button>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Descripción (opcional)</label>
                        <input type="text" name="descripcion" class="form-control form-control-sm" placeholder="Ej: Extracto consolidado">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Listado -->
    <?php if (empty($extractos)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No hay extractos para los filtros seleccionados.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Cuenta</th>
                        <th>Periodo</th>
                        <th>Descripción</th>
                        <th>Archivo</th>
                        <th>Subido</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($extractos as $e): ?>
                    <tr>
                        <td><?= esc($e['nombre_cuenta'] ?? '—') ?></td>
                        <td><?= esc(($meses[(int) $e['mes']] ?? '?') . ' ' . $e['anio']) ?></td>
                        <td><?= esc($e['descripcion'] ?? '') ?></td>
                        <td>
                            <small class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= esc($e['nombre_original']) ?>">
                                <i class="bi bi-file-earmark-pdf text-danger"></i> <?= esc($e['nombre_original']) ?>
                            </small>
                        </td>
                        <td>
                            <small>
                                <?= esc($e['subido_por'] ?? '—') ?><br>
                                <span class="text-muted"><?= date('d/m/Y', strtotime($e['created_at'])) ?></span>
                            </small>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="<?= base_url('conciliaciones/extractos-bancarios/ver/' . $e['id_extracto']) ?>"
                               class="btn btn-sm btn-outline-primary" target="_blank" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= base_url('conciliaciones/extractos-bancarios/descargar/' . $e['id_extracto']) ?>"
                               class="btn btn-sm btn-outline-secondary" title="Descargar">
                                <i class="bi bi-download"></i>
                            </a>
                            <?php if (!$esContador): ?>
                            <a href="<?= base_url('conciliaciones/extractos-bancarios/eliminar/' . $e['id_extracto']) ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar este extracto?')" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
