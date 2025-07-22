<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios – Afilogro</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Responsive CSS -->
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .container-fluid {
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: #fff;
        }

        .table-container {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }

        .dataTables_scrollBody {
            overflow: auto;
        }
    </style>
</head>

<body>

    <?= $this->include('partials/nav') ?>

    <div class="container-fluid">
        <!-- Encabezado y métricas fijos -->
        <div class="sticky-header">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-white">
                <h1 class="h3 m-0">Listado de Usuarios</h1>
                <a href="<?= base_url('users/add') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo Usuario
                </a>
            </div>

            <div class="row g-3 mb-0 px-3 pb-3 bg-white">
                <div class="col-md-4">
                    <div class="card text-white bg-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Usuarios</h5>
                            <p class="display-6 fw-bold"><?= esc($total_usuarios) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Roles</h5>
                            <p class="display-6 fw-bold"><?= esc($total_roles) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Áreas</h5>
                            <p class="display-6 fw-bold"><?= esc($total_areas) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla scrollable -->
        <div class="table-container p-3">
            <table id="userTable" class="table table-striped table-hover table-bordered nowrap display" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Correo</th>
                        <th>Cargo</th>
                        <th>Rol</th>
                        <th>Área</th>
                        <th>Jefe Inmediato</th>
                        <th>Perfil de Cargo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Correo</th>
                        <th>Cargo</th>
                        <th>Rol</th>
                        <th>Área</th>
                        <th>Jefe Inmediato</th>
                        <th>Perfil de Cargo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php if (! empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= esc($u['nombre_completo']) ?></td>
                                <td><?= esc($u['documento_identidad']) ?></td>
                                <td><?= esc($u['correo']) ?></td>
                                <td><?= esc($u['cargo']) ?></td>
                                <td><?= esc($u['rol_nombre'] ?? $u['id_roles']) ?></td>
                                <td><?= esc($u['area_nombre'] ?? $u['id_areas']) ?></td>
                                <td><?= esc($u['nombre_jefe'] ?? '—') ?></td>
                                <td><?= esc($u['perfil_nombre']) ?></td>
                                <td>
                                    <?php if ($u['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('users/edit/' . $u['id_users']) ?>" class="btn btn-sm btn-warning me-1" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= base_url('users/delete/' . $u['id_users']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar a <?= esc($u['nombre_completo']) ?>?')" title="Eliminar">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= $this->include('partials/logout') ?>

    <!-- JS Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.0/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

    <!-- DataTable Initialization -->
    <script>
        $(document).ready(function() {
            var table = $('#userTable').DataTable({
                dom: 'Blfrtip',
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Todos"]
                ],
                buttons: [{
                    extend: 'excelHtml5',
                    text: 'Exportar a Excel',
                    className: 'btn btn-success btn-sm'
                }],
                responsive: true,
                scrollX: true,
                scrollY: 'calc(100vh - 200px)',
                scrollCollapse: true,
                paging: true,
                autoWidth: false,
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    },
                    zeroRecords: "No se encontraron registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 usuarios",
                    infoFiltered: "(filtrado de _MAX_ total usuarios)"
                },
                initComplete: function() {
                    this.api().columns().every(function() {
                        var column = this;
                        $('<input type="text" class="form-control form-control-sm" placeholder="Buscar..." />')
                            .appendTo($(column.footer()).empty())
                            .on('keyup change clear', function() {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    });
                }
            });

            table.buttons().container().appendTo('#userTable_wrapper .col-md-6:eq(0)');

            table.on('length.dt', function(e, settings, len) {
                if (len === 100) {
                    $('.dataTables_scrollBody').css('max-height', 'none');
                    $('.table-container').css('flex', 'auto');
                } else {
                    $('.dataTables_scrollBody').css('max-height', 'calc(100vh - 200px)');
                    $('.table-container').css('flex', '1');
                }
            });
        });
    </script>

</body>

</html>
