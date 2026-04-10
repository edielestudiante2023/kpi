<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conciliación Bancaria – Kpi Cycloid</title>
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
            <a href="<?= base_url('conciliaciones/bancaria/upload') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1 class="h3 mb-0">Conciliación Bancaria (<?= number_format(count($registros), 0, ',', '.') ?> movimientos)</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="get" class="d-flex gap-2">
                <select name="anio" class="form-select form-select-sm" style="width:100px;" onchange="this.form.desde.value='';this.form.hasta.value='';this.form.rango.value='todos';this.form.submit()">
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
                <select name="rango" class="form-select form-select-sm" style="width:170px;" onchange="if(this.value!=='personalizado'){this.form.desde.value='';this.form.hasta.value='';} this.form.submit()">
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
                <select name="cuenta" class="form-select form-select-sm" style="width:150px;" onchange="this.form.submit()">
                    <option value="">Todos los bancos</option>
                    <?php foreach ($resumenCuentas as $rc): ?>
                        <option value="<?= $rc['id_cuenta_banco'] ?>" <?= ($filtroCuenta ?? '') == $rc['id_cuenta_banco'] ? 'selected' : '' ?>>Banco <?= esc($rc['nombre_cuenta']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="<?= base_url('conciliaciones/bancaria/exportar?' . http_build_query(array_filter(['anio'=>$anioActual,'rango'=>$rangoActual,'desde'=>$fechaDesde ?? '','hasta'=>$fechaHasta ?? '','cuenta'=>$filtroCuenta,'centro'=>$filtroCentro,'debcred'=>$filtroDebCred]))) ?>" class="btn btn-outline-success btn-sm" title="Descargar Excel">
                <i class="bi bi-download"></i>
            </a>
            <a href="<?= base_url('conciliaciones/bancaria') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
                <i class="bi bi-eraser"></i>
            </a>
            <a href="<?= base_url('conciliaciones/bancaria/upload') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-upload me-1"></i> Cargar Excel
            </a>
        </div>
    </div>

    <?php $baseUrl = 'conciliaciones/bancaria?anio='.$anioActual.'&rango='.$rangoActual.($filtroCuenta ? '&cuenta='.$filtroCuenta : ''); ?>

    <!-- Cards: Ingreso/Egreso + Centro de Costo -->
    <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
        <?php foreach ($resumenDebCred as $rdc): ?>
        <a href="<?= base_url($baseUrl.'&debcred='.$rdc['deb_cred']) ?>" class="text-decoration-none">
            <div class="card <?= ($filtroDebCred ?? '') === $rdc['deb_cred'] ? ($rdc['deb_cred'] === 'INGRESO' ? 'bg-success text-white' : 'bg-danger text-white') : ($rdc['deb_cred'] === 'INGRESO' ? 'border-success' : 'border-danger') ?>" style="cursor:pointer; min-width:120px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small class="fw-bold"><?= esc($rdc['deb_cred']) ?></small>
                    <p class="fw-bold mb-0">$<?= number_format(abs((float)$rdc['total_valor']), 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $rdc['movimientos'] ?> mov.</small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <span class="border-start mx-1" style="height:40px;"></span>

        <a href="<?= base_url($baseUrl) ?>" class="text-decoration-none">
            <div class="card <?= empty($filtroCentro) && empty($filtroDebCred) ? 'bg-dark text-white' : 'border-dark' ?>" style="cursor:pointer; min-width:70px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small>Todos</small>
                    <p class="fw-bold mb-0"><?= number_format(count($registros)) ?></p>
                </div>
            </div>
        </a>
        <?php foreach ($resumenCentros as $rcc): ?>
        <a href="<?= base_url($baseUrl.'&centro='.$rcc['id_centro_costo']) ?>" class="text-decoration-none">
            <div class="card <?= ($filtroCentro ?? '') == $rcc['id_centro_costo'] ? 'bg-info text-white' : 'border-secondary' ?>" style="cursor:pointer; min-width:100px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small><?= esc($rcc['centro_costo']) ?></small>
                    <p class="fw-bold mb-0 <?= (float)$rcc['total_valor'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        $<?= number_format((float)$rcc['total_valor'], 0, ',', '.') ?>
                    </p>
                    <small class="text-muted"><?= $rcc['movimientos'] ?></small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <table id="conciliacionTable" class="table table-striped table-hover nowrap" style="width:100%; font-size:0.85rem;">
        <thead class="table-dark">
            <tr>
                <th>Cuenta</th>
                <th>Centro Costo</th>
                <th>Llave Item</th>
                <th>Deb/Cred</th>
                <th>FV</th>
                <th>Cliente/Item</th>
                <th>Año</th>
                <th>Mes</th>
                <th>Mes Real</th>
                <th>Fecha Sistema</th>
                <th class="text-end">Valor</th>
                <th>Transacción</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><select class="form-select form-select-sm"><option value="">Todas</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todas</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Buscar FV..."></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Buscar cliente..."></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todas</option></select></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Buscar..."></th>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= $r['nombre_cuenta'] ? 'Banco ' . esc($r['nombre_cuenta']) : '' ?></td>
                <td><?= esc($r['centro_costo'] ?? '') ?></td>
                <td><?= esc($r['llave_item']) ?></td>
                <td>
                    <?= $r['deb_cred'] === 'INGRESO'
                        ? '<span class="badge bg-success">INGRESO</span>'
                        : '<span class="badge bg-danger">EGRESO</span>'
                    ?>
                </td>
                <td><?= esc($r['fv'] ?? '') ?></td>
                <td><?= esc($r['item_cliente'] ?? '') ?></td>
                <td><?= esc($r['anio']) ?></td>
                <td><?= esc($r['mes']) ?></td>
                <td><?= esc($r['mes_real']) ?></td>
                <td><?= $r['fecha_sistema'] ? date('d/m/Y', strtotime($r['fecha_sistema'])) : '' ?></td>
                <td class="text-end <?= (float)$r['valor'] < 0 ? 'text-danger' : 'text-success' ?>">
                    <?= number_format((float)$r['valor'], 0, ',', '.') ?>
                </td>
                <td><?= esc($r['transaccion'] ?? '') ?></td>
                <td><?= esc(mb_substr($r['descripcion_motivo'] ?? '', 0, 50)) ?></td>
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
    var selectCols = [0, 1, 2, 3, 6, 7, 8, 11];
    var inputCols = [4, 5, 12];
    var table = $('#conciliacionTable').DataTable({
        pageLength: 50,
        lengthMenu: [[50, 100, 200, -1], [50, 100, 200, 'Todos']],
        responsive: true,
        autoWidth: false,
        order: [[9, 'desc']],
        initComplete: function () {
            var api = this.api();
            // Selects
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
