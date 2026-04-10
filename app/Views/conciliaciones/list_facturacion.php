<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('conciliaciones/facturacion/upload') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1 class="h3 mb-0">Facturación (<?= number_format(count($registros), 0, ',', '.') ?> registros)</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="get" class="d-flex gap-2">
                <select name="anio" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
                    <option value="todos" <?= ($anioActual ?? '') === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <?php foreach ($anios as $a): ?>
                        <option value="<?= $a['anio'] ?>" <?= ($anioActual ?? '') == $a['anio'] ? 'selected' : '' ?>><?= $a['anio'] ?></option>
                    <?php endforeach; ?>
                </select>
                <?php
                $mesesNombre = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                $rangos = [
                    'todos' => 'Todo el año',
                    'mes_actual' => 'Mes actual',
                    'mes_anterior' => 'Mes anterior',
                    'bimestre_anterior' => 'Bimestre anterior',
                    'trimestre_anterior' => 'Trimestre anterior',
                    'cuatrimestre_anterior' => 'Cuatrimestre anterior',
                    'semestre_anterior' => 'Semestre anterior',
                ];
                ?>
                <select name="rango" class="form-select form-select-sm" style="width:170px;" onchange="this.form.submit()">
                    <?php foreach ($rangos as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($rangoActual ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                    <option value="personalizado" <?= ($rangoActual ?? '') === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
                    <optgroup label="Mes específico">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= sprintf('%02d', $m) ?>" <?= ($rangoActual ?? '') === sprintf('%02d', $m) ? 'selected' : '' ?>><?= $mesesNombre[$m] ?></option>
                        <?php endfor; ?>
                    </optgroup>
                </select>
                <input type="date" name="desde" class="form-control form-control-sm" style="width:140px;" value="<?= $fechaDesde ?? '' ?>" onchange="document.querySelector('[name=rango]').value='personalizado'; this.form.submit()">
                <input type="date" name="hasta" class="form-control form-control-sm" style="width:140px;" value="<?= $fechaHasta ?? '' ?>" onchange="document.querySelector('[name=rango]').value='personalizado'; this.form.submit()">
            </form>
            <a href="<?= base_url('conciliaciones/facturacion/exportar?' . http_build_query(array_filter(['anio'=>$anioActual,'rango'=>$rangoActual,'desde'=>$fechaDesde,'hasta'=>$fechaHasta,'pagado'=>$filtroPagado,'portafolio'=>$filtroPortafolio,'vencida'=>$filtroVencida]))) ?>" class="btn btn-outline-success btn-sm" title="Descargar Excel">
                <i class="bi bi-download"></i>
            </a>
            <a href="<?= base_url('conciliaciones/facturacion') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
                <i class="bi bi-eraser"></i>
            </a>
            <a href="<?= base_url('conciliaciones/facturacion/upload') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-upload me-1"></i> Cargar Excel
            </a>
        </div>
    </div>

    <?php $baseUrl = 'conciliaciones/facturacion?anio='.$anioActual.'&rango='.$rangoActual; ?>

    <!-- Cards: Totales financieros -->
    <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
        <div class="card border-success" style="min-width:130px;">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold">BASE GRAVADA</small>
                <p class="fw-bold mb-0 text-success">$<?= number_format($totalBaseGravada, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="card border-warning" style="min-width:130px;">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold">IVA</small>
                <p class="fw-bold mb-0 text-warning">$<?= number_format($totalIva, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="card border-danger" style="min-width:130px;">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold">RETENCIÓN 4%</small>
                <p class="fw-bold mb-0 text-danger">-$<?= number_format($totalRetencion, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="card border-primary bg-primary bg-opacity-10" style="min-width:130px;">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold">LÍQUIDO</small>
                <p class="fw-bold mb-0 text-primary">$<?= number_format($totalLiquido, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <!-- Cards: Pagado/No Pagado + Cartera Vencida + Portafolios -->
    <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
        <?php foreach ($resumenPagado as $rpg): ?>
        <a href="<?= base_url($baseUrl.'&pagado='.$rpg['pagado'].($filtroPortafolio ? '&portafolio='.$filtroPortafolio : '')) ?>" class="text-decoration-none">
            <div class="card <?= ($filtroPagado ?? '') === (string)$rpg['pagado'] ? ($rpg['pagado'] ? 'bg-success text-white' : 'bg-danger text-white') : ($rpg['pagado'] ? 'border-success' : 'border-danger') ?>" style="cursor:pointer; min-width:130px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small class="fw-bold"><?= $rpg['pagado'] ? 'PAGADAS' : 'CARTERA' ?></small>
                    <p class="fw-bold mb-0">$<?= number_format((float)$rpg['total_base'], 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $rpg['facturas'] ?> facturas</small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <?php if ($facturasVencidas > 0): ?>
        <a href="<?= base_url($baseUrl.'&vencida=1'.($filtroPortafolio ? '&portafolio='.$filtroPortafolio : '')) ?>" class="text-decoration-none">
        <div class="card border-dark <?= ($filtroVencida ?? '') === '1' ? 'bg-danger text-white' : 'bg-danger bg-opacity-10' ?>" style="cursor:pointer; min-width:140px;">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold <?= ($filtroVencida ?? '') === '1' ? '' : 'text-danger' ?>">CARTERA VENCIDA</small>
                <p class="fw-bold mb-0 <?= ($filtroVencida ?? '') === '1' ? '' : 'text-danger' ?>">$<?= number_format($totalCarteraVencida, 0, ',', '.') ?></p>
                <small class="<?= ($filtroVencida ?? '') === '1' ? '' : 'text-muted' ?>"><?= $facturasVencidas ?> facturas &gt;30 días</small>
            </div>
        </div>
        </a>
        <?php endif; ?>

        <span class="border-start mx-1" style="height:40px;"></span>

        <a href="<?= base_url($baseUrl) ?>" class="text-decoration-none">
            <div class="card <?= empty($filtroPortafolio) && ($filtroPagado === null || $filtroPagado === '') ? 'bg-dark text-white' : 'border-dark' ?>" style="cursor:pointer; min-width:70px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small>Todas</small>
                    <p class="fw-bold mb-0"><?= number_format(count($registros)) ?></p>
                </div>
            </div>
        </a>
        <?php foreach ($resumenPortafolios as $rp): ?>
        <a href="<?= base_url($baseUrl.'&portafolio='.$rp['id_portafolio'].($filtroPagado !== null && $filtroPagado !== '' ? '&pagado='.$filtroPagado : '')) ?>" class="text-decoration-none">
            <div class="card <?= ($filtroPortafolio ?? '') == $rp['id_portafolio'] ? 'bg-primary text-white' : 'border-primary' ?>" style="cursor:pointer; min-width:100px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small><?= esc($rp['portafolio']) ?></small>
                    <p class="fw-bold mb-0 text-success">$<?= number_format((float)$rp['total_base'], 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $rp['facturas'] ?></small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <table id="facturacionTable" class="table table-striped table-hover nowrap" style="width:100%; font-size:0.85rem;">
        <thead class="table-dark">
            <tr>
                <th>Portafolio</th>
                <th>Año</th>
                <th>Mes</th>
                <th>Comprobante</th>
                <th>Fecha Elab.</th>
                <th>NIT</th>
                <th>Cliente</th>
                <th class="text-end">Base Gravada</th>
                <th class="text-end">IVA</th>
                <th class="text-end">Ret. 4%</th>
                <th class="text-end">Líquido</th>
                <th>Pagado</th>
                <th>Fecha Pago</th>
                <th class="text-end">Valor Pagado</th>
                <th>Vendedor</th>
                <th>Detallado</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Comprobante..."></th>
                <th></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="NIT..."></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Cliente..."></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= esc($r['portafolio'] ?? '') ?></td>
                <td><?= esc($r['anio']) ?></td>
                <td><?= esc($r['mes']) ?></td>
                <td><?= esc($r['comprobante']) ?></td>
                <td><?= $r['fecha_elaboracion'] ? date('d/m/Y', strtotime($r['fecha_elaboracion'])) : '' ?></td>
                <td><?= esc($r['identificacion']) ?></td>
                <td><?= esc($r['nombre_tercero']) ?></td>
                <td class="text-end"><?= number_format((float)$r['base_gravada'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format((float)$r['iva'], 0, ',', '.') ?></td>
                <td class="text-end text-danger"><?= number_format(abs((float)$r['retefuente_4']), 0, ',', '.') ?></td>
                <td class="text-end text-success fw-bold"><?= number_format((float)$r['base_gravada'] - abs((float)$r['retefuente_4']), 0, ',', '.') ?></td>
                <td>
                    <?= $r['pagado']
                        ? '<span class="badge bg-success">SI</span>'
                        : '<span class="badge bg-danger">NO</span>'
                    ?>
                </td>
                <td><?= $r['fecha_pago'] ? date('d/m/Y', strtotime($r['fecha_pago'])) : '' ?></td>
                <td class="text-end"><?= $r['valor_pagado'] ? number_format((float)$r['valor_pagado'], 0, ',', '.') : '' ?></td>
                <td><?= esc($r['vendedor'] ?? '') ?></td>
                <td><?= esc($r['portafolio_detallado'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    var selectCols = [0, 1, 2, 11, 14, 15];
    var inputCols = [3, 5, 6];
    var table = $('#facturacionTable').DataTable({
        pageLength: 50,
        lengthMenu: [[50, 100, 200, -1], [50, 100, 200, 'Todos']],
        responsive: true,
        autoWidth: false,
        order: [[4, 'desc']],
        initComplete: function () {
            var api = this.api();
            api.columns(selectCols).every(function () {
                var column = this;
                var select = $('select', column.footer());
                column.data().unique().sort().each(function (d) {
                    var txt = $('<div>').html(d).text().trim();
                    if (txt.length && select.find('option[value="'+txt+'"]').length === 0) {
                        select.append('<option value="'+txt+'">'+txt+'</option>');
                    }
                });
                select.on('change', function () {
                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                    column.search(val ? '^'+val+'$' : '', true, false).draw();
                });
            });
            // Inputs de texto
            api.columns(inputCols).every(function () {
                var column = this;
                $('input', column.footer()).on('keyup change', function () {
                    if (column.search() !== this.value) {
                        column.search(this.value).draw();
                    }
                });
            });
        },
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
            zeroRecords: "No se encontraron registros"
        }
    });
});
</script>
</body>
</html>
