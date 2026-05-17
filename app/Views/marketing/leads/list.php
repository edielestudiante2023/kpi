<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads – Marketing – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .badge-estado-nuevo      { background: #0dcaf0; color: #000; }
        .badge-estado-contactado { background: #fd7e14; color: #fff; }
        .badge-estado-calificado { background: #198754; color: #fff; }
        .badge-estado-descartado { background: #6c757d; color: #fff; }
    </style>
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="bi bi-people me-2"></i>Leads <small class="text-muted">Marketing</small></h1>
        <div>
            <a href="<?= base_url('marketing/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
            <a href="<?= base_url('marketing/leads/nuevo') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nuevo lead
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-2">
            <label class="form-label small mb-1">Estado</label>
            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach (['nuevo','contactado','calificado','descartado'] as $e): ?>
                    <option value="<?= $e ?>" <?= ($filtros['estado'] ?? '') === $e ? 'selected' : '' ?>>
                        <?= ucfirst($e) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Fuente</label>
            <select name="fuente" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php foreach ($fuentes as $f): ?>
                    <option value="<?= $f['id_fuente'] ?>" <?= ($filtros['id_fuente'] ?? '') == $f['id_fuente'] ? 'selected' : '' ?>>
                        <?= esc($f['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Responsable</label>
            <select name="responsable" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id_users'] ?>" <?= ($filtros['id_responsable'] ?? '') == $u['id_users'] ? 'selected' : '' ?>>
                        <?= esc($u['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Buscar</label>
            <div class="input-group input-group-sm">
                <input type="text" name="busqueda" class="form-control" placeholder="Nombre, empresa o email..."
                       value="<?= esc($filtros['busqueda'] ?? '') ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <a href="<?= base_url('marketing/leads') ?>" class="btn btn-outline-secondary btn-sm w-100">Limpiar</a>
        </div>
    </form>

    <?php if (empty($leads)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No hay leads para los filtros seleccionados.
        </div>
    <?php else: ?>
    <table id="tablaLeads" class="table table-sm table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Nombre</th>
                <th>Empresa</th>
                <th>Email</th>
                <th>Fuente</th>
                <th>Estado</th>
                <th>Responsable</th>
                <th>Creado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $l): ?>
            <tr>
                <td>
                    <a href="<?= base_url('marketing/leads/ver/' . $l['id_lead']) ?>" class="text-decoration-none fw-bold">
                        <?= esc($l['nombre']) ?>
                    </a>
                    <?php if (!empty($l['cargo'])): ?>
                        <div class="text-muted small"><?= esc($l['cargo']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= esc($l['empresa_text'] ?? '—') ?></td>
                <td><small><?= esc($l['email'] ?? '—') ?></small></td>
                <td><small><?= esc($l['fuente_nombre'] ?? '—') ?></small></td>
                <td><span class="badge badge-estado-<?= $l['estado'] ?>"><?= ucfirst($l['estado']) ?></span></td>
                <td><small><?= esc($l['responsable_nombre'] ?? '—') ?></small></td>
                <td><small><?= date('d/m/Y', strtotime($l['created_at'])) ?></small></td>
                <td class="text-center text-nowrap">
                    <a href="<?= base_url('marketing/leads/ver/' . $l['id_lead']) ?>"
                       class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                    <a href="<?= base_url('marketing/leads/editar/' . $l['id_lead']) ?>"
                       class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
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
    if ($('#tablaLeads tbody tr').length) {
        $('#tablaLeads').DataTable({
            pageLength: 25,
            order: [[6, 'desc']],
            language: {
                search: 'Buscar en página:', lengthMenu: 'Mostrar _MENU_',
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
