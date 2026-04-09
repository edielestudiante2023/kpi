<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deudas – Conciliaciones – Kpi Cycloid</title>
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
            <h1 class="h3 mb-0">Deudas / Obligaciones</h1>
        </div>
        <a href="<?= base_url('conciliaciones/deudas/add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva Deuda
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Deuda Activa</h6>
                    <p class="display-6 fw-bold text-danger">$<?= number_format($totalDeuda ?? 0, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Abonado</h6>
                    <p class="display-6 fw-bold text-success">$<?= number_format($totalAbonado ?? 0, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h6 class="card-title text-muted">Saldo Pendiente</h6>
                    <p class="display-6 fw-bold text-warning">$<?= number_format($totalSaldo ?? 0, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
    </div>

    <table id="deudasTable" class="table table-striped table-hover nowrap" style="width:100%; font-size:0.9rem;">
        <thead class="table-dark">
            <tr>
                <th>Concepto</th>
                <th>Acreedor</th>
                <th class="text-end">Monto Original</th>
                <th class="text-end">Abonado</th>
                <th class="text-end">Saldo</th>
                <th>Registro</th>
                <th>Vencimiento</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th></th>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($deudas as $d): ?>
            <tr>
                <td><a href="<?= base_url('conciliaciones/deudas/ver/'.$d['id_deuda']) ?>"><?= esc($d['concepto']) ?></a></td>
                <td><?= esc($d['acreedor']) ?></td>
                <td class="text-end">$<?= number_format((float)$d['monto_original'], 0, ',', '.') ?></td>
                <td class="text-end text-success">$<?= number_format((float)$d['total_abonado'], 0, ',', '.') ?></td>
                <td class="text-end <?= $d['saldo_pendiente'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                    $<?= number_format((float)$d['saldo_pendiente'], 0, ',', '.') ?>
                </td>
                <td><?= date('d/m/Y', strtotime($d['fecha_registro'])) ?></td>
                <td><?= $d['fecha_vencimiento'] ? date('d/m/Y', strtotime($d['fecha_vencimiento'])) : '' ?></td>
                <td>
                    <?= $d['estado'] === 'activa'
                        ? '<span class="badge bg-danger">Activa</span>'
                        : '<span class="badge bg-success">Saldada</span>'
                    ?>
                </td>
                <td class="text-center">
                    <a href="<?= base_url('conciliaciones/deudas/ver/'.$d['id_deuda']) ?>" class="btn btn-sm btn-info me-1" title="Ver abonos">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= base_url('conciliaciones/deudas/edit/'.$d['id_deuda']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                    <a href="<?= base_url('conciliaciones/deudas/delete/'.$d['id_deuda']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta deuda y todos sus abonos?')">Eliminar</a>
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
    var filterCols = [1, 7];
    var table = $('#deudasTable').DataTable({
        pageLength: 20, responsive: true, autoWidth: false,
        order: [[7, 'asc'], [6, 'asc']],
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
