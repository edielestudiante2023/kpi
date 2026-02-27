<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<h6 class="mb-3"><i class="bi bi-calendar-event me-1"></i> Días Festivos <?= $anio ?></h6>

<!-- Navegación por año -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= base_url('bitacora/festivos/' . ($anio - 1)) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-left"></i> <?= $anio - 1 ?>
    </a>
    <span class="fw-bold"><?= $anio ?></span>
    <a href="<?= base_url('bitacora/festivos/' . ($anio + 1)) ?>" class="btn btn-sm btn-outline-secondary">
        <?= $anio + 1 ?> <i class="bi bi-chevron-right"></i>
    </a>
</div>

<!-- Formulario agregar -->
<div class="card shadow-sm mb-3">
    <div class="card-body p-2">
        <div class="row g-2 align-items-end">
            <div class="col-5">
                <label class="form-label small mb-1">Fecha</label>
                <input type="date" class="form-control form-control-sm" id="inputFecha" value="<?= $anio ?>-01-01">
            </div>
            <div class="col-5">
                <label class="form-label small mb-1">Descripción</label>
                <input type="text" class="form-control form-control-sm" id="inputDesc" placeholder="Nombre del festivo">
            </div>
            <div class="col-2">
                <button class="btn btn-sm btn-primary w-100" id="btnAgregar">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Lista de festivos -->
<div id="listaFestivos">
    <?php if (empty($festivos)): ?>
    <div class="text-center text-muted py-4">
        <i class="bi bi-calendar-x fs-1"></i>
        <p class="mt-2 small">No hay festivos registrados para <?= $anio ?></p>
    </div>
    <?php else: ?>
    <?php foreach ($festivos as $f): ?>
    <div class="card shadow-sm mb-2" id="festivo-<?= $f['id_festivo'] ?>">
        <div class="card-body p-2 d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold small"><?= esc($f['descripcion']) ?></div>
                <div class="text-muted" style="font-size: 0.7rem;">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d/m/Y', strtotime($f['fecha'])) ?>
                    (<?= strftime('%A', strtotime($f['fecha'])) ?: date('l', strtotime($f['fecha'])) ?>)
                </div>
            </div>
            <button class="btn btn-sm btn-outline-danger btn-eliminar" data-id="<?= $f['id_festivo'] ?>">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Volver a liquidación -->
<a href="<?= base_url('bitacora/liquidacion') ?>" class="btn btn-outline-secondary btn-sm w-100 mt-3">
    <i class="bi bi-arrow-left me-1"></i> Volver a Liquidación
</a>

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

    // Agregar festivo
    var btnAgregar = document.getElementById('btnAgregar');
    if (btnAgregar) {
        btnAgregar.addEventListener('click', function() {
            var fecha = document.getElementById('inputFecha').value;
            var desc = document.getElementById('inputDesc').value.trim();
            if (!fecha || !desc) { alert('Fecha y descripción son requeridas'); return; }

            btnAgregar.disabled = true;
            ajax('POST', 'bitacora/festivos/guardar', { fecha: fecha, descripcion: desc })
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

    // Eliminar festivo
    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('¿Eliminar este día festivo?')) return;
            var id = btn.getAttribute('data-id');
            btn.disabled = true;

            ajax('POST', 'bitacora/festivos/eliminar/' + id, {})
                .then(function(resp) {
                    if (resp.ok) {
                        var card = document.getElementById('festivo-' + id);
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
