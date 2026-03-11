<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<h6 class="mb-3"><i class="bi bi-person-check me-1"></i> Novedades Individuales</h6>
<p class="text-muted small mb-3">Reducciones de jornada autorizadas <strong>persona a persona</strong>.</p>

<!-- Formulario agregar -->
<div class="card shadow-sm mb-3">
    <div class="card-body p-2">
        <div class="row g-2 align-items-end">
            <div class="col-12 mb-1">
                <label class="form-label small mb-1">Usuario</label>
                <select class="form-select form-select-sm" id="selectUsuario" style="width:100%">
                    <option value="">Seleccionar usuario...</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id_users'] ?>"><?= esc($u['nombre_completo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4">
                <label class="form-label small mb-1">Fecha</label>
                <input type="date" class="form-control form-control-sm" id="inputFecha" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-2">
                <label class="form-label small mb-1">Horas</label>
                <input type="number" class="form-control form-control-sm" id="inputHoras" min="0.5" max="8" step="0.5" value="2">
            </div>
            <div class="col-4">
                <label class="form-label small mb-1">Motivo</label>
                <input type="text" class="form-control form-control-sm" id="inputMotivo" placeholder="Ej: Cita médica">
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
        <i class="bi bi-person-x fs-1"></i>
        <p class="mt-2 small">No hay novedades individuales registradas</p>
    </div>
    <?php else: ?>
    <?php foreach ($novedades as $n): ?>
    <div class="card shadow-sm mb-2" id="nov-<?= $n['id_novedad_individual'] ?>">
        <div class="card-body p-2 d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold small"><?= esc($n['nombre_completo']) ?></div>
                <div class="text-muted" style="font-size: 0.7rem;">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d/m/Y', strtotime($n['fecha'])) ?>
                    &middot;
                    <span class="badge bg-info text-dark"><?= $n['horas_reduccion'] ?>h</span>
                    &middot;
                    <?= esc($n['motivo']) ?>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-danger btn-eliminar" data-id="<?= $n['id_novedad_individual'] ?>">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Navegación -->
<div class="mt-3 d-flex flex-column gap-2">
    <a href="<?= base_url('bitacora/novedades-colectivas') ?>" class="btn btn-outline-info btn-sm w-100">
        <i class="bi bi-calendar2-check me-1"></i> Novedades Colectivas (Fechas Especiales)
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

    // Select2
    $('#selectUsuario').select2({
        theme: 'bootstrap-5',
        placeholder: 'Seleccionar usuario...',
        allowClear: true
    });

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
            var usuario = document.getElementById('selectUsuario').value;
            var fecha = document.getElementById('inputFecha').value;
            var horas = document.getElementById('inputHoras').value;
            var motivo = document.getElementById('inputMotivo').value.trim();

            if (!usuario || !fecha || !horas || parseFloat(horas) <= 0 || !motivo) {
                alert('Todos los campos son requeridos');
                return;
            }

            btnAgregar.disabled = true;
            ajax('POST', 'bitacora/novedades-individuales/guardar', {
                id_usuario: usuario,
                fecha: fecha,
                horas_reduccion: horas,
                motivo: motivo
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
            if (!confirm('¿Eliminar esta novedad individual?')) return;
            var id = btn.getAttribute('data-id');
            btn.disabled = true;

            ajax('POST', 'bitacora/novedades-individuales/eliminar/' + id, {})
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
