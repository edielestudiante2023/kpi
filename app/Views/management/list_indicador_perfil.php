<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Indicadores por Perfil – Kpi Cycloid</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- DataTables Bootstrap5 CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        #indicadorPerfilTable tbody tr {
            height: 30px !important;
        }

        #indicadorPerfilTable td {
            padding-top: 0;
            padding-bottom: 0;
            line-height: 30px;
        }
    </style>
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <?= view('components/back_to_dashboard') ?>
                <h1 class="h4 mb-0">Listado de Indicadores por Perfil</h1>
            </div>
            <div>
                <a href="<?= base_url('indicadores_perfil/add') ?>" class="btn btn-primary me-2">+ Asignar Indicador</a>
                <!-- Botón de exportar a Excel -->
                <button id="btnExportExcel" class="btn btn-success">Descargar Excel</button>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <table id="indicadorPerfilTable" class="table table-bordered table-striped" style="table-layout: fixed; width:100%">
            <thead class="table-dark">
                <tr>
                    <th>Área</th>
                    <th>Cargo</th>
                    <th>Indicador</th>
                    <th>Periodicidad</th>
                    <th>Meta Valor</th>
                    <th>Meta Descripción</th>
                    <th>Ponderación (%)</th>
                    <th>Tipo de Meta</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>Área</th>
                    <th>Cargo</th>
                    <th>Indicador</th>
                    <th>Periodicidad</th>
                    <th>Meta Valor</th>
                    <th>Meta Descripción</th>
                    <th>Ponderación (%)</th>
                    <th>Tipo de Meta</th>
                    <th></th>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($indicadores_perfil as $item): ?>
                    <tr>
                        <td><?= esc($item['nombre_area']) ?></td>
                        <td><?= esc($item['nombre_cargo']) ?></td>
                        <td><?= esc($item['nombre_indicador']) ?></td>
                        <td><?= esc($item['periodicidad']) ?></td>
                        <td><?= esc($item['meta_valor']) ?></td>
                        <td><?= esc($item['meta_descripcion']) ?></td>
                        <td><?= esc($item['ponderacion']) ?></td>
                        <td><?= esc($item['tipo_meta']) ?></td>
                        <td class="text-center">
                            <a href="<?= base_url('indicadores_perfil/edit/' . $item['id_indicador_perfil']) ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="<?= base_url('indicadores_perfil/delete/' . $item['id_indicador_perfil']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Deseas eliminar esta asignación?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

    <script>
        $(function() {
            var table = $('#indicadorPerfilTable').DataTable({
                dom: 'Blfrtip', // Agregamos 'l' para el selector de longitud
                buttons: [{
                    extend: 'excelHtml5',
                    text: 'Descargar Excel',
                    className: 'd-none', // ocultamos el botón interno
                }],
                responsive: true,
                autoWidth: false,
                pageLength: 25, // Longitud por defecto (opcional)
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Todos"]
                ], // Opciones del selector
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                },
                initComplete: function() {
                    this.api().columns().every(function() {
                        var col = this;
                        var select = $('<select class="form-select form-select-sm"><option value="">Todos</option></select>')
                            .appendTo($(col.footer()).empty())
                            .on('change', function() {
                                col.search($.fn.dataTable.util.escapeRegex(this.value) ? '^' + this.value + '$' : '', true, false).draw();
                            });
                        col.data().unique().sort().each(function(d) {
                            if (d) select.append('<option value="' + d + '">' + d + '</option>');
                        });
                    });
                }
            });

            // Enlazar nuestro botón personalizado con el de DataTables
            $('#btnExportExcel').on('click', function() {
                table.button(0).trigger();
            });
        });
    </script>
</body>

</html>