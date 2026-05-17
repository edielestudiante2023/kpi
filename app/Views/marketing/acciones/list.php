<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diario de acciones – Marketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="bi bi-journal-text me-2"></i>Diario de acciones <small class="text-muted">Marketing</small></h1>
        <div>
            <a href="<?= base_url('marketing/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
            <a href="<?= base_url('marketing/acciones/nueva') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Registrar acción
            </a>
        </div>
    </div>
    <p class="text-muted small">Anota cada acción de marketing (post, evento, correo en frío, llamada). Sin esto, los números del dashboard no se pueden interpretar.</p>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-2">
            <label class="form-label small mb-1">Desde</label>
            <input type="date" name="desde" class="form-control form-control-sm" value="<?= esc($filtros['desde'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Hasta</label>
            <input type="date" name="hasta" class="form-control form-control-sm" value="<?= esc($filtros['hasta'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Tipo</label>
            <select name="tipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id_tipo_accion'] ?>" <?= ($filtros['id_tipo_accion'] ?? '') == $t['id_tipo_accion'] ? 'selected' : '' ?>>
                        <?= esc($t['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Responsable</label>
            <select name="responsable" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id_users'] ?>" <?= ($filtros['id_responsable'] ?? '') == $u['id_users'] ? 'selected' : '' ?>>
                        <?= esc($u['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-1">
            <button type="submit" class="btn btn-outline-secondary btn-sm flex-grow-1">Filtrar</button>
            <a href="<?= base_url('marketing/acciones') ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">Limpiar</a>
        </div>
    </form>

    <?php if (empty($acciones)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
            No hay acciones registradas para los filtros. Crea la primera con "Registrar acción".
        </div>
    <?php else: ?>
    <table id="tablaAcciones" class="table table-sm table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th class="text-end">Costo</th>
                <th class="text-center">Leads</th>
                <th>Responsable</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($acciones as $a): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($a['fecha'])) ?></td>
                <td><span class="badge" style="background-color: <?= esc($a['tipo_color']) ?>"><?= esc($a['tipo_nombre']) ?></span></td>
                <td><?= esc($a['descripcion']) ?></td>
                <td class="text-end"><?= $a['costo'] !== null ? '$' . number_format((float) $a['costo'], 0, ',', '.') : '—' ?></td>
                <td class="text-center"><?= $a['leads_generados'] !== null ? (int) $a['leads_generados'] : '—' ?></td>
                <td><small><?= esc($a['responsable_nombre'] ?? '—') ?></small></td>
                <td class="text-center text-nowrap">
                    <a href="<?= base_url('marketing/acciones/editar/' . $a['id_accion']) ?>"
                       class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                    <a href="<?= base_url('marketing/acciones/eliminar/' . $a['id_accion']) ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('¿Eliminar esta acción?')" title="Eliminar"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function() {
    if ($('#tablaAcciones tbody tr').length) {
        $('#tablaAcciones').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: 'Buscar:', lengthMenu: 'Mostrar _MENU_',
                info: '_START_–_END_ de _TOTAL_',
                paginate: { first: '«', last: '»', next: '›', previous: '‹' },
                zeroRecords: 'Sin coincidencias'
            }
        });
    }
});
</script>
</body>
</html>
