<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Áreas – Kpi Cycloid</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Responsive CSS -->
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Listado de Áreas</h1>
        <a href="<?= base_url('areas/add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva Área
        </a>
    </div>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <table id="areasTable" class="table table-striped table-hover nowrap" style="width:100%">
        <thead class="table-dark">
            <tr>
                <th>Nombre Área</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>
                    <select class="form-select form-select-sm">
                        <option value="">Todas</option>
                    </select>
                </th>
                <th>
                    <select class="form-select form-select-sm">
                        <option value="">Todas</option>
                    </select>
                </th>
                <th>
                    <select class="form-select form-select-sm">
                        <option value="">Todas</option>
                    </select>
                </th>
                <th></th>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($areas as $a): ?>
            <tr>
                <td><?= esc($a['nombre_area']) ?></td>
                <td><?= esc($a['descripcion_area']) ?></td>
                <td>
                    <?= $a['estado_area']
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-secondary">Inactivo</span>'
                    ?>
                </td>
                <td class="text-center">
                    <a href="<?= base_url('areas/edit/'.$a['id_areas']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                    <a href="<?= base_url('areas/delete/'.$a['id_areas']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta área?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->include('partials/logout') ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#areasTable').DataTable({
        pageLength: 20,
        lengthMenu: [[20, 50, 100], [20, 50, 100]],
        responsive: true,
        autoWidth: false,
        initComplete: function () {
            // Para cada columna 0, 1 y 2, crear filtro tipo select
            this.api().columns([0,1,2]).every(function () {
                var column = this;
                var select = $('select', column.footer());
                column.data().unique().sort().each(function (d) {
                    // Obtener texto plano si hay HTML
                    var txt = $('<div>').html(d).text().trim();
                    if (txt.length && select.find('option[value="'+txt+'"]').length === 0) {
                        select.append('<option value="'+txt+'">'+txt+'</option>');
                    }
                });
                // Evento change para filtrar
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
            paginate: {
                first:    "Primero",
                last:     "Último",
                next:     "Siguiente",
                previous: "Anterior"
            },
            zeroRecords: "No se encontraron registros"
        }
    });
});
</script>
</body>
</html>
