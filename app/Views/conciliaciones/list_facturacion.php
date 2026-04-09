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
                <select name="anio" class="form-select form-select-sm" style="width:120px;" onchange="this.form.submit()">
                    <option value="todos" <?= ($anioActual ?? '') === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <?php foreach ($anios as $a): ?>
                        <option value="<?= $a['anio'] ?>" <?= ($anioActual ?? '') == $a['anio'] ? 'selected' : '' ?>><?= $a['anio'] ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="<?= base_url('conciliaciones/facturacion/upload') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-upload me-1"></i> Cargar Excel
            </a>
        </div>
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
                <th class="text-end">Total</th>
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
                <th></th>
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
                <td class="text-end"><?= number_format((float)$r['total'], 0, ',', '.') ?></td>
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
    var filterCols = [0, 1, 2, 8, 11, 12]; // columnas con filtro select
    var table = $('#facturacionTable').DataTable({
        pageLength: 50,
        lengthMenu: [[50, 100, 200, -1], [50, 100, 200, 'Todos']],
        responsive: true,
        autoWidth: false,
        order: [[1, 'desc'], [2, 'desc']],
        initComplete: function () {
            this.api().columns(filterCols).every(function () {
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
