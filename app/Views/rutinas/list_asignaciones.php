<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignaciones de Rutina – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .form-switch .form-check-input { cursor: pointer; }
        .table tbody tr.row-selected { background-color: #fff3cd !important; }
        .bulk-bar { position: sticky; top: 0; z-index: 10; }
    </style>
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <?= view('components/back_to_dashboard') ?>
        <h1 class="h3 mb-0"><i class="bi bi-person-plus me-2"></i>Asignaciones de Rutina</h1>
        <span class="badge bg-secondary ms-2"><?= count($asignaciones) ?> registros</span>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario nueva asignación -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="bi bi-plus-lg me-1"></i> Nueva Asignacion (N usuarios x N actividades)</span>
            <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#nuevaAsignacion">
                <i class="bi bi-chevron-down"></i> Mostrar/Ocultar
            </button>
        </div>
        <div class="collapse show" id="nuevaAsignacion">
            <div class="card-body">
                <form method="post" action="<?= base_url('rutinas/asignaciones/add') ?>">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-bold">Usuarios (puede seleccionar varios)</label>
                            <div class="border rounded p-2" style="max-height:220px;overflow-y:auto;">
                                <div class="form-check mb-1 border-bottom pb-1">
                                    <input class="form-check-input" type="checkbox" id="selectAllUsers">
                                    <label class="form-check-label fw-bold" for="selectAllUsers">Seleccionar todos</label>
                                </div>
                                <?php foreach ($usuarios as $u): ?>
                                    <div class="form-check">
                                        <input class="form-check-input user-cb" type="checkbox"
                                               name="id_users[]" value="<?= $u['id_users'] ?>"
                                               id="usr_<?= $u['id_users'] ?>">
                                        <label class="form-check-label" for="usr_<?= $u['id_users'] ?>">
                                            <?= esc($u['nombre_completo']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-bold">Actividades (agrupadas por categoria)</label>
                            <div class="border rounded p-2" style="max-height:220px;overflow-y:auto;">
                                <div class="form-check mb-1 border-bottom pb-1">
                                    <input class="form-check-input" type="checkbox" id="selectAllActs">
                                    <label class="form-check-label fw-bold" for="selectAllActs">Seleccionar todas</label>
                                </div>
                                <?php
                                $porCat = [];
                                foreach ($actividades as $act) {
                                    $cat = $act['categoria'] ?? 'General';
                                    $porCat[$cat][] = $act;
                                }
                                ksort($porCat);
                                ?>
                                <?php foreach ($porCat as $cat => $acts): ?>
                                    <div class="mt-2"><strong class="text-muted small"><?= esc($cat) ?></strong></div>
                                    <?php foreach ($acts as $act): ?>
                                        <div class="form-check">
                                            <input class="form-check-input act-cb" type="checkbox"
                                                   name="actividades[]" value="<?= $act['id_actividad'] ?>"
                                                   id="act_<?= $act['id_actividad'] ?>">
                                            <label class="form-check-label" for="act_<?= $act['id_actividad'] ?>">
                                                <?= esc($act['nombre']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-lg me-1"></i>Asignar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filtros server-side -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Usuario</label>
                    <select name="usuario" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id_users'] ?>" <?= ($filtros['usuario'] ?? 0) == $u['id_users'] ? 'selected' : '' ?>>
                                <?= esc($u['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Categoria</label>
                    <select name="categoria" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= esc($c) ?>" <?= ($filtros['categoria'] ?? '') === $c ? 'selected' : '' ?>>
                                <?= esc($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Frecuencia</label>
                    <select name="frecuencia" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <option value="L-V" <?= ($filtros['frecuencia'] ?? '') === 'L-V' ? 'selected' : '' ?>>L-V</option>
                        <option value="diaria" <?= ($filtros['frecuencia'] ?? '') === 'diaria' ? 'selected' : '' ?>>Diaria</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Estado</label>
                    <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="1" <?= ($filtros['estado'] ?? '') === '1' ? 'selected' : '' ?>>Activas</option>
                        <option value="0" <?= ($filtros['estado'] ?? '') === '0' ? 'selected' : '' ?>>Inactivas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="<?= base_url('rutinas/asignaciones') ?>" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-eraser me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Barra de acciones masivas -->
    <div id="bulkBar" class="bulk-bar card shadow-sm mb-2 d-none">
        <div class="card-body py-2 bg-warning bg-opacity-25">
            <form id="frmBulk" method="post" action="<?= base_url('rutinas/asignaciones/bulk') ?>" class="d-flex align-items-center gap-2 flex-wrap">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" id="bulkAccion" value="">
                <span class="fw-bold"><i class="bi bi-check2-square me-1"></i> <span id="bulkCount">0</span> seleccionadas:</span>
                <button type="button" class="btn btn-sm btn-success" data-accion="activar">
                    <i class="bi bi-toggle-on me-1"></i> Activar
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-accion="desactivar">
                    <i class="bi bi-toggle-off me-1"></i> Desactivar
                </button>
                <button type="button" class="btn btn-sm btn-danger" data-accion="delete">
                    <i class="bi bi-trash me-1"></i> Eliminar
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="btnDeselect">
                    <i class="bi bi-x me-1"></i> Deseleccionar
                </button>
                <div id="bulkIds"></div>
            </form>
        </div>
    </div>

    <!-- Tabla de asignaciones -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table id="tblAsignaciones" class="table table-striped table-hover nowrap" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="selectAllRows" class="form-check-input"></th>
                        <th>Usuario</th>
                        <th>Categoria</th>
                        <th>Actividad</th>
                        <th>Frecuencia</th>
                        <th>Estado</th>
                        <th class="text-center" style="width:80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $colorCategoria = function($cat) {
                    $colors = ['Operativa'=>'primary','Comercial'=>'success','SST'=>'danger','Bitacora'=>'warning','Reportes'=>'info','General'=>'secondary'];
                    return $colors[$cat] ?? 'dark';
                };
                ?>
                <?php foreach ($asignaciones as $a): ?>
                    <tr data-id="<?= $a['id_asignacion'] ?>">
                        <td>
                            <input type="checkbox" class="form-check-input row-cb" value="<?= $a['id_asignacion'] ?>">
                        </td>
                        <td>
                            <strong><?= esc($a['nombre_completo']) ?></strong>
                            <br><small class="text-muted"><?= esc($a['correo']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $colorCategoria($a['categoria'] ?? 'General') ?>">
                                <?= esc($a['categoria'] ?? 'General') ?>
                            </span>
                        </td>
                        <td><?= esc($a['actividad_nombre']) ?></td>
                        <td>
                            <?php if (($a['frecuencia'] ?? '') === 'diaria'): ?>
                                <span class="badge bg-info" data-bs-toggle="tooltip" title="Diaria (incluye fines de semana)">📅 diaria</span>
                            <?php else: ?>
                                <span class="badge bg-info" data-bs-toggle="tooltip" title="Lunes a Viernes">📆 L-V</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input toggle-activa" type="checkbox"
                                       data-id="<?= $a['id_asignacion'] ?>"
                                       <?= $a['activa'] ? 'checked' : '' ?>>
                                <label class="form-check-label small">
                                    <?= $a['activa'] ? '<span class="text-success fw-bold">Activa</span>' : '<span class="text-muted">Inactiva</span>' ?>
                                </label>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('rutinas/asignaciones/delete/' . $a['id_asignacion']) ?>"
                               class="btn btn-sm btn-outline-danger"
                               data-bs-toggle="tooltip" title="Eliminar"
                               onclick="return confirm('Quitar esta asignacion?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

    // DataTable
    var table = $('#tblAsignaciones').DataTable({
        pageLength: 25,
        responsive: true,
        order: [[1, 'asc'], [2, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 5, 6] }
        ],
        language: {
            search: "Buscar en pagina:", lengthMenu: "Mostrar _MENU_",
            info: "_START_ a _END_ de _TOTAL_", emptyTable: "Sin asignaciones",
            paginate: { previous: "Ant", next: "Sig" }
        }
    });

    // === Form: select all users / actividades ===
    $('#selectAllUsers').on('change', function() {
        $('.user-cb').prop('checked', this.checked);
    });
    $('#selectAllActs').on('change', function() {
        $('.act-cb').prop('checked', this.checked);
    });

    // === Selección masiva en tabla ===
    function actualizarBulkBar() {
        var seleccionados = $('.row-cb:checked').length;
        if (seleccionados > 0) {
            $('#bulkBar').removeClass('d-none');
            $('#bulkCount').text(seleccionados);
        } else {
            $('#bulkBar').addClass('d-none');
        }
        // Resaltar filas seleccionadas
        $('.row-cb').each(function() {
            $(this).closest('tr').toggleClass('row-selected', this.checked);
        });
    }

    $(document).on('change', '.row-cb', actualizarBulkBar);

    $('#selectAllRows').on('change', function() {
        var checked = this.checked;
        // Solo afectar filas visibles en la página actual
        table.rows({ page: 'current' }).every(function() {
            $(this.node()).find('.row-cb').prop('checked', checked);
        });
        actualizarBulkBar();
    });

    $('#btnDeselect').on('click', function() {
        $('.row-cb').prop('checked', false);
        $('#selectAllRows').prop('checked', false);
        actualizarBulkBar();
    });

    // === Bulk submit ===
    $('#bulkBar [data-accion]').on('click', function() {
        var accion = $(this).data('accion');
        var ids = $('.row-cb:checked').map(function() { return this.value; }).get();
        if (ids.length === 0) return;

        var labels = { 'delete': 'eliminar', 'activar': 'activar', 'desactivar': 'desactivar' };
        if (!confirm('Esta seguro de ' + labels[accion] + ' ' + ids.length + ' asignacion(es)?')) return;

        $('#bulkAccion').val(accion);
        $('#bulkIds').empty();
        ids.forEach(function(id) {
            $('#bulkIds').append('<input type="hidden" name="ids[]" value="' + id + '">');
        });
        $('#frmBulk').submit();
    });

    // === Toggle inline activa/inactiva ===
    $(document).on('change', '.toggle-activa', function() {
        var $cb = $(this);
        var id = $cb.data('id');
        var $label = $cb.siblings('label');

        $cb.prop('disabled', true);

        $.post('<?= base_url('rutinas/asignaciones/toggle/') ?>' + id, {
            <?= csrf_token() ?>: '<?= csrf_hash() ?>'
        })
        .done(function(resp) {
            if (resp && resp.success) {
                if (resp.activa) {
                    $label.html('<span class="text-success fw-bold">Activa</span>');
                } else {
                    $label.html('<span class="text-muted">Inactiva</span>');
                }
            } else {
                alert('Error al actualizar');
                $cb.prop('checked', !$cb.prop('checked'));
            }
        })
        .fail(function() {
            alert('Error de conexion');
            $cb.prop('checked', !$cb.prop('checked'));
        })
        .always(function() {
            $cb.prop('disabled', false);
        });
    });
});
</script>
</body>
</html>
