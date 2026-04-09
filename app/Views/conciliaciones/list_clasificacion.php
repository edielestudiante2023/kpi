<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clasificación de Costos – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <?= view('components/back_to_dashboard') ?>
            <h1 class="h3 mb-0">Clasificación de Costos</h1>
        </div>
        <a href="<?= base_url('conciliaciones/clasificacion/add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva Clasificación
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <?php if (!empty($sinClasificar)): ?>
    <div class="alert alert-warning">
        <strong><i class="bi bi-exclamation-triangle me-1"></i><?= count($sinClasificar) ?> llave_items sin clasificar:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($sinClasificar as $s): ?>
                <li><strong><?= esc($s['llave_item']) ?></strong> (<?= $s['total'] ?> movimientos)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <table id="clasificacionTable" class="table table-striped table-hover nowrap" style="width:100%; font-size:0.9rem;">
        <thead class="table-dark">
            <tr>
                <th>Llave Item</th>
                <th>Categoría</th>
                <th>Tipo</th>
                <th class="text-end">Movimientos</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todas</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($clasificaciones as $c): ?>
            <tr>
                <td><?= esc($c['llave_item']) ?></td>
                <td><?= esc($c['categoria']) ?></td>
                <td>
                    <?php
                    $badges = ['fijo' => 'bg-primary', 'variable' => 'bg-warning text-dark', 'ingreso' => 'bg-success', 'neutro' => 'bg-secondary'];
                    ?>
                    <span class="badge <?= $badges[$c['tipo']] ?? 'bg-secondary' ?>"><?= strtoupper($c['tipo']) ?></span>
                </td>
                <td class="text-end"><?= number_format($c['total_movimientos']) ?></td>
                <td class="text-center">
                    <a href="<?= base_url('conciliaciones/clasificacion/edit/'.$c['id_clasificacion']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                    <a href="<?= base_url('conciliaciones/clasificacion/delete/'.$c['id_clasificacion']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                </td>
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
    var filterCols = [1, 2];
    var table = $('#clasificacionTable').DataTable({
        pageLength: 50, responsive: true, autoWidth: false,
        order: [[1, 'asc'], [0, 'asc']],
        initComplete: function() {
            this.api().columns(filterCols).every(function() {
                var column = this;
                var select = $('select', column.footer());
                column.data().unique().sort().each(function(d) {
                    var txt = $('<div>').html(d).text().trim();
                    if (txt.length && select.find('option[value="'+txt+'"]').length === 0)
                        select.append('<option value="'+txt+'">'+txt+'</option>');
                });
                select.on('change', function() {
                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                    column.search(val ? '^'+val+'$' : '', true, false).draw();
                });
            });
        },
        language: { search:"Buscar:", lengthMenu:"Mostrar _MENU_ registros",
            info:"Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate:{first:"Primero",last:"Último",next:"Siguiente",previous:"Anterior"},
            zeroRecords:"No se encontraron registros" }
    });
});
</script>
</body>
</html>
