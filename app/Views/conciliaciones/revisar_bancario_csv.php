<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisar Movimientos CSV – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="<?= base_url('conciliaciones/cruda/bancario') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0">Revisar movimientos bancarios – Banco <?= esc($cuentaNombre) ?></h1>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="alert alert-success py-2 px-3 mb-0">
            <i class="bi bi-check-circle me-1"></i> <strong><?= count($movimientos) ?></strong> movimientos encontrados
        </div>
        <?php
            $totalIng = 0; $totalEgr = 0;
            foreach ($movimientos as $m) {
                if ($m['valor'] >= 0) $totalIng += $m['valor'];
                else $totalEgr += abs($m['valor']);
            }
        ?>
        <div class="alert alert-light border py-2 px-3 mb-0">
            <span class="text-success fw-bold">Ingresos: $<?= number_format($totalIng, 0, ',', '.') ?></span>
            &nbsp;|&nbsp;
            <span class="text-danger fw-bold">Egresos: $<?= number_format($totalEgr, 0, ',', '.') ?></span>
            &nbsp;|&nbsp;
            <span class="fw-bold <?= ($totalIng - $totalEgr) >= 0 ? 'text-success' : 'text-danger' ?>">Neto: $<?= number_format($totalIng - $totalEgr, 0, ',', '.') ?></span>
        </div>
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
        </ul>
    </div>
    <?php endif; ?>

    <form action="<?= base_url('conciliaciones/cruda/bancario/confirmar') ?>" method="post">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
                <strong>Centro de costo global:</strong>
                <?php
                $centroLabel = function($nombre) {
                    return match(mb_strtoupper($nombre)) {
                        'DEBITO'  => 'EGRESO',
                        'CREDITO' => 'INGRESO',
                        default   => $nombre,
                    };
                };
                ?>
                <select name="centro_global" id="centroGlobal" class="form-select form-select-sm" style="width:180px;">
                    <option value="">Sin asignar</option>
                    <?php foreach ($centros as $c): ?>
                        <option value="<?= $c['id_centro_costo'] ?>"><?= esc($centroLabel($c['centro_costo'])) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAplicarCentro">
                    <i class="bi bi-arrow-down-circle me-1"></i>Aplicar a todos
                </button>
                <span class="border-start mx-2" style="height:25px;"></span>
                <strong>Llave item global:</strong>
                <input type="text" id="llaveGlobal" class="form-control form-control-sm" style="width:180px;" list="llaveItemsSugerencias" placeholder="Ej: ABONO CYCLOID">
                <button type="button" class="btn btn-sm btn-outline-info" id="btnAplicarLlave">
                    <i class="bi bi-arrow-down-circle me-1"></i>Aplicar a todos
                </button>
                <datalist id="llaveItemsSugerencias">
                    <?php foreach ($llaveItems as $li): ?>
                        <option value="<?= esc($li) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <div class="table-responsive">
        <table class="table table-striped table-hover" style="font-size:0.78rem;">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Movimiento</th>
                    <th class="text-end">Valor</th>
                    <th>Documento</th>
                    <th>Descripcion</th>
                    <th>Transaccion</th>
                    <th>Centro Costo</th>
                    <th>Llave Item</th>
                    <th>FV</th>
                    <th>Item/Cliente</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($movimientos as $i => $m): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= date('d/m/Y', strtotime($m['fecha_sistema'])) ?></td>
                    <td>
                        <?= $m['deb_cred'] === 'INGRESO'
                            ? '<span class="badge bg-success">INGRESO</span>'
                            : '<span class="badge bg-danger">EGRESO</span>' ?>
                    </td>
                    <td class="text-end fw-bold <?= (float)$m['valor'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        $<?= number_format(abs((float)$m['valor']), 0, ',', '.') ?>
                    </td>
                    <td><?= esc($m['documento'] ?? '') ?></td>
                    <td title="<?= esc($m['descripcion_motivo'] ?? '') ?>"><?= esc(mb_substr($m['descripcion_motivo'] ?? '', 0, 40)) ?></td>
                    <td><?= esc($m['transaccion'] ?? '') ?></td>
                    <td>
                        <select name="centro[<?= $i ?>]" class="form-select form-select-sm centro-individual" style="min-width:110px;">
                            <option value="">--</option>
                            <?php foreach ($centros as $c): ?>
                                <option value="<?= $c['id_centro_costo'] ?>"><?= esc($centroLabel($c['centro_costo'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="llave_item[<?= $i ?>]" class="form-control form-control-sm llave-individual" list="llaveItemsSugerencias" style="min-width:120px;" placeholder="Llave...">
                    </td>
                    <td>
                        <input type="text" name="fv[<?= $i ?>]" class="form-control form-control-sm" style="min-width:80px;" placeholder="FV...">
                    </td>
                    <td>
                        <input type="text" name="item_cliente[<?= $i ?>]" class="form-control form-control-sm" style="min-width:120px;" placeholder="Cliente...">
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-success btn-lg" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Importando...'; this.form.submit();">
                <i class="bi bi-check-circle me-1"></i> Confirmar e importar <?= count($movimientos) ?> movimientos
            </button>
            <a href="<?= base_url('conciliaciones/cruda/bancario') ?>" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-x me-1"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$('#btnAplicarCentro').on('click', function() {
    var val = $('#centroGlobal').val();
    if (!val) { alert('Seleccione un centro de costo primero.'); return; }
    $('.centro-individual').each(function() {
        if (!$(this).val()) $(this).val(val);
    });
});

$('#btnAplicarLlave').on('click', function() {
    var val = $('#llaveGlobal').val().trim();
    if (!val) { alert('Escriba una llave item primero.'); return; }
    $('.llave-individual').each(function() {
        if (!$(this).val()) $(this).val(val);
    });
});
</script>
</body>
</html>
