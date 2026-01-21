<!-- app/Views/superadmin/superadmindashboard.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Superadmin | KPI Cycloid</title>
    <?= $this->include('partials/pwa_head') ?>

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
        <h1 class="mb-4">Dashboard Superadmin Cycloid</h1>

        <!-- MODULO DE ACTIVIDADES - PRIMERO -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-kanban me-2"></i>Gestion de Actividades</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-success w-100">
                            <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-primary w-100">
                            <i class="bi bi-kanban me-1"></i> Tablero Kanban
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= base_url('actividades/responsable') ?>" class="btn btn-outline-primary w-100">
                            <i class="bi bi-people me-1"></i> Por Responsable
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= base_url('actividades/dashboard') ?>" class="btn btn-outline-info w-100">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- CONFIGURACION -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Configuracion</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="<?= base_url('preferencias/notificaciones') ?>" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-bell me-1"></i> Preferencias de Notificacion
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

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
    <?= $this->include('partials/pwa_scripts') ?>
</body>

</html>
