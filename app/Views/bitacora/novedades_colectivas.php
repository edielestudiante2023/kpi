<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<h6 class="mb-3"><i class="bi bi-calendar2-check me-1"></i> Novedades Colectivas <?= $anio ?></h6>
<p class="text-muted small mb-3">Reducciones de jornada que aplican a <strong>todos</strong> los usuarios (fechas especiales).</p>

<!-- Navegación por año -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= base_url('bitacora/novedades-colectivas/' . ($anio - 1)) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-left"></i> <?= $anio - 1 ?>
    </a>
    <span class="fw-bold"><?= $anio ?></span>
    <a href="<?= base_url('bitacora/novedades-colectivas/' . ($anio + 1)) ?>" class="btn btn-sm btn-outline-secondary">
        <?= $anio + 1 ?> <i class="bi bi-chevron-right"></i>
    </a>
</div>

<!-- Formulario agregar -->
<div class="card shadow-sm mb-3">
    <div class="card-body p-2">
        <div class="row g-2 align-items-end">
            <div class="col-4">
                <label class="form-label small mb-1">Fecha</label>
                <input type="date" class="form-control form-control-sm" id="inputFecha" value="<?= $anio ?>-01-01">
            </div>
            <div class="col-4">
                <label class="form-label small mb-1">Descripción</label>
                <input type="text" class="form-control form-control-sm" id="inputDesc" placeholder="Ej: Día de la Mujer">
            </div>
            <div class="col-2">
                <label class="form-label small mb-1">Horas</label>
                <input type="number" class="form-control form-control-sm" id="inputHoras" min="0.5" max="8" step="0.5" value="4" placeholder="4">
            </div>
            <div class="col-2">
                <button class="btn btn-sm btn-primary w-100" id="btnAgregar">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Lista -->
<div id="listaNov">
    <?php if (empty($novedades)): ?>
    <div class="text-center text-muted py-4">
        <i class="bi bi-calendar-x fs-1"></i>
        <p class="mt-2 small">No hay novedades colectivas para <?= $anio ?></p>
    </div>
    <?php else: ?>
    <?php foreach ($novedades as $n): ?>
    <div class="card shadow-sm mb-2" id="nov-<?= $n['id_novedad_colectiva'] ?>">
        <div class="card-body p-2 d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold small"><?= esc($n['descripcion']) ?></div>
                <div class="text-muted" style="font-size: 0.7rem;">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d/m/Y', strtotime($n['fecha'])) ?>
                    &middot;
                    <span class="badge bg-warning text-dark"><?= $n['horas_reduccion'] ?>h de reducción</span>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-danger btn-eliminar" data-id="<?= $n['id_novedad_colectiva'] ?>">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Navegación -->
<div class="mt-3 d-flex flex-column gap-2">
    <a href="<?= base_url('bitacora/novedades-individuales') ?>" class="btn btn-outline-info btn-sm w-100">
        <i class="bi bi-person-check me-1"></i> Novedades Individuales
    </a>
    <a href="<?= base_url('bitacora/liquidacion') ?>" class="btn btn-outline-secondary btn-sm w-100">
        <i class="bi bi-arrow-left me-1"></i> Volver a Liquidación
    </a>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    var BASE = '<?= base_url() ?>';
    var CSRF_NAME = '<?= csrf_token() ?>';
    var CSRF_HASH = '<?= csrf_hash() ?>';

    function ajax(method, url, data) {
        data[CSRF_NAME] = CSRF_HASH;
        var fd = new FormData();
        for (var k in data) fd.append(k, data[k]);
        return fetch(BASE + url, { method: method, body: fd })
            .then(function(r) { return r.json(); });
    }

    var btnAgregar = document.getElementById('btnAgregar');
    if (btnAgregar) {
        btnAgregar.addEventListener('click', function() {
            var fecha = document.getElementById('inputFecha').value;
            var desc = document.getElementById('inputDesc').value.trim();
            var horas = document.getElementById('inputHoras').value;
            if (!fecha || !desc || !horas || parseFloat(horas) <= 0) {
                alert('Todos los campos son requeridos');
                return;
            }

            btnAgregar.disabled = true;
            ajax('POST', 'bitacora/novedades-colectivas/guardar', {
                fecha: fecha,
                descripcion: desc,
                horas_reduccion: horas
            })
            .then(function(resp) {
                if (resp.ok) {
                    location.reload();
                } else {
                    alert(resp.error || 'Error al guardar');
                    btnAgregar.disabled = false;
                }
            })
            .catch(function() {
                alert('Error de conexión');
                btnAgregar.disabled = false;
            });
        });
    }

    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('¿Eliminar esta novedad colectiva?')) return;
            var id = btn.getAttribute('data-id');
            btn.disabled = true;

            ajax('POST', 'bitacora/novedades-colectivas/eliminar/' + id, {})
                .then(function(resp) {
                    if (resp.ok) {
                        var card = document.getElementById('nov-' + id);
                        if (card) card.remove();
                    } else {
                        alert(resp.error || 'Error al eliminar');
                        btn.disabled = false;
                    }
                })
                .catch(function() {
                    alert('Error de conexión');
                    btn.disabled = false;
                });
        });
    });
})();
</script>
<?= $this->endSection() ?>
