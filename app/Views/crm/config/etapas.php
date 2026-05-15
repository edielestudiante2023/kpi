<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etapas – CRM Config – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-diagram-3 me-2"></i>Etapas del pipeline</h1>
        <div>
            <a href="<?= base_url('crm/config/fuentes') ?>" class="btn btn-outline-secondary btn-sm">Fuentes</a>
            <a href="<?= base_url('crm/config/motivos') ?>" class="btn btn-outline-secondary btn-sm">Motivos</a>
            <button class="btn btn-primary btn-sm" onclick="abrirNueva()">
                <i class="bi bi-plus-lg me-1"></i> Nueva etapa
            </button>
        </div>
    </div>
    <p class="text-muted small">Define las etapas por las que pasa una oportunidad. El tipo determina el comportamiento: <strong>abierta</strong> aparece en el Kanban; <strong>ganada</strong>/<strong>perdida</strong> cierran la oportunidad.</p>

    <table class="table table-sm table-striped">
        <thead class="table-dark">
            <tr>
                <th>Orden</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Probabilidad default</th>
                <th>Color</th>
                <th>Activa</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($etapas as $e): ?>
            <tr>
                <td><?= $e['orden'] ?></td>
                <td><strong><?= esc($e['nombre']) ?></strong></td>
                <td>
                    <?php
                    $colorTipo = ['abierta' => 'bg-info', 'ganada' => 'bg-success', 'perdida' => 'bg-danger'][$e['tipo']];
                    ?>
                    <span class="badge <?= $colorTipo ?>"><?= esc($e['tipo']) ?></span>
                </td>
                <td><?= (int) $e['probabilidad_default'] ?>%</td>
                <td><span class="d-inline-block" style="width:24px;height:24px;background:<?= esc($e['color']) ?>;border-radius:4px;vertical-align:middle;"></span> <?= esc($e['color']) ?></td>
                <td>
                    <?= (int) $e['activa'] === 1 ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?>
                </td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick='editar(<?= json_encode($e, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminar(<?= $e['id_etapa'] ?>)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="modalEtapa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEtapa">
                <div class="modal-header">
                    <h5 class="modal-title">Etapa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_etapa" id="idEtapa" value="0">
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label small">Nombre *</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Orden *</label>
                            <input type="number" name="orden" class="form-control form-control-sm" required value="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Tipo *</label>
                            <select name="tipo" class="form-select form-select-sm">
                                <option value="abierta">Abierta (Kanban)</option>
                                <option value="ganada">Ganada (cierre)</option>
                                <option value="perdida">Perdida (cierre)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Probabilidad default (%)</label>
                            <input type="number" name="probabilidad_default" class="form-control form-control-sm" min="0" max="100" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Color</label>
                            <input type="color" name="color" class="form-control form-control-sm form-control-color" value="#6c757d">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check ms-2">
                                <input type="checkbox" name="activa" value="1" class="form-check-input" id="activaEt" checked>
                                <label class="form-check-label small" for="activaEt">Activa</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = '<?= base_url() ?>';
const CSRF_NAME = '<?= csrf_token() ?>';
const CSRF_HASH = '<?= csrf_hash() ?>';
const modal = new bootstrap.Modal(document.getElementById('modalEtapa'));

function abrirNueva() {
    const f = document.getElementById('formEtapa');
    f.reset();
    document.getElementById('idEtapa').value = 0;
    f.querySelector('[name=activa]').checked = true;
    f.querySelector('[name=color]').value = '#6c757d';
    modal.show();
}
function editar(e) {
    const f = document.getElementById('formEtapa');
    f.reset();
    document.getElementById('idEtapa').value = e.id_etapa;
    f.querySelector('[name=nombre]').value = e.nombre;
    f.querySelector('[name=orden]').value = e.orden;
    f.querySelector('[name=tipo]').value = e.tipo;
    f.querySelector('[name=probabilidad_default]').value = e.probabilidad_default;
    f.querySelector('[name=color]').value = e.color;
    f.querySelector('[name=activa]').checked = parseInt(e.activa, 10) === 1;
    modal.show();
}

document.getElementById('formEtapa').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'crm/config/etapas/guardar', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.ok) location.reload(); else alert(d.error || 'Error'); });
});

function eliminar(id) {
    if (!confirm('¿Eliminar esta etapa? Solo posible si no tiene oportunidades.')) return;
    const fd = new FormData(); fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'crm/config/etapas/eliminar/' + id, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.ok) location.reload(); else alert(d.error || 'Error'); });
}
</script>
</body>
</html>
