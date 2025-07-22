<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Perfiles – Kpi Cycloid</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Buttons -->
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Listado de Perfiles</h1>
            <button id="excelExportBtn" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
            </button>
            <a href="<?= base_url('perfiles/add') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Perfil
            </a>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="perfilTable" class="table table-striped table-bordered display nowrap" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre Cargo</th>
                        <th>Área</th>
                        <th>Cargo Jefe Inmediato</th>
                        <th>Colaboradores</th>
                        <th>Creado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><input type="text" placeholder="Filtrar Nombre" class="form-control form-control-sm" /></th>
                        <th><input type="text" placeholder="Filtrar Área" class="form-control form-control-sm" /></th>
                        <th><input type="text" placeholder="Filtrar Jefe" class="form-control form-control-sm" /></th>
                        <th><input type="text" placeholder="Filtrar Colaboradores" class="form-control form-control-sm" /></th>
                        <th><input type="text" placeholder="Filtrar Fecha" class="form-control form-control-sm" /></th>
                        <th><input type="text" placeholder="Filtrar Acciones" class="form-control form-control-sm" /></th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php foreach ($perfiles as $p): ?>
                        <tr>
                            <td><?= esc($p['nombre_cargo']) ?></td>
                            <td><?= esc($p['area']) ?></td>
                            <td><?= esc($p['jefe_inmediato']) ?></td>
                            <td><?= esc($p['colaboradores_a_cargo']) ?></td>
                            <td><?= esc($p['created_at']) ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('perfiles/edit/' . $p['id_perfil_cargo']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                                <a href="<?= base_url('perfiles/delete/' . $p['id_perfil_cargo']) ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('¿Eliminar este perfil?')">
                                    Eliminar
                                </a>
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

    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#perfilTable').DataTable({
                responsive: true,
                autoWidth: false,
                dom: 'Blfrtip', // ← Aquí agregamos la 'l' para mostrar el selector de longitud
                pageLength: 25, // ← Cambia este valor inicial si quieres
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Todos"]
                ],
                buttons: [{
                    extend: 'excelHtml5',
                    text: '📥 Descargar Excel',
                    className: 'd-none',
                    title: 'Listado de Perfiles',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4],
                        modifier: {
                            page: 'all'
                        }
                    }
                }],
                initComplete: function() {
                    this.api().columns().every(function() {
                        var column = this;
                        $('input', this.footer()).on('keyup change clear', function() {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                    });
                }
            });

            // Disparar el botón de Excel al hacer click en el personalizado
            $('#excelExportBtn').on('click', function() {
                var oldLength = table.page.len(); // Guardamos el valor original
                table.page.len(-1).draw(); // Mostramos todos los registros
                setTimeout(function() {
                    table.button('.buttons-excel').trigger(); // Exportamos
                    table.page.len(oldLength).draw(); // Restauramos la paginación original
                }, 500); // Un pequeño delay para asegurar que la tabla se redibuje antes de exportar
            });
        });
    </script>
</body>

</html>