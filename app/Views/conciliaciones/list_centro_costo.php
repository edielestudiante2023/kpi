<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centros de Costo – Conciliaciones – Kpi Cycloid</title>
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
            <h1 class="h3 mb-0">Centros de Costo</h1>
        </div>
        <a href="<?= base_url('conciliaciones/centros-costo/add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Centro de Costo
        </a>
    </div>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <table id="centrosTable" class="table table-striped table-hover nowrap" style="width:100%">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Centro de Costo</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($centros as $c): ?>
            <tr>
                <td><?= esc($c['id_centro_costo']) ?></td>
                <td><?= esc($c['centro_costo']) ?></td>
                <td class="text-center">
                    <a href="<?= base_url('conciliaciones/centros-costo/edit/'.$c['id_centro_costo']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                    <a href="<?= base_url('conciliaciones/centros-costo/delete/'.$c['id_centro_costo']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este centro de costo?')">Eliminar</a>
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
    $('#centrosTable').DataTable({
        pageLength: 20,
        responsive: true,
        autoWidth: false,
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
