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
            <a href="<?= base_url('conciliaciones/bancaria/exportar?' . http_build_query(array_filter(['anio'=>$anioActual,'rango'=>$rangoActual,'desde'=>$fechaDesde ?? '','hasta'=>$fechaHasta ?? '','cuenta'=>$filtroCuenta,'centro'=>$filtroCentro,'debcred'=>$filtroDebCred,'categoria'=>$filtroCategoria ?? '','llave'=>$filtroLlave ?? '']))) ?>" class="btn btn-outline-success btn-sm" title="Descargar Excel">
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

    <?php
        $baseUrl = 'conciliaciones/bancaria?anio='.$anioActual.'&rango='.$rangoActual
            .($filtroCuenta ? '&cuenta='.$filtroCuenta : '')
            .($fechaDesde ? '&desde='.$fechaDesde : '')
            .($fechaHasta ? '&hasta='.$fechaHasta : '');
        // URL con debcred fijo (para categorías)
        $baseUrlConDebCred = $baseUrl.($filtroDebCred ? '&debcred='.$filtroDebCred : '');
        // URL con debcred + categoría fijo (para llave items)
        $baseUrlConCategoria = $baseUrlConDebCred.($filtroCategoria ? '&categoria='.$filtroCategoria : '');
    ?>

    <!-- ═══ FILA 1: INGRESO / EGRESO ═══ -->
    <div class="d-flex gap-2 mb-2 flex-wrap align-items-center">
        <?php foreach ($resumenDebCred as $rdc): ?>
        <a href="<?= base_url($baseUrl.'&debcred='.$rdc['deb_cred']) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="<?= $rdc['deb_cred'] === 'INGRESO' ? 'Total de ingresos bancarios en el período seleccionado. Clic para filtrar solo ingresos.' : 'Total de egresos bancarios en el período seleccionado. Clic para filtrar solo egresos.' ?>">
            <div class="card <?= ($filtroDebCred ?? '') === $rdc['deb_cred'] ? ($rdc['deb_cred'] === 'INGRESO' ? 'bg-success text-white' : 'bg-danger text-white') : ($rdc['deb_cred'] === 'INGRESO' ? 'border-success' : 'border-danger') ?>" style="cursor:pointer; min-width:140px;">
                <div class="card-body py-2 px-3 text-center">
                    <small class="fw-bold" style="font-size:0.85rem;"><?= esc($rdc['deb_cred']) ?></small>
                    <p class="fw-bold mb-0" style="font-size:1.1rem;">$<?= number_format(abs((float)$rdc['total_valor']), 0, ',', '.') ?></p>
                    <small class="<?= ($filtroDebCred ?? '') === $rdc['deb_cred'] ? 'text-white-50' : 'text-muted' ?>"><?= $rdc['movimientos'] ?> mov.</small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <?php
            $totalIngreso = 0; $totalEgreso = 0;
            foreach ($resumenDebCred as $rdc) {
                if ($rdc['deb_cred'] === 'INGRESO') $totalIngreso = abs((float)$rdc['total_valor']);
                if ($rdc['deb_cred'] === 'EGRESO')  $totalEgreso  = abs((float)$rdc['total_valor']);
            }
            $netoPeriodo = $totalIngreso - $totalEgreso;
        ?>
        <div data-bs-toggle="tooltip" title="Resultado neto del período = Ingresos - Egresos. Indica si el período fue positivo (ganancia) o negativo (pérdida).">
            <div class="card <?= $netoPeriodo >= 0 ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' ?>" style="min-width:140px;">
                <div class="card-body py-2 px-3 text-center">
                    <small class="fw-bold" style="font-size:0.85rem;">NETO PERÍODO</small>
                    <p class="fw-bold mb-0 <?= $netoPeriodo >= 0 ? 'text-success' : 'text-danger' ?>" style="font-size:1.1rem;">$<?= number_format($netoPeriodo, 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $netoPeriodo >= 0 ? 'superávit' : 'déficit' ?></small>
                </div>
            </div>
        </div>

        <span class="border-start mx-1" style="height:40px;"></span>

        <a href="<?= base_url($baseUrl) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Quitar todos los filtros de tipo, centro, categoría y llave. Muestra todos los movimientos.">
            <div class="card <?= empty($filtroCentro) && empty($filtroDebCred) && empty($filtroCategoria) && empty($filtroLlave) ? 'bg-dark text-white' : 'border-dark' ?>" style="cursor:pointer; min-width:70px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small>Todos</small>
                    <p class="fw-bold mb-0"><?= number_format(count($registros)) ?></p>
                </div>
            </div>
        </a>
        <?php foreach ($resumenCentros as $rcc): ?>
        <a href="<?= base_url($baseUrl.'&centro='.$rcc['id_centro_costo']) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Centro de costo: <?= esc($rcc['centro_costo']) ?>. Suma neta de ingresos y egresos (<?= $rcc['movimientos'] ?> movimientos). Clic para filtrar.">
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

    <!-- ═══ FILA 2: CATEGORÍAS (desde tbl_clasificacion_costos) ═══ -->
    <?php if (!empty($resumenCategorias)): ?>
    <div class="mb-2">
        <div class="d-flex align-items-center gap-2 mb-1">
            <small class="text-muted fw-bold"><i class="bi bi-diagram-3"></i> Categorías</small>
            <?php if ($filtroCategoria): ?>
                <a href="<?= base_url($baseUrlConDebCred) ?>" class="badge bg-secondary text-decoration-none">
                    <i class="bi bi-x"></i> Quitar categoría
                </a>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach ($resumenCategorias as $rc):
                $isActive = ($filtroCategoria ?? '') === $rc['categoria'];
                $tipoColor = match($rc['tipo'] ?? '') {
                    'ingreso'  => 'success',
                    'fijo'     => 'primary',
                    'variable' => 'warning',
                    'neutro'   => 'secondary',
                    default    => 'secondary',
                };
            ?>
            <a href="<?= base_url($baseUrlConDebCred.'&categoria='.urlencode($rc['categoria'])) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Categoría: <?= esc($rc['categoria']) ?> (tipo <?= esc($rc['tipo'] ?? '') ?>). Agrupa <?= $rc['movimientos'] ?> movimientos por $<?= number_format(abs((float)$rc['total_valor']), 0, ',', '.') ?>. Clic para ver sus llave items.">
                <div class="card <?= $isActive ? 'bg-'.$tipoColor.' text-white border-'.$tipoColor : 'border-'.$tipoColor ?>" style="cursor:pointer; min-width:110px;">
                    <div class="card-body py-1 px-2 text-center" style="font-size:0.78rem;">
                        <small class="fw-bold"><?= esc($rc['categoria']) ?></small>
                        <span class="badge bg-<?= $tipoColor ?> <?= $isActive ? 'bg-opacity-75' : '' ?>" style="font-size:0.6rem;"><?= esc($rc['tipo'] ?? '') ?></span>
                        <p class="fw-bold mb-0 <?= !$isActive ? ((float)$rc['total_valor'] >= 0 ? 'text-success' : 'text-danger') : '' ?>">
                            $<?= number_format(abs((float)$rc['total_valor']), 0, ',', '.') ?>
                        </p>
                        <small class="<?= $isActive ? 'text-white-50' : 'text-muted' ?>"><?= $rc['movimientos'] ?> mov.</small>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ FILA 3: LLAVE ITEMS (drill-down de categoría seleccionada) ═══ -->
    <?php if (!empty($resumenLlaveItems)): ?>
    <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <small class="text-muted fw-bold"><i class="bi bi-tag"></i> Llave Items de: <span class="text-dark"><?= esc($filtroCategoria) ?></span></small>
            <?php if ($filtroLlave): ?>
                <a href="<?= base_url($baseUrlConCategoria) ?>" class="badge bg-secondary text-decoration-none">
                    <i class="bi bi-x"></i> Quitar llave
                </a>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach ($resumenLlaveItems as $rl):
                $isActive = ($filtroLlave ?? '') === $rl['llave_item'];
            ?>
            <a href="<?= base_url($baseUrlConCategoria.'&llave='.urlencode($rl['llave_item'])) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Llave item: <?= esc($rl['llave_item']) ?>. <?= $rl['movimientos'] ?> movimientos por $<?= number_format(abs((float)$rl['total_valor']), 0, ',', '.') ?>. Clic para filtrar la tabla.">
                <div class="card <?= $isActive ? 'bg-dark text-white' : 'border-dark' ?>" style="cursor:pointer; min-width:100px;">
                    <div class="card-body py-1 px-2 text-center" style="font-size:0.75rem;">
                        <small class="fw-bold"><?= esc($rl['llave_item']) ?></small>
                        <span class="badge bg-dark <?= $isActive ? 'bg-opacity-50' : '' ?>" style="font-size:0.6rem;"><?= $rl['movimientos'] ?></span>
                        <p class="fw-bold mb-0 <?= !$isActive ? ((float)$rl['total_valor'] >= 0 ? 'text-success' : 'text-danger') : '' ?>">
                            $<?= number_format(abs((float)$rl['total_valor']), 0, ',', '.') ?>
                        </p>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

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
                <td data-order="<?= $r['fecha_sistema'] ?? '' ?>"><?= $r['fecha_sistema'] ? date('d/m/Y', strtotime($r['fecha_sistema'])) : '' ?></td>
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
    // Activar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

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
