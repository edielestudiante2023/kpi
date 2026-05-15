<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($empresa['razon_social']) ?> – CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .label { font-size: 0.7rem; color: #6c757d; }
        .timeline-item { border-left: 3px solid #dee2e6; padding-left: 14px; padding-bottom: 12px; position: relative; }
        .timeline-item:before { content: ''; position: absolute; left: -7px; top: 4px; width: 11px; height: 11px; background: #6c757d; border-radius: 50%; }
        .timeline-item.llamada:before { background: #0d6efd; }
        .timeline-item.reunion:before { background: #198754; }
        .timeline-item.correo:before { background: #ffc107; }
        .timeline-item.tarea:before { background: #fd7e14; }
        .timeline-item.nota:before { background: #6c757d; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-building me-2"></i><?= esc($empresa['razon_social']) ?>
                <?php if ((int) $empresa['activo'] === 0): ?>
                    <span class="badge bg-warning text-dark fs-6 align-middle">Inactiva</span>
                <?php endif; ?>
            </h1>
            <div class="text-muted small">
                <?= esc($empresa['nit'] ?? '—') ?>
                <?php if (!empty($empresa['sector'])): ?> · <?= esc($empresa['sector']) ?><?php endif; ?>
                <?php if (!empty($empresa['ciudad'])): ?> · <?= esc($empresa['ciudad']) ?><?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('crm/empresas') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= base_url('crm/empresas/editar/' . $empresa['id_empresa']) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i> Editar
            </a>
            <a href="<?= base_url('crm/oportunidades/nueva?id_empresa=' . $empresa['id_empresa']) ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nueva oportunidad
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

    <div class="row g-3">
        <!-- COLUMNA IZQ: ficha + oportunidades + interacciones -->
        <div class="col-md-8">
            <!-- Ficha -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-info-circle me-1"></i>Ficha</div>
                <div class="card-body">
                    <div class="row small g-2">
                        <div class="col-md-4"><div class="label">Tamaño</div><div><?= esc($empresa['tamano'] ?? '—') ?></div></div>
                        <div class="col-md-4"><div class="label">Teléfono</div><div><?= esc($empresa['telefono'] ?? '—') ?></div></div>
                        <div class="col-md-4"><div class="label">Email principal</div><div><?= esc($empresa['email_principal'] ?? '—') ?></div></div>
                        <div class="col-md-6">
                            <div class="label">Sitio web</div>
                            <div>
                                <?php if (!empty($empresa['sitio_web'])): ?>
                                    <a href="<?= esc($empresa['sitio_web']) ?>" target="_blank"><?= esc($empresa['sitio_web']) ?></a>
                                <?php else: ?>—<?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3"><div class="label">Fuente</div><div><?= esc($empresa['fuente_nombre'] ?: '—') ?></div></div>
                        <div class="col-md-3"><div class="label">Responsable</div><div><?= esc($empresa['responsable_nombre'] ?: '—') ?></div></div>
                        <?php if (!empty($empresa['notas'])): ?>
                            <div class="col-12 mt-2"><div class="label">Notas</div><div><?= nl2br(esc($empresa['notas'])) ?></div></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Oportunidades -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-briefcase me-1"></i>Oportunidades</span>
                    <span class="badge bg-secondary"><?= count($oportunidades) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($oportunidades)): ?>
                        <div class="text-center text-muted py-4 small">Aún no hay oportunidades con esta empresa.</div>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Código</th><th>Título</th><th>Etapa</th><th class="text-end">Valor</th><th>Cierre est.</th><th>Resp.</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($oportunidades as $o): ?>
                                <tr>
                                    <td><a href="<?= base_url('crm/oportunidades/ver/' . $o['id_oportunidad']) ?>"><?= esc($o['codigo']) ?></a></td>
                                    <td><?= esc($o['titulo']) ?></td>
                                    <td><span class="badge" style="background-color: <?= esc($o['etapa_color'] ?? '#6c757d') ?>"><?= esc($o['etapa_nombre'] ?? '—') ?></span></td>
                                    <td class="text-end">$<?= number_format((float) $o['valor'], 0, ',', '.') ?></td>
                                    <td><?= $o['fecha_cierre_estimada'] ? date('d/m/Y', strtotime($o['fecha_cierre_estimada'])) : '—' ?></td>
                                    <td><?= esc($o['responsable_nombre'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Interacciones -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-chat-dots me-1"></i>Interacciones recientes</div>
                <div class="card-body">
                    <?php if (empty($interacciones)): ?>
                        <div class="text-center text-muted py-3 small">Sin interacciones registradas todavía.</div>
                    <?php else: ?>
                        <?php foreach ($interacciones as $i): ?>
                            <div class="timeline-item <?= esc($i['tipo']) ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-secondary text-uppercase" style="font-size:0.6rem;"><?= esc($i['tipo']) ?></span>
                                        <strong class="small"><?= esc($i['asunto']) ?></strong>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($i['fecha_completada'] ?? $i['fecha_programada'] ?? $i['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if (!empty($i['detalle'])): ?>
                                    <div class="small text-muted mt-1"><?= nl2br(esc($i['detalle'])) ?></div>
                                <?php endif; ?>
                                <div class="small text-muted mt-1">
                                    <?php if (!empty($i['oportunidad_codigo'])): ?>
                                        <i class="bi bi-briefcase me-1"></i><?= esc($i['oportunidad_codigo']) ?> ·
                                    <?php endif; ?>
                                    <i class="bi bi-person me-1"></i><?= esc($i['usuario_nombre'] ?? '—') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- COLUMNA DER: contactos -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-1"></i>Contactos</span>
                    <button class="btn btn-sm btn-outline-primary" onclick="abrirNuevoContacto()">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="card-body" id="contactos-list">
                    <?php if (empty($contactos)): ?>
                        <div class="text-muted small text-center py-3">Sin contactos. Agrega uno con el botón +.</div>
                    <?php endif; ?>
                    <?php foreach ($contactos as $c): ?>
                        <div class="border-bottom pb-2 mb-2" id="contacto-<?= $c['id_contacto'] ?>">
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1">
                                    <div class="fw-bold small">
                                        <?= esc($c['nombre']) ?>
                                        <?php if ((int) $c['es_decisor'] === 1): ?>
                                            <span class="badge bg-warning text-dark" style="font-size:0.55rem;">DECISOR</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($c['cargo'])): ?><div class="small text-muted"><?= esc($c['cargo']) ?></div><?php endif; ?>
                                    <?php if (!empty($c['email'])): ?>
                                        <div class="small"><i class="bi bi-envelope me-1"></i><a href="mailto:<?= esc($c['email']) ?>"><?= esc($c['email']) ?></a></div>
                                    <?php endif; ?>
                                    <?php if (!empty($c['telefono'])): ?>
                                        <div class="small"><i class="bi bi-telephone me-1"></i><?= esc($c['telefono']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-nowrap">
                                    <button class="btn btn-sm btn-link p-0 me-1"
                                            onclick='editarContacto(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-link text-danger p-0"
                                            onclick="eliminarContacto(<?= $c['id_contacto'] ?>)" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal contacto -->
<div class="modal fade" id="modalContacto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formContacto">
                <div class="modal-header">
                    <h5 class="modal-title">Contacto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_contacto" id="idContacto" value="0">
                    <input type="hidden" name="id_empresa" value="<?= $empresa['id_empresa'] ?>">
                    <div class="row g-2">
                        <div class="col-12"><label class="form-label small">Nombre *</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" required></div>
                        <div class="col-md-6"><label class="form-label small">Cargo</label>
                            <input type="text" name="cargo" class="form-control form-control-sm"></div>
                        <div class="col-md-6"><label class="form-label small">Teléfono</label>
                            <input type="text" name="telefono" class="form-control form-control-sm"></div>
                        <div class="col-12"><label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm"></div>
                        <div class="col-12"><label class="form-label small">Notas</label>
                            <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea></div>
                        <div class="col-12 form-check ms-2 mt-1">
                            <input type="checkbox" name="es_decisor" value="1" class="form-check-input" id="es_decisor">
                            <label class="form-check-label small" for="es_decisor">Es decisor</label>
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
let CSRF_HASH = '<?= csrf_hash() ?>';

const modal = new bootstrap.Modal(document.getElementById('modalContacto'));

function abrirNuevoContacto() {
    const f = document.getElementById('formContacto');
    f.reset();
    document.getElementById('idContacto').value = 0;
    modal.show();
}
function editarContacto(c) {
    const f = document.getElementById('formContacto');
    f.reset();
    document.getElementById('idContacto').value = c.id_contacto;
    f.querySelector('[name=nombre]').value = c.nombre || '';
    f.querySelector('[name=cargo]').value  = c.cargo || '';
    f.querySelector('[name=email]').value  = c.email || '';
    f.querySelector('[name=telefono]').value = c.telefono || '';
    f.querySelector('[name=notas]').value  = c.notas || '';
    f.querySelector('[name=es_decisor]').checked = parseInt(c.es_decisor, 10) === 1;
    modal.show();
}

document.getElementById('formContacto').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'crm/contactos/guardar', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.ok) location.reload();
            else alert((resp.errors || [resp.error || 'Error']).join('\n'));
        })
        .catch(() => alert('Error de conexión'));
});

function eliminarContacto(id) {
    if (!confirm('¿Eliminar este contacto?')) return;
    const fd = new FormData(); fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'crm/contactos/eliminar/' + id, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.ok) {
                const el = document.getElementById('contacto-' + id);
                if (el) el.remove();
            } else alert(resp.error || 'Error');
        });
}
</script>
</body>
</html>
