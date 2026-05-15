<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($oportunidad['codigo']) ?> – CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; }
        .historial-item { border-left: 2px solid #dee2e6; padding-left: 12px; padding-bottom: 8px; position: relative; }
        .historial-item:before { content: ''; position: absolute; left: -5px; top: 4px; width: 9px; height: 9px; border-radius: 50%; background: #6c757d; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php
$tipo = $oportunidad['etapa_tipo'] ?? 'abierta';
$puedeCerrar = $tipo === 'abierta';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <div class="text-muted small"><?= esc($oportunidad['codigo']) ?></div>
            <h1 class="h4 mb-1"><?= esc($oportunidad['titulo']) ?></h1>
            <div>
                <a href="<?= base_url('crm/empresas/ver/' . $oportunidad['id_empresa']) ?>" class="text-decoration-none">
                    <i class="bi bi-building me-1"></i><?= esc($oportunidad['empresa_nombre'] ?? '—') ?>
                </a>
                <span class="ms-2 badge" style="background-color: <?= esc($oportunidad['etapa_color'] ?? '#6c757d') ?>; font-size: 0.75rem;">
                    <?= esc($oportunidad['etapa_nombre'] ?? '—') ?>
                </span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('crm/oportunidades/kanban') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Pipeline
            </a>
            <a href="<?= base_url('crm/oportunidades/editar/' . $oportunidad['id_oportunidad']) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i> Editar
            </a>
            <?php if ($puedeCerrar): ?>
                <button class="btn btn-success btn-sm" onclick="marcarGanada()">
                    <i class="bi bi-trophy me-1"></i> Marcar ganada
                </button>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalPerdida">
                    <i class="bi bi-x-circle me-1"></i> Marcar perdida
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- IZQ: ficha -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-info-circle me-1"></i>Información</div>
                <div class="card-body">
                    <div class="row g-2 small">
                        <div class="col-md-3"><div class="label">Valor</div>
                            <div class="fw-bold fs-5 text-success">
                                $<?= number_format((float) $oportunidad['valor'], 0, ',', '.') ?>
                            </div>
                        </div>
                        <div class="col-md-3"><div class="label">Probabilidad</div>
                            <div class="fw-bold fs-5"><?= (int) $oportunidad['probabilidad'] ?>%</div>
                        </div>
                        <div class="col-md-3"><div class="label">Cierre estimado</div>
                            <div><?= !empty($oportunidad['fecha_cierre_estimada']) ? date('d/m/Y', strtotime($oportunidad['fecha_cierre_estimada'])) : '—' ?></div>
                        </div>
                        <div class="col-md-3"><div class="label">Cierre real</div>
                            <div><?= !empty($oportunidad['fecha_cierre_real']) ? date('d/m/Y', strtotime($oportunidad['fecha_cierre_real'])) : '—' ?></div>
                        </div>
                        <div class="col-md-6 mt-3"><div class="label">Responsable</div>
                            <div><i class="bi bi-person-circle me-1"></i><?= esc($oportunidad['responsable_nombre'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6 mt-3"><div class="label">Contacto principal</div>
                            <div><?= esc($oportunidad['contacto_nombre'] ?? '—') ?></div>
                        </div>
                        <?php if (!empty($oportunidad['motivo_perdida_nombre'])): ?>
                        <div class="col-12 mt-3"><div class="label">Motivo de pérdida</div>
                            <div class="text-danger"><strong><?= esc($oportunidad['motivo_perdida_nombre']) ?></strong></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($oportunidad['descripcion'])): ?>
                        <div class="col-12 mt-3"><div class="label">Descripción</div>
                            <div><?= nl2br(esc($oportunidad['descripcion'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($oportunidad['notas'])): ?>
                        <div class="col-12 mt-3"><div class="label">Notas</div>
                            <div><?= nl2br(esc($oportunidad['notas'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Placeholder de interacciones (se llena en chunk 4) -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-chat-dots me-1"></i>Interacciones</span>
                    <button class="btn btn-sm btn-outline-primary" disabled title="Disponible en próxima entrega">
                        <i class="bi bi-plus-lg"></i> Agregar
                    </button>
                </div>
                <div class="card-body text-center text-muted small py-4">
                    El timeline de interacciones (llamadas, reuniones, tareas) se habilita en la próxima entrega.
                </div>
            </div>
        </div>

        <!-- DER: historial -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-clock-history me-1"></i>Historial de etapas</div>
                <div class="card-body">
                    <?php if (empty($historial)): ?>
                        <div class="text-muted small text-center py-3">Sin historial.</div>
                    <?php else: ?>
                        <?php foreach ($historial as $h): ?>
                            <div class="historial-item">
                                <div class="small">
                                    <?php if (!empty($h['etapa_anterior_nombre'])): ?>
                                        <span class="badge text-bg-light" style="border: 1px solid <?= esc($h['etapa_anterior_color']) ?>;">
                                            <?= esc($h['etapa_anterior_nombre']) ?>
                                        </span>
                                        <i class="bi bi-arrow-right mx-1"></i>
                                    <?php endif; ?>
                                    <span class="badge" style="background-color: <?= esc($h['etapa_nueva_color'] ?? '#6c757d') ?>">
                                        <?= esc($h['etapa_nueva_nombre']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($h['comentario'])): ?>
                                    <div class="small mt-1"><?= esc($h['comentario']) ?></div>
                                <?php endif; ?>
                                <div class="text-muted small mt-1">
                                    <?= esc($h['usuario_nombre'] ?? '—') ?> ·
                                    <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Marcar perdida -->
<div class="modal fade" id="modalPerdida" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formPerdida">
                <div class="modal-header">
                    <h5 class="modal-title">Marcar como perdida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small">Motivo *</label>
                    <select name="id_motivo_perdida" class="form-select form-select-sm" required>
                        <option value="">— Selecciona —</option>
                        <?php foreach ($motivos as $m): ?>
                            <option value="<?= $m['id_motivo_perdida'] ?>"><?= esc($m['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label small mt-2">Comentario</label>
                    <textarea name="comentario" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirmar pérdida</button>
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
let CSRF_HASH = '<?= csrf_hash() ?>';

function marcarGanada() {
    if (!confirm('¿Marcar esta oportunidad como ganada?')) return;
    const fd = new FormData(); fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'crm/oportunidades/marcar-ganada/<?= $oportunidad['id_oportunidad'] ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.ok) location.reload(); else alert(d.error || 'Error'); });
}

document.getElementById('formPerdida').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this); fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'crm/oportunidades/marcar-perdida/<?= $oportunidad['id_oportunidad'] ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.ok) location.reload(); else alert(d.error || 'Error'); });
});
</script>
</body>
</html>
