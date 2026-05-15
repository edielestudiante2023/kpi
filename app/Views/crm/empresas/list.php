<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas – CRM – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><i class="bi bi-building me-2"></i>Empresas <small class="text-muted">CRM</small></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('crm/oportunidades/kanban') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-kanban me-1"></i> Pipeline
            </a>
            <a href="<?= base_url('crm/empresas/nueva') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nueva empresa
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

    <form method="get" class="mb-3">
        <div class="input-group input-group-sm" style="max-width: 480px;">
            <input type="text" name="busqueda" class="form-control"
                   placeholder="Buscar por razón social, NIT, email o ciudad…"
                   value="<?= esc($filtroBusqueda) ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($filtroBusqueda): ?>
                <a href="<?= base_url('crm/empresas') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($empresas)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            <?= $filtroBusqueda ? 'Ninguna empresa coincide con la búsqueda.' : 'No hay empresas registradas todavía.' ?>
        </div>
    <?php else: ?>
    <table id="tablaEmpresas" class="table table-sm table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Razón social</th>
                <th>NIT</th>
                <th>Sector</th>
                <th>Ciudad</th>
                <th>Responsable</th>
                <th>Fuente</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($empresas as $e): ?>
            <tr>
                <td>
                    <a href="<?= base_url('crm/empresas/ver/' . $e['id_empresa']) ?>" class="fw-bold text-decoration-none">
                        <?= esc($e['razon_social']) ?>
                    </a>
                    <?php if ((int) $e['activo'] === 0): ?>
                        <span class="badge bg-warning text-dark ms-1">Inactiva</span>
                    <?php endif; ?>
                </td>
                <td><?= esc($e['nit'] ?? '—') ?></td>
                <td><?= esc($e['sector'] ?? '—') ?></td>
                <td><?= esc($e['ciudad'] ?? '—') ?></td>
                <td><?= esc($e['responsable_nombre'] ?? '—') ?></td>
                <td><?= esc($e['fuente_nombre'] ?? '—') ?></td>
                <td class="text-center text-nowrap">
                    <a href="<?= base_url('crm/empresas/ver/' . $e['id_empresa']) ?>"
                       class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                    <a href="<?= base_url('crm/empresas/editar/' . $e['id_empresa']) ?>"
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
    if ($('#tablaEmpresas tbody tr').length) {
        $('#tablaEmpresas').DataTable({
            pageLength: 25,
            order: [[0, 'asc']],
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_',
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
