<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Equipos – Kpi Cycloid</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Responsive -->
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        tfoot input {
            width: 100%;
            box-sizing: border-box;
            padding: 3px;
        }

        /* Asegurar que los controles de DataTable se muestren correctamente */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin: 6px 0;
        }
    </style>
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Listado de Equipos</h1>
            <a href="<?= base_url('equipos/add') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Equipo
            </a>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="equipoTable" class="table table-striped table-hover table-bordered nowrap w-100">
                <thead class="table-dark">
                    <tr>
                        <th>Jefe</th>
                        <th>Subordinado</th>
                        <th>Fecha Asignación</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Jefe</th>
                        <th>Subordinado</th>
                        <th>Fecha Asignación</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php foreach ($equipos as $e): ?>
                        <tr>
                            <td><?= esc($e['jefe_nombre']) ?></td>
                            <td><?= esc($e['sub_nombre']) ?></td>
                            <td><?= esc($e['fecha_asignacion']) ?></td>
                            <td>
                                <?= $e['estado_relacion'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('equipos/edit/' . $e['id_equipos']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                                <a href="<?= base_url('equipos/delete/' . $e['id_equipos']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta asignación?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= $this->include('partials/logout') ?>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

    <!-- DataTables Buttons and dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Activamos DataTable primero
            const table = $('#equipoTable').DataTable({
                // Configuración del DOM - orden de elementos
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"B>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',

                // Configuración de botones
                buttons: [{
                    extend: 'excelHtml5',
                    text: 'Exportar a Excel',
                    titleAttr: 'Exportar a Excel',
                    className: 'btn btn-success btn-sm mb-3',
                    exportOptions: {
                        columns: ':visible:not(:last-child)'
                    }
                }],

                // Configuración de paginación y longitud
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Todos"]
                ],

                // Configuración responsive
                responsive: true,
                autoWidth: false,

                // Configuración de idioma en español
                language: {
                    lengthMenu: "Mostrar _MENU_ registros por página",
                    zeroRecords: "No se encontraron resultados",
                    info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    infoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
                    infoFiltered: "(filtrado de un total de _MAX_ registros)",
                    search: "Buscar:",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                }
            });

            // Después de inicializar la tabla, configuramos los filtros del footer
            $('#equipoTable tfoot th').each(function(index) {
                const title = $(this).text();
                if (title && title !== 'Acciones' && title !== '') {
                    $(this).html('<input type="text" placeholder="Filtrar ' + title + '" class="form-control form-control-sm" />');
                } else {
                    $(this).html('');
                }
            });

            // Aplicamos búsqueda por columna
            table.columns().every(function(index) {
                const that = this;
                $('input', this.footer()).on('keyup change clear', function() {
                    if (that.search() !== this.value) {
                        that.search(this.value).draw();
                    }
                });
            });
        });
    </script>
</body>

</html>