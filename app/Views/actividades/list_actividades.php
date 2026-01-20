<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Actividades - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        .badge-estado { padding: 0.3rem 0.6rem; }
        .estado-pendiente { background-color: #6c757d; }
        .estado-en_progreso { background-color: #0d6efd; }
        .estado-en_revision { background-color: #6f42c1; }
        .estado-completada { background-color: #198754; }
        .estado-cancelada { background-color: #dc3545; }

        .prioridad-urgente { background-color: #dc3545; }
        .prioridad-alta { background-color: #fd7e14; }
        .prioridad-media { background-color: #ffc107; color: #212529; }
        .prioridad-baja { background-color: #198754; }

        .fecha-vencida { color: #dc3545; font-weight: 600; }
        thead { background-color: #052c65; color: white; }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <!-- Usuario en sesion -->
        <div class="text-end mb-2">
            <span class="badge bg-primary fs-6">
                <i class="bi bi-person-circle me-1"></i>
                <?= esc(session()->get('nombre_completo')) ?>
            </span>
        </div>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <?= view('components/back_to_dashboard') ?>
                <h1 class="h3 mb-0"><i class="bi bi-table me-2"></i>Lista de Actividades</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-kanban me-1"></i> Ver Tablero
                </a>
                <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
                </a>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabla -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaActividades" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Titulo</th>
                                <th>Categoria</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Asignado a</th>
                                <th>Creado por</th>
                                <th>Fecha Limite</th>
                                <th>Creacion</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tfoot class="table-light">
                            <tr>
                                <th></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Buscar..."></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php foreach ($actividades as $act): ?>
                                <tr>
                                    <td><code><?= esc($act['codigo']) ?></code></td>
                                    <td>
                                        <a href="<?= base_url('actividades/ver/' . $act['id_actividad']) ?>" class="text-decoration-none">
                                            <?= esc($act['titulo']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($act['nombre_categoria']): ?>
                                            <span class="badge" style="background-color: <?= $act['color_categoria'] ?>;">
                                                <?= esc($act['nombre_categoria']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-estado estado-<?= $act['estado'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $act['estado'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge prioridad-<?= $act['prioridad'] ?>">
                                            <?= ucfirst($act['prioridad']) ?>
                                        </span>
                                    </td>
                                    <td><?= $act['nombre_asignado'] ? esc($act['nombre_asignado']) : '<span class="text-muted">Sin asignar</span>' ?></td>
                                    <td><?= esc($act['nombre_creador']) ?></td>
                                    <td>
                                        <?php if ($act['fecha_limite']): ?>
                                            <?php
                                            $dias = $act['dias_restantes'] ?? 0;
                                            $clase = ($dias < 0 && !in_array($act['estado'], ['completada', 'cancelada'])) ? 'fecha-vencida' : '';
                                            ?>
                                            <span class="<?= $clase ?>">
                                                <?= date('d/m/Y', strtotime($act['fecha_limite'])) ?>
                                                <?php if ($dias < 0 && !in_array($act['estado'], ['completada', 'cancelada'])): ?>
                                                    <i class="bi bi-exclamation-circle" title="Vencida"></i>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($act['fecha_creacion'])) ?></td>
                                    <td>
                                        <?php
                                        // Solo puede editar/eliminar: el creador o superadmin (rol_id = 1)
                                        $puedeEditar = (session()->get('id_users') == $act['id_creador'])
                                                       || (session()->get('rol_id') == 1);
                                        ?>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('actividades/ver/' . $act['id_actividad']) ?>"
                                               class="btn btn-outline-primary" title="Ver">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($puedeEditar): ?>
                                                <a href="<?= base_url('actividades/editar/' . $act['id_actividad']) ?>"
                                                   class="btn btn-outline-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?= base_url('actividades/eliminar/' . $act['id_actividad']) ?>"
                                                   class="btn btn-outline-danger" title="Eliminar"
                                                   onclick="return confirm('¿Seguro que deseas eliminar esta actividad?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#tablaActividades').DataTable({
                dom: 'Blfrtip',
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel',
                    className: 'btn btn-success btn-sm',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                }],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
                initComplete: function() {
                    var api = this.api();

                    // Filtro por columna de titulo (1)
                    $('input', api.column(1).footer()).on('keyup change clear', function() {
                        api.column(1).search(this.value).draw();
                    });

                    // Selectores para Estado (3), Prioridad (4), Asignado (5)
                    [3, 4, 5].forEach(function(colIdx) {
                        var column = api.column(colIdx);
                        var footer = $(column.footer());
                        footer.empty();
                        var select = $('<select class="form-select form-select-sm"><option value="">Todos</option></select>')
                            .appendTo(footer)
                            .on('change', function() {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                column.search(val ? '^' + val + '$' : '', true, false).draw();
                            });

                        column.data().unique().sort().each(function(d) {
                            var text = $('<div>').html(d).text().trim();
                            if (text && text !== '-') {
                                select.append('<option value="' + text + '">' + text + '</option>');
                            }
                        });
                    });
                }
            });
        });
    </script>
</body>
</html>
