<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pipeline – CRM – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f6fa; }
        .kanban-wrap { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; }
        .kanban-col {
            flex: 0 0 290px; background: #fff; border-radius: 8px;
            border: 1px solid #e3e6ea; display: flex; flex-direction: column;
            max-height: calc(100vh - 180px);
        }
        .kanban-col.cerrada { background: #f8f9fa; opacity: 0.9; }
        .kanban-col-header {
            padding: 10px 12px; border-bottom: 2px solid; font-weight: 600;
            display: flex; justify-content: space-between; align-items: center;
            border-radius: 8px 8px 0 0; font-size: 0.85rem;
        }
        .kanban-col-body { padding: 8px; overflow-y: auto; flex: 1; min-height: 100px; }
        .kanban-col-body.drop-target { background: #e7f1ff; outline: 2px dashed #0d6efd; }
        .kanban-card {
            background: #fff; border: 1px solid #e3e6ea; border-radius: 6px;
            padding: 10px; margin-bottom: 8px; cursor: grab;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: transform 0.05s;
        }
        .kanban-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .kanban-card.dragging { opacity: 0.4; transform: rotate(2deg); }
        .kanban-card .codigo { font-size: 0.65rem; color: #6c757d; }
        .kanban-card .titulo { font-weight: 600; font-size: 0.85rem; color: #2c3e50; }
        .kanban-card .empresa { font-size: 0.72rem; color: #495057; }
        .kanban-card .valor { font-size: 0.85rem; font-weight: 700; color: #198754; }
        .kanban-card .meta-bottom {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 6px; font-size: 0.7rem; color: #6c757d;
        }
        .kanban-col-total { font-size: 0.7rem; color: #6c757d; font-weight: normal; }
        .col-count { font-size: 0.7rem; background: rgba(0,0,0,0.08); padding: 1px 6px; border-radius: 10px; }
        .toast-container { position: fixed; top: 60px; right: 16px; z-index: 1080; }
    </style>
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-3 px-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="h5 mb-0"><i class="bi bi-kanban me-2"></i>Pipeline comercial</h1>
            <div class="text-muted small">Arrastra las oportunidades entre etapas para actualizar su estado.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('crm/oportunidades/lista') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i> Lista
            </a>
            <a href="<?= base_url('crm/empresas') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-building me-1"></i> Empresas
            </a>
            <a href="<?= base_url('crm/oportunidades/nueva') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nueva oportunidad
            </a>
        </div>
    </div>

    <div class="kanban-wrap" id="kanban">
    <?php foreach ($etapas as $et):
        $cards   = $porEtapa[(int) $et['id_etapa']] ?? [];
        $total   = array_sum(array_map(fn($c) => (float) $c['valor'], $cards));
        $color   = $et['color'] ?: '#6c757d';
        $cerrada = $et['tipo'] !== 'abierta';
    ?>
        <div class="kanban-col <?= $cerrada ? 'cerrada' : '' ?>" data-id-etapa="<?= $et['id_etapa'] ?>" data-tipo="<?= esc($et['tipo']) ?>">
            <div class="kanban-col-header" style="border-bottom-color: <?= esc($color) ?>; color: <?= esc($color) ?>;">
                <span>
                    <?= esc($et['nombre']) ?>
                    <span class="col-count"><?= count($cards) ?></span>
                </span>
                <span class="kanban-col-total">$<?= number_format($total, 0, ',', '.') ?></span>
            </div>
            <div class="kanban-col-body" id="col-<?= $et['id_etapa'] ?>">
                <?php foreach ($cards as $c): ?>
                    <?= view('crm/oportunidades/_card', ['c' => $c]) ?>
                <?php endforeach; ?>
                <?php if (empty($cards)): ?>
                    <div class="text-muted small text-center py-3"><em>Vacío</em></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- Modal: motivo de pérdida -->
<div class="modal fade" id="modalMotivo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formMotivo">
                <div class="modal-header">
                    <h5 class="modal-title">Motivo de pérdida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_oportunidad" id="motivoIdOp">
                    <input type="hidden" name="id_etapa" id="motivoIdEtapa">
                    <label class="form-label small">Motivo *</label>
                    <select name="id_motivo_perdida" id="motivoSelect" class="form-select form-select-sm" required>
                        <option value="">— Selecciona —</option>
                        <?php foreach ($motivos as $m): ?>
                            <option value="<?= $m['id_motivo_perdida'] ?>"><?= esc($m['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label small mt-2">Comentario (opcional)</label>
                    <textarea name="comentario" id="motivoComentario" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">Marcar perdida</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="toast-container"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = '<?= base_url() ?>';
const CSRF_NAME = '<?= csrf_token() ?>';
let CSRF_HASH = '<?= csrf_hash() ?>';

const modalMotivo = new bootstrap.Modal(document.getElementById('modalMotivo'));

function toast(msg, tipo = 'success') {
    const wrap = document.querySelector('.toast-container');
    const html = `<div class="toast align-items-center text-white bg-${tipo} border-0 show" role="alert">
        <div class="d-flex"><div class="toast-body">${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
    wrap.insertAdjacentHTML('beforeend', html);
    setTimeout(() => { wrap.firstElementChild?.remove(); }, 3500);
}

// Drag-and-drop nativo HTML5
let draggingId = null;

document.querySelectorAll('.kanban-card').forEach(initCard);

function initCard(card) {
    card.setAttribute('draggable', 'true');
    card.addEventListener('dragstart', e => {
        draggingId = card.dataset.id;
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', () => {
        card.classList.remove('dragging');
        draggingId = null;
    });
}

document.querySelectorAll('.kanban-col-body').forEach(col => {
    col.addEventListener('dragover', e => {
        if (!draggingId) return;
        e.preventDefault();
        col.classList.add('drop-target');
    });
    col.addEventListener('dragleave', () => col.classList.remove('drop-target'));
    col.addEventListener('drop', e => {
        e.preventDefault();
        col.classList.remove('drop-target');
        if (!draggingId) return;
        const idOp = draggingId;
        const idEtapa = col.parentElement.dataset.idEtapa;
        const tipo = col.parentElement.dataset.tipo;

        // Si la columna destino es perdida → abrir modal de motivo
        if (tipo === 'perdida') {
            document.getElementById('motivoIdOp').value = idOp;
            document.getElementById('motivoIdEtapa').value = idEtapa;
            modalMotivo.show();
            return;
        }
        moverOportunidad(idOp, idEtapa, null, null, col);
    });
});

function moverOportunidad(idOp, idEtapa, idMotivo, comentario, colDestino) {
    const card = document.querySelector(`.kanban-card[data-id='${idOp}']`);
    if (!card) return;
    const fd = new FormData();
    fd.append('id_oportunidad', idOp);
    fd.append('id_etapa', idEtapa);
    if (idMotivo) fd.append('id_motivo_perdida', idMotivo);
    if (comentario) fd.append('comentario', comentario);
    fd.append(CSRF_NAME, CSRF_HASH);

    fetch(BASE + 'crm/oportunidades/cambiar-etapa', { method: 'POST', body: fd })
        .then(r => r.json().then(d => ({ status: r.status, body: d })))
        .then(({ status, body }) => {
            if (status === 200 && body.ok) {
                // Mover el card visualmente y refrescar contadores
                if (colDestino) {
                    const empty = colDestino.querySelector('em');
                    if (empty) empty.parentElement.remove();
                    colDestino.appendChild(card);
                }
                actualizarContadores();
                toast('Etapa actualizada', 'success');
            } else {
                toast(body.error || 'Error al mover', 'danger');
            }
        })
        .catch(() => toast('Error de conexión', 'danger'));
}

document.getElementById('formMotivo').addEventListener('submit', function(e) {
    e.preventDefault();
    const idOp = document.getElementById('motivoIdOp').value;
    const idEtapa = document.getElementById('motivoIdEtapa').value;
    const idMotivo = document.getElementById('motivoSelect').value;
    const comentario = document.getElementById('motivoComentario').value;
    if (!idMotivo) { alert('Selecciona un motivo'); return; }
    const col = document.getElementById('col-' + idEtapa);
    modalMotivo.hide();
    moverOportunidad(idOp, idEtapa, idMotivo, comentario, col);
});

function actualizarContadores() {
    document.querySelectorAll('.kanban-col').forEach(col => {
        const body = col.querySelector('.kanban-col-body');
        const cards = body.querySelectorAll('.kanban-card');
        const total = Array.from(cards).reduce((sum, c) => sum + (parseFloat(c.dataset.valor) || 0), 0);
        col.querySelector('.col-count').textContent = cards.length;
        col.querySelector('.kanban-col-total').textContent = '$' + total.toLocaleString('es-CO');
        // Si vacía, mostrar placeholder
        if (cards.length === 0 && !body.querySelector('em')) {
            body.innerHTML = '<div class="text-muted small text-center py-3"><em>Vacío</em></div>';
        }
    });
}
</script>
</body>
</html>
