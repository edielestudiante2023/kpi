<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oportunidades – CRM – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-list-ul me-2"></i>Oportunidades (lista)</h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('crm/oportunidades/kanban') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-kanban me-1"></i> Pipeline
            </a>
            <a href="<?= base_url('crm/oportunidades/nueva') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nueva
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label small mb-1">Etapa</label>
            <select name="etapa" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php foreach ($etapas as $e): ?>
                    <option value="<?= $e['id_etapa'] ?>" <?= $filtroEtapa == $e['id_etapa'] ? 'selected' : '' ?>>
                        <?= esc($e['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Responsable</label>
            <select name="responsable" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($usuariosCrm as $u): ?>
                    <option value="<?= $u['id_users'] ?>" <?= $filtroResponsable == $u['id_users'] ? 'selected' : '' ?>>
                        <?= esc($u['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($filtroEtapa || $filtroResponsable): ?>
            <div class="col-md-2 d-flex align-items-end">
                <a href="<?= base_url('crm/oportunidades/lista') ?>" class="btn btn-outline-secondary btn-sm w-100">Limpiar</a>
            </div>
        <?php endif; ?>
    </form>

    <?php if (empty($oportunidades)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Sin oportunidades para los filtros seleccionados.
        </div>
    <?php else: ?>
    <table id="tablaOps" class="table table-sm table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Código</th>
                <th>Título</th>
                <th>Empresa</th>
                <th>Etapa</th>
                <th class="text-end">Valor</th>
                <th>Prob.</th>
                <th>Cierre est.</th>
                <th>Responsable</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($oportunidades as $o): ?>
            <tr>
                <td><a href="<?= base_url('crm/oportunidades/ver/' . $o['id_oportunidad']) ?>"><?= esc($o['codigo']) ?></a></td>
                <td><?= esc($o['titulo']) ?></td>
                <td><?= esc($o['empresa_nombre'] ?? '—') ?></td>
                <td><span class="badge" style="background-color: <?= esc($o['etapa_color'] ?? '#6c757d') ?>"><?= esc($o['etapa_nombre'] ?? '—') ?></span></td>
                <td class="text-end fw-bold">$<?= number_format((float) $o['valor'], 0, ',', '.') ?></td>
                <td><?= (int) $o['probabilidad'] ?>%</td>
                <td><?= $o['fecha_cierre_estimada'] ? date('d/m/Y', strtotime($o['fecha_cierre_estimada'])) : '—' ?></td>
                <td><?= esc($o['responsable_nombre'] ?? '—') ?></td>
                <td class="text-center text-nowrap">
                    <a href="<?= base_url('crm/oportunidades/ver/' . $o['id_oportunidad']) ?>"
                       class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                    <a href="<?= base_url('crm/oportunidades/editar/' . $o['id_oportunidad']) ?>"
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
    if ($('#tablaOps tbody tr').length) {
        $('#tablaOps').DataTable({
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
