<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terceros – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .doc-card { background: #f8f9fa; border-radius: 6px; padding: 10px; margin-bottom: 8px; }
        .doc-card h6 { font-size: 0.85rem; margin-bottom: 8px; }
        .doc-item { font-size: 0.78rem; padding: 4px 8px; background: #fff; border-radius: 4px; margin-bottom: 4px; }
        .badge-doc { font-size: 0.65rem; }
    </style>
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <?= view('components/back_to_dashboard') ?>
            <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>Terceros</h1>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTercero" onclick="abrirNuevo()">
            <i class="bi bi-plus-lg me-1"></i> Nuevo tercero
        </button>
    </div>
    <p class="text-muted small">
        Maestro de proveedores (personas a las que se les emiten cuentas de cobro). Adjunta el RUT,
        la cédula y la certificación bancaria para tenerlos a la mano al momento de pagar.
    </p>

    <!-- Buscador -->
    <form method="get" class="mb-3">
        <div class="input-group">
            <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, documento o email…"
                   value="<?= esc($filtroBusqueda) ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($filtroBusqueda): ?>
                <a href="<?= base_url('conciliaciones/terceros') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Listado -->
    <?php if (empty($terceros)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No hay terceros registrados<?= $filtroBusqueda ? ' que coincidan con la búsqueda.' : '. Crea el primero con el botón de arriba.' ?>
        </div>
    <?php else: ?>
        <?php foreach ($terceros as $t): ?>
        <div class="card shadow-sm mb-2" id="tercero-<?= $t['id_tercero'] ?>">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1" role="button"
                         data-bs-toggle="collapse" data-bs-target="#det-<?= $t['id_tercero'] ?>">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold"><?= esc($t['nombre']) ?></span>
                            <span class="badge bg-secondary"><?= esc($t['tipo_documento']) ?> <?= esc($t['documento']) ?></span>
                            <?php if ((int) $t['activo'] === 0): ?>
                                <span class="badge bg-warning text-dark">Inactivo</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small">
                            <?= (int) $t['tiene_rut'] > 0
                                ? '<span class="badge bg-success badge-doc"><i class="bi bi-check-lg"></i> RUT</span>'
                                : '<span class="badge bg-light text-dark badge-doc"><i class="bi bi-x-lg"></i> RUT</span>' ?>
                            <?= (int) $t['tiene_cedula'] > 0
                                ? '<span class="badge bg-success badge-doc"><i class="bi bi-check-lg"></i> Cédula</span>'
                                : '<span class="badge bg-light text-dark badge-doc"><i class="bi bi-x-lg"></i> Cédula</span>' ?>
                            <?= (int) $t['tiene_cert_bancaria'] > 0
                                ? '<span class="badge bg-success badge-doc"><i class="bi bi-check-lg"></i> Cert. bancaria</span>'
                                : '<span class="badge bg-light text-dark badge-doc"><i class="bi bi-x-lg"></i> Cert. bancaria</span>' ?>
                            <?php if (!empty($t['banco'])): ?>
                                <span class="ms-2"><i class="bi bi-bank me-1"></i><?= esc($t['banco']) ?> · <?= esc($t['numero_cuenta'] ?? '') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-nowrap">
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick='abrirEditar(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                data-bs-toggle="modal" data-bs-target="#modalTercero">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarTercero(<?= $t['id_tercero'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="collapse mt-3" id="det-<?= $t['id_tercero'] ?>">
                    <hr class="my-2">
                    <div class="row small">
                        <div class="col-md-6">
                            <div><span class="text-muted">Email:</span> <?= esc($t['email'] ?? '—') ?></div>
                            <div><span class="text-muted">Teléfono:</span> <?= esc($t['telefono'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div><span class="text-muted">Banco:</span> <?= esc($t['banco'] ?? '—') ?></div>
                            <div><span class="text-muted">Cuenta:</span> <?= esc($t['tipo_cuenta'] ?? '—') ?> · <?= esc($t['numero_cuenta'] ?? '—') ?></div>
                            <div><span class="text-muted">Titular:</span> <?= esc($t['titular_cuenta'] ?? '—') ?></div>
                        </div>
                    </div>
                    <?php if (!empty($t['notas'])): ?>
                        <div class="mt-2 small"><span class="text-muted">Notas:</span> <?= esc($t['notas']) ?></div>
                    <?php endif; ?>

                    <!-- Documentos por tipo -->
                    <div class="row mt-3">
                        <?php foreach (['rut' => 'RUT', 'cedula' => 'Cédula', 'cert_bancaria' => 'Certificación bancaria'] as $tipo => $label):
                            $docsDelTipo = array_values(array_filter($t['documentos'], fn($d) => $d['tipo'] === $tipo));
                        ?>
                        <div class="col-md-4">
                            <div class="doc-card">
                                <h6><i class="bi bi-file-earmark-pdf me-1"></i><?= $label ?></h6>
                                <?php if (empty($docsDelTipo)): ?>
                                    <div class="text-muted small mb-2"><em>Sin documentos.</em></div>
                                <?php else: ?>
                                    <?php foreach ($docsDelTipo as $d): ?>
                                    <div class="doc-item d-flex justify-content-between align-items-center" id="doc-<?= $d['id_documento'] ?>">
                                        <span class="text-truncate" style="max-width: 150px;" title="<?= esc($d['nombre_original']) ?>">
                                            <?= esc($d['nombre_original']) ?>
                                            <br><small class="text-muted"><?= date('d/m/Y', strtotime($d['created_at'])) ?></small>
                                        </span>
                                        <span class="text-nowrap">
                                            <a href="<?= base_url('conciliaciones/terceros/documento/ver/' . $d['id_documento']) ?>"
                                               class="btn btn-sm btn-outline-primary py-0 px-1" target="_blank"
                                               title="Ver"><i class="bi bi-eye"></i></a>
                                            <button class="btn btn-sm btn-outline-danger py-0 px-1"
                                                    onclick="eliminarDoc(<?= $d['id_documento'] ?>)"
                                                    title="Eliminar"><i class="bi bi-trash"></i></button>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <form class="form-subir-doc" data-id-tercero="<?= $t['id_tercero'] ?>" data-tipo="<?= $tipo ?>">
                                    <input type="file" name="archivo" accept="application/pdf" class="form-control form-control-sm mt-2">
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal crear/editar tercero -->
<div class="modal fade" id="modalTercero" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formTercero">
                <div class="modal-header">
                    <h5 class="modal-title">Tercero</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_tercero" id="idTercero" value="0">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Tipo doc.</label>
                            <select name="tipo_documento" class="form-select form-select-sm">
                                <option value="CC">CC</option>
                                <option value="CE">CE</option>
                                <option value="TI">TI</option>
                                <option value="NIT">NIT</option>
                                <option value="PASAPORTE">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Documento *</label>
                            <input type="text" name="documento" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">
                                <input type="checkbox" name="activo" value="1" checked> Activo
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Nombre completo *</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Teléfono</label>
                            <input type="text" name="telefono" class="form-control form-control-sm">
                        </div>
                        <hr class="my-2">
                        <div class="col-12"><strong class="small text-muted">Datos bancarios</strong></div>
                        <div class="col-md-6">
                            <label class="form-label small">Banco</label>
                            <input type="text" name="banco" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Tipo cuenta</label>
                            <select name="tipo_cuenta" class="form-select form-select-sm">
                                <option value="">—</option>
                                <option value="ahorros">Ahorros</option>
                                <option value="corriente">Corriente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Número de cuenta</label>
                            <input type="text" name="numero_cuenta" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">Titular de la cuenta</label>
                            <input type="text" name="titular_cuenta" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Notas</label>
                            <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
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

function abrirNuevo() {
    const f = document.getElementById('formTercero');
    f.reset();
    document.getElementById('idTercero').value = 0;
    f.querySelector('[name=activo]').checked = true;
}

function abrirEditar(t) {
    const f = document.getElementById('formTercero');
    f.reset();
    document.getElementById('idTercero').value = t.id_tercero;
    f.querySelector('[name=tipo_documento]').value = t.tipo_documento || 'CC';
    f.querySelector('[name=documento]').value = t.documento || '';
    f.querySelector('[name=nombre]').value = t.nombre || '';
    f.querySelector('[name=email]').value = t.email || '';
    f.querySelector('[name=telefono]').value = t.telefono || '';
    f.querySelector('[name=banco]').value = t.banco || '';
    f.querySelector('[name=tipo_cuenta]').value = t.tipo_cuenta || '';
    f.querySelector('[name=numero_cuenta]').value = t.numero_cuenta || '';
    f.querySelector('[name=titular_cuenta]').value = t.titular_cuenta || '';
    f.querySelector('[name=notas]').value = t.notas || '';
    f.querySelector('[name=activo]').checked = parseInt(t.activo, 10) === 1;
}

document.getElementById('formTercero').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'conciliaciones/terceros/guardar', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.ok) {
                location.reload();
            } else {
                alert((resp.errors || ['Error al guardar']).join('\n'));
            }
        })
        .catch(() => alert('Error de conexión'));
});

function eliminarTercero(id) {
    if (!confirm('¿Eliminar este tercero? Solo se puede si no tiene cuentas de cobro asociadas.')) return;
    const fd = new FormData(); fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'conciliaciones/terceros/eliminar/' + id, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.ok) location.reload();
            else alert(resp.error || 'Error');
        });
}

function eliminarDoc(id) {
    if (!confirm('¿Eliminar este documento?')) return;
    const fd = new FormData(); fd.append(CSRF_NAME, CSRF_HASH);
    fetch(BASE + 'conciliaciones/terceros/documento/eliminar/' + id, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.ok) {
                const el = document.getElementById('doc-' + id);
                if (el) el.remove();
            } else alert(resp.error || 'Error');
        });
}

// Subida de PDFs (uno por form al cambiar el input)
document.querySelectorAll('.form-subir-doc input[type=file]').forEach(function(input) {
    input.addEventListener('change', function() {
        const form = input.closest('.form-subir-doc');
        const idTercero = form.dataset.idTercero;
        const tipo = form.dataset.tipo;
        if (!input.files.length) return;

        const fd = new FormData();
        fd.append('archivo', input.files[0]);
        fd.append('tipo', tipo);
        fd.append(CSRF_NAME, CSRF_HASH);

        input.disabled = true;
        fetch(BASE + 'conciliaciones/terceros/documento/subir/' + idTercero, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(resp => {
                if (resp.ok) location.reload();
                else { alert(resp.error || 'Error al subir'); input.disabled = false; }
            })
            .catch(() => { alert('Error de conexión'); input.disabled = false; });
    });
});
</script>
</body>
</html>
