<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisar Facturación CSV – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="<?= base_url('conciliaciones/cruda/facturacion') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0">Revisar facturas del CSV</h1>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="alert alert-success py-2 px-3 mb-0">
            <i class="bi bi-check-circle me-1"></i> <strong><?= count($facturas) ?></strong> facturas nuevas encontradas
        </div>
        <?php if ($duplicados > 0): ?>
        <div class="alert alert-warning py-2 px-3 mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i> <strong><?= $duplicados ?></strong> ya existian (omitidas)
        </div>
        <?php endif; ?>
        <?php if (!empty($errores)): ?>
        <div class="alert alert-danger py-2 px-3 mb-0">
            <i class="bi bi-x-circle me-1"></i> <?= count($errores) ?> filas con errores
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger mb-3" style="font-size:0.85rem;">
        <strong>Detalle errores:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach (array_slice($errores, 0, 10) as $e): ?>
                <li><?= esc($e) ?></li>
            <?php endforeach; ?>
            <?php if (count($errores) > 10): ?>
                <li>... y <?= count($errores) - 10 ?> mas.</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form action="<?= base_url('conciliaciones/cruda/facturacion/confirmar') ?>" method="post">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-body py-2 d-flex align-items-center gap-3">
                <strong>Portafolio global:</strong>
                <select name="portafolio_global" id="portafolioGlobal" class="form-select form-select-sm" style="width:200px;">
                    <option value="">Sin asignar</option>
                    <?php foreach ($portafolios as $p): ?>
                        <option value="<?= $p['id_portafolio'] ?>"><?= esc($p['portafolio']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAplicarGlobal">
                    <i class="bi bi-arrow-down-circle me-1"></i>Aplicar a todas
                </button>
                <span class="text-muted" style="font-size:0.8rem;">O asigne individualmente en cada fila</span>
            </div>
        </div>

        <div class="table-responsive">
        <table class="table table-striped table-hover" style="font-size:0.82rem;">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Comprobante</th>
                    <th>Fecha</th>
                    <th>NIT</th>
                    <th>Cliente</th>
                    <th class="text-end">Base Gravada</th>
                    <th class="text-end">IVA</th>
                    <th class="text-end">Ret. 4%</th>
                    <th class="text-end">Liquido</th>
                    <th class="text-end">Total</th>
                    <th>Portafolio</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($facturas as $i => $f):
                $ret4 = round((float)$f['base_gravada'] * 0.04, 2);
                $liquido = (float)$f['base_gravada'] - $ret4;
            ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= esc($f['comprobante']) ?></strong></td>
                    <td><?= date('d/m/Y', strtotime($f['fecha_elaboracion'])) ?></td>
                    <td><?= esc($f['identificacion']) ?></td>
                    <td><?= esc($f['nombre_tercero']) ?></td>
                    <td class="text-end">$<?= number_format((float)$f['base_gravada'], 0, ',', '.') ?></td>
                    <td class="text-end">$<?= number_format((float)$f['iva'], 0, ',', '.') ?></td>
                    <td class="text-end text-danger">$<?= number_format($ret4, 0, ',', '.') ?></td>
                    <td class="text-end text-success fw-bold">$<?= number_format($liquido, 0, ',', '.') ?></td>
                    <td class="text-end">$<?= number_format((float)$f['total'], 0, ',', '.') ?></td>
                    <td>
                        <select name="portafolio[<?= $i ?>]" class="form-select form-select-sm portafolio-individual" style="min-width:120px;">
                            <option value="">--</option>
                            <?php foreach ($portafolios as $p): ?>
                                <option value="<?= $p['id_portafolio'] ?>"><?= esc($p['portafolio']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="5" class="text-end">TOTALES</th>
                    <th class="text-end">$<?= number_format(array_sum(array_column($facturas, 'base_gravada')), 0, ',', '.') ?></th>
                    <th class="text-end">$<?= number_format(array_sum(array_column($facturas, 'iva')), 0, ',', '.') ?></th>
                    <th class="text-end">$<?= number_format(array_sum(array_column($facturas, 'base_gravada')) * 0.04, 0, ',', '.') ?></th>
                    <th class="text-end">$<?= number_format(array_sum(array_column($facturas, 'base_gravada')) * 0.96, 0, ',', '.') ?></th>
                    <th class="text-end">$<?= number_format(array_sum(array_column($facturas, 'total')), 0, ',', '.') ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-success btn-lg" id="btnConfirmar" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Importando...'; this.form.submit();">
                <i class="bi bi-check-circle me-1"></i> Confirmar e importar <?= count($facturas) ?> facturas
            </button>
            <a href="<?= base_url('conciliaciones/cruda/facturacion') ?>" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-x me-1"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$('#btnAplicarGlobal').on('click', function() {
    var val = $('#portafolioGlobal').val();
    if (!val) { alert('Seleccione un portafolio global primero.'); return; }
    $('.portafolio-individual').each(function() {
        if (!$(this).val()) { // solo las que no tienen asignado
            $(this).val(val);
        }
    });
});
</script>
</body>
</html>
