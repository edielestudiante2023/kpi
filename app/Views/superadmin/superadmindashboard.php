<!-- app/Views/superadmin/superadmindashboard.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Superadmin – Kpi Cycloid</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Responsive CSS -->
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <h1 class="mb-4">Dashboard Superadmin</h1>

        <!-- Tabla de accesos por rol -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Accesos por Rol</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaAccesos" class="table table-striped table-hover mb-0 nowrap" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Rol</th>
                                <th>Detalle</th>
                                <th>Enlace</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Rol</th>
                                <th>Detalle</th>
                                <th></th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php foreach ($accesos as $a): ?>
                                <tr>
                                    <td><?= esc($a['nombre_rol']) ?></td>
                                    <td><?= esc($a['detalle']) ?></td>
                                    <td class="text-center">
                                        <a href="<?= base_url($a['enlace']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                            Abrir
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $a['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($a['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('accesosrol/edit/' . $a['id_acceso']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                                        <a href="<?= base_url('accesosrol/delete/' . $a['id_acceso']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este acceso?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <?= $this->include('partials/logout') ?>

    <!-- Scripts: jQuery, Bootstrap, DataTables, Responsive -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#tablaAccesos').DataTable({
                pageLength: 20,
                lengthMenu: [ [20, 50, 100], [20, 50, 100] ],
                responsive: true,
                autoWidth: false,
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
                    zeroRecords: "No se encontraron registros coincidentes"
                },
                initComplete: function() {
                    var api = this.api();

                    // Columnas a filtrar por select: Rol (0), Detalle (1), Estado (3)
                    [0, 1, 3].forEach(function(colIdx) {
                        var column = api.column(colIdx);
                        var select = $('<select class="form-select form-select-sm"><option value="">Todos</option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function() {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                column.search(val ? '^' + val + '$' : '', true, false).draw();
                            });

                        column.data().unique().sort().each(function(d) {
                            // Extraer texto si viene HTML
                            var text = $('<div>').html(d).text();
                            select.append('<option value="' + text + '">' + text + '</option>');
                        });
                    });
                }
            });
        });
    </script>
</body>

</html>
