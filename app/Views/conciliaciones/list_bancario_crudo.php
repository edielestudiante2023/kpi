<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos Bancarios Crudos – Conciliaciones – Kpi Cycloid</title>
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
            <a href="<?= base_url('conciliaciones/cruda/bancario') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1 class="h3 mb-0">Movimientos Bancarios Crudos (<?= number_format(count($registros), 0, ',', '.') ?>)</h1>
        </div>
        <a href="<?= base_url('conciliaciones/cruda/bancario') ?>" class="btn btn-primary">
            <i class="bi bi-upload me-1"></i> Cargar CSV
        </a>
    </div>

    <table id="movCrudoTable" class="table table-striped table-hover nowrap" style="width:100%; font-size:0.85rem;">
        <thead class="table-dark">
            <tr>
                <th>Cuenta</th>
                <th>Fecha Sistema</th>
                <th>Documento</th>
                <th>Descripción</th>
                <th>Transacción</th>
                <th>Oficina</th>
                <th>NIT Origen</th>
                <th class="text-end">Valor Total</th>
                <th>Ref 1</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><select class="form-select form-select-sm"><option value="">Todas</option></select></th>
                <th></th>
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todas</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todas</option></select></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= esc($r['nombre_cuenta'] ?? '') ?></td>
                <td><?= date('d/m/Y', strtotime($r['fecha_sistema'])) ?></td>
                <td><?= esc($r['documento'] ?? '') ?></td>
                <td><?= esc(mb_substr($r['descripcion_motivo'] ?? '', 0, 50)) ?></td>
                <td><?= esc($r['transaccion'] ?? '') ?></td>
                <td><?= esc($r['oficina_recaudo'] ?? '') ?></td>
                <td><?= esc($r['id_origen_destino'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float)$r['valor_total'], 0, ',', '.') ?></td>
                <td><?= esc($r['referencia_1'] ?? '') ?></td>
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
    var filterCols = [0, 4, 5];
    var table = $('#movCrudoTable').DataTable({
        pageLength: 50, lengthMenu: [[50,100,200,-1],[50,100,200,'Todos']],
        responsive: true, autoWidth: false, order: [[1,'desc']],
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
