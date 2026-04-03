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
        <a href="<?= base_url('conciliaciones/bancaria/upload') ?>" class="btn btn-primary">
            <i class="bi bi-upload me-1"></i> Cargar Excel
        </a>
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
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todas</option></select></th>
                <th></th>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= esc($r['nombre_cuenta'] ?? '') ?></td>
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
    var filterCols = [0, 1, 2, 3, 6, 7, 8, 11];
    var table = $('#conciliacionTable').DataTable({
        pageLength: 50,
        lengthMenu: [[50, 100, 200, -1], [50, 100, 200, 'Todos']],
        responsive: true,
        autoWidth: false,
        order: [[9, 'desc']],
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
