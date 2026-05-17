<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($lead['nombre']) ?> – Lead – Marketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; }
        .badge-estado-nuevo      { background: #0dcaf0; color: #000; }
        .badge-estado-contactado { background: #fd7e14; color: #fff; }
        .badge-estado-calificado { background: #198754; color: #fff; }
        .badge-estado-descartado { background: #6c757d; color: #fff; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php $yaConvertido = !empty($lead['id_oportunidad_convertida']); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-person me-1"></i><?= esc($lead['nombre']) ?>
                <span class="badge badge-estado-<?= $lead['estado'] ?> fs-6 align-middle"><?= ucfirst($lead['estado']) ?></span>
            </h1>
            <div class="text-muted small">
                <?php if (!empty($lead['cargo'])): ?><?= esc($lead['cargo']) ?><?php endif; ?>
                <?php if (!empty($lead['empresa_text'])): ?> · <?= esc($lead['empresa_text']) ?><?php endif; ?>
                · Creado <?= date('d/m/Y', strtotime($lead['created_at'])) ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('marketing/leads') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= base_url('marketing/leads/editar/' . $lead['id_lead']) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i> Editar
            </a>
            <?php if (!$yaConvertido): ?>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalConvertir">
                    <i class="bi bi-arrow-right-circle me-1"></i> Convertir a oportunidad
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <?php if ($yaConvertido): ?>
        <div class="alert alert-info py-2">
            <i class="bi bi-check-circle me-1"></i>
            <strong>Lead convertido el <?= !empty($lead['fecha_calificacion']) ? date('d/m/Y H:i', strtotime($lead['fecha_calificacion'])) : '—' ?>.</strong>
            <a href="<?= base_url('crm/oportunidades/ver/' . $lead['id_oportunidad_convertida']) ?>">
                Ver oportunidad <?= esc($lead['oportunidad_convertida_codigo'] ?? '') ?>
            </a> ·
            <a href="<?= base_url('crm/empresas/ver/' . $lead['id_empresa_convertida']) ?>">
                Ver empresa <?= esc($lead['empresa_convertida_nombre'] ?? '') ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-info-circle me-1"></i>Información</div>
                <div class="card-body">
                    <div class="row g-2 small">
                        <div class="col-md-6">
                            <div class="label">Email</div>
                            <div><?= !empty($lead['email']) ? '<a href="mailto:' . esc($lead['email']) . '">' . esc($lead['email']) . '</a>' : '—' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Teléfono</div>
                            <div><?= esc($lead['telefono'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Fuente</div>
                            <div><?= esc($lead['fuente_nombre'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Responsable</div>
                            <div><i class="bi bi-person-circle me-1"></i><?= esc($lead['responsable_nombre'] ?? '—') ?></div>
                        </div>
                        <?php if (!empty($lead['notas'])): ?>
                        <div class="col-12 mt-2">
                            <div class="label">Notas</div>
                            <div><?= nl2br(esc($lead['notas'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($lead['fecha_descartado'])): ?>
                        <div class="col-12 mt-2">
                            <div class="label">Descartado</div>
                            <div class="text-danger">
                                <?= date('d/m/Y', strtotime($lead['fecha_descartado'])) ?>
                                <?php if (!empty($lead['motivo_descarte'])): ?>
                                    · <?= esc($lead['motivo_descarte']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-arrow-repeat me-1"></i>Cambiar estado</div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Cambia rápido el estado del lead. El sistema registra fecha automáticamente.</p>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach (['nuevo','contactado','calificado','descartado'] as $e):
                            if ($e === $lead['estado']) continue;
                        ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="cambiarEstado('<?= $e ?>')">
                                → <?= ucfirst($e) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal convertir -->
<?php if (!$yaConvertido): ?>
<div class="modal fade" id="modalConvertir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formConvertir">
                <div class="modal-header">
                    <h5 class="modal-title">Convertir lead a oportunidad CRM</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Se creará una <strong>empresa</strong>, un <strong>contacto</strong>
                    y una <strong>oportunidad</strong> en el CRM, todo enlazado al lead.</p>

                    <label class="form-label small">Razón social (empresa)</label>
                    <input type="text" name="razon_social" class="form-control form-control-sm mb-2"
                           value="<?= esc($lead['empresa_text'] ?? $lead['nombre']) ?>">

                    <label class="form-label small">Título de la oportunidad</label>
                    <input type="text" name="titulo_oportunidad" class="form-control form-control-sm mb-2"
                           placeholder="Ej: Implementación SST 2026">

                    <label class="form-label small">Valor estimado (COP)</label>
                    <input type="number" name="valor" class="form-control form-control-sm mb-2" min="0" step="1000" value="0">

                    <small class="text-muted">La etapa inicial será la primera abierta del pipeline (típicamente "Prospecto").</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-arrow-right-circle me-1"></i> Convertir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = '<?= base_url() ?>';
const CSRF_NAME = '<?= csrf_token() ?>';
const CSRF_HASH = '<?= csrf_hash() ?>';

function cambiarEstado(nuevo) {
    let motivo = null;
    if (nuevo === 'descartado') {
        motivo = prompt('¿Por qué se descarta? (ej: Precio, No respondió, No es target)');
        if (motivo === null) return;
    } else if (!confirm('¿Cambiar estado a "' + nuevo + '"?')) {
        return;
    }
    const fd = new FormData();
    fd.append('estado', nuevo);
    if (motivo) fd.append('motivo_descarte', motivo);
    fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'marketing/leads/cambiar-estado/<?= $lead['id_lead'] ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.ok) location.reload(); else alert(d.error || 'Error'); });
}

<?php if (!$yaConvertido): ?>
document.getElementById('formConvertir').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append(CSRF_NAME, CSRF_HASH);
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    fetch(BASE + 'marketing/leads/convertir/<?= $lead['id_lead'] ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                alert('¡Convertido! Oportunidad ' + d.codigo + ' creada.');
                window.location.href = BASE.replace(/\/$/, '') + d.redirect;
            } else {
                alert(d.error || 'Error');
                btn.disabled = false;
            }
        })
        .catch(() => { alert('Error de conexión'); btn.disabled = false; });
});
<?php endif; ?>
</script>
</body>
</html>
