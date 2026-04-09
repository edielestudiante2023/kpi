<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas de Banco – Conciliaciones – Kpi Cycloid</title>
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
            <h1 class="h3 mb-0">Cuentas de Banco</h1>
        </div>
        <a href="<?= base_url('conciliaciones/cuentas-banco/add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva Cuenta
        </a>
    </div>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <table id="cuentasTable" class="table table-striped table-hover nowrap" style="width:100%">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre Cuenta</th>
                <th class="text-end">Saldo Inicial</th>
                <th>Fecha Saldo</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($cuentas as $c): ?>
            <tr>
                <td><?= esc($c['id_cuenta_banco']) ?></td>
                <td><?= esc($c['nombre_cuenta']) ?></td>
                <td class="text-end">$<?= number_format((float)$c['saldo_inicial'], 0, ',', '.') ?></td>
                <td><?= $c['fecha_saldo_inicial'] ? date('d/m/Y', strtotime($c['fecha_saldo_inicial'])) : '' ?></td>
                <td class="text-center">
                    <a href="<?= base_url('conciliaciones/cuentas-banco/edit/'.$c['id_cuenta_banco']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                    <a href="<?= base_url('conciliaciones/cuentas-banco/delete/'.$c['id_cuenta_banco']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta cuenta?')">Eliminar</a>
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
    $('#cuentasTable').DataTable({
        pageLength: 20, responsive: true, autoWidth: false,
        language: {
            search: "Buscar:", lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
            zeroRecords: "No se encontraron registros"
        }
    });
});
</script>
</body>
</html>
