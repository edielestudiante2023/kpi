<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<h6 class="mb-3"><i class="bi bi-hourglass-split me-1"></i> Consumir tiempo adicional</h6>
<p class="text-muted small mb-3">
    Saldo a favor de cada persona: horas trabajadas por encima de la meta, acumuladas
    quincena a quincena. Consumir tiempo crea una novedad individual que reduce la meta de esa fecha.
</p>

<!-- Formulario: registrar consumo -->
<div class="card shadow-sm mb-3">
    <div class="card-body p-2">
        <div class="fw-bold small mb-2"><i class="bi bi-dash-circle me-1"></i> Registrar uso de tiempo adicional</div>
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
                <input type="number" class="form-control form-control-sm" id="inputHoras" min="0.5" step="0.5" value="2">
            </div>
            <div class="col-4">
                <label class="form-label small mb-1">Motivo</label>
                <input type="text" class="form-control form-control-sm" id="inputMotivo" placeholder="Ej: Tarde libre">
            </div>
            <div class="col-2">
                <button class="btn btn-sm btn-primary w-100" id="btnRegistrar">
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        </div>
        <div class="text-muted mt-1" style="font-size:0.7rem;">
            Si la persona no tiene saldo suficiente, igual se registra: el saldo queda en
            negativo y la deuda se arrastra al siguiente periodo.
        </div>
    </div>
</div>

<!-- Lista de saldos por usuario -->
<?php if (empty($resumen)): ?>
    <div class="text-center text-muted py-4">
        <i class="bi bi-hourglass fs-1"></i>
        <p class="mt-2 small">Sin usuarios con bitácora habilitada</p>
    </div>
<?php else: ?>
    <?php foreach ($resumen as $r):
        $colorDisp = $r['disponible'] > 0 ? 'success' : ($r['disponible'] < 0 ? 'danger' : 'secondary');
    ?>
    <div class="card shadow-sm mb-2">
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-center"
                 role="button" data-bs-toggle="collapse" data-bs-target="#det-<?= $r['id_users'] ?>">
                <div>
                    <div class="fw-bold small"><?= esc($r['nombre_completo']) ?></div>
                    <div class="text-muted" style="font-size:0.7rem;">
                        Acumulado: <?= $r['acumulado'] ?>h
                        &middot;
                        Consumido: <?= $r['consumido'] ?>h
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-<?= $colorDisp ?>" style="font-size:0.8rem;">
                        <?= $r['disponible'] ?>h disp.
                    </span>
                    <i class="bi bi-chevron-down text-muted ms-1"></i>
                </div>
            </div>

            <div class="collapse mt-2" id="det-<?= $r['id_users'] ?>">
                <hr class="my-2">
                <!-- Acumulaciones por quincena -->
                <div class="small fw-bold text-success mb-1">
                    <i class="bi bi-plus-circle me-1"></i> Acumulado por quincena
                </div>
                <?php if (empty($r['acumulaciones'])): ?>
                    <div class="text-muted" style="font-size:0.72rem;">Sin excedentes registrados.</div>
                <?php else: ?>
                    <?php foreach ($r['acumulaciones'] as $a): ?>
                    <div class="d-flex justify-content-between" style="font-size:0.72rem;">
                        <span class="text-muted">
                            <?= date('d/m/Y', strtotime($a['fecha_inicio'])) ?>
                            — <?= date('d/m/Y', strtotime($a['fecha_corte'])) ?>
                        </span>
                        <span class="text-success fw-bold">+<?= $a['horas_adicionales'] ?>h</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Consumos -->
                <div class="small fw-bold text-danger mb-1 mt-2">
                    <i class="bi bi-dash-circle me-1"></i> Tiempo consumido
                </div>
                <?php if (empty($r['consumos'])): ?>
                    <div class="text-muted" style="font-size:0.72rem;">Sin consumos registrados.</div>
                <?php else: ?>
                    <?php foreach ($r['consumos'] as $c): ?>
                    <div class="d-flex justify-content-between align-items-center" id="consumo-<?= $c['id_novedad_individual'] ?>" style="font-size:0.72rem;">
                        <span class="text-muted">
                            <?= date('d/m/Y', strtotime($c['fecha'])) ?>
                            &middot; <?= esc($c['motivo']) ?>
                        </span>
                        <span>
                            <span class="text-danger fw-bold me-2">-<?= $c['horas_reduccion'] ?>h</span>
                            <button class="btn btn-sm btn-outline-danger py-0 px-1 btn-eliminar-consumo"
                                    data-id="<?= $c['id_novedad_individual'] ?>">
                                <i class="bi bi-trash" style="font-size:0.7rem;"></i>
                            </button>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

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
    // Saldo disponible por usuario, para avisar si el consumo lo deja en negativo
    var SALDOS = <?= json_encode(array_column($resumen, 'disponible', 'id_users')) ?>;

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

    var btnRegistrar = document.getElementById('btnRegistrar');
    if (btnRegistrar) {
        btnRegistrar.addEventListener('click', function() {
            var usuario = document.getElementById('selectUsuario').value;
            var fecha   = document.getElementById('inputFecha').value;
            var horas   = document.getElementById('inputHoras').value;
            var motivo  = document.getElementById('inputMotivo').value.trim();

            if (!usuario || !fecha || !horas || parseFloat(horas) <= 0 || !motivo) {
                alert('Todos los campos son requeridos');
                return;
            }

            // Avisar si el consumo deja el saldo en negativo
            var disp = SALDOS[usuario];
            if (typeof disp !== 'undefined' && parseFloat(horas) > disp) {
                var nuevoSaldo = (disp - parseFloat(horas)).toFixed(2);
                if (!confirm('La persona no tiene saldo suficiente. Esto dejará su saldo en '
                    + nuevoSaldo + 'h y la deuda se arrastra al siguiente periodo. ¿Continuar?')) {
                    return;
                }
            }

            btnRegistrar.disabled = true;
            ajax('POST', 'bitacora/tiempo-adicional/registrar', {
                id_usuario: usuario,
                fecha: fecha,
                horas: horas,
                motivo: motivo
            })
            .then(function(resp) {
                if (resp.ok) {
                    location.reload();
                } else {
                    alert(resp.error || 'Error al registrar');
                    btnRegistrar.disabled = false;
                }
            })
            .catch(function() {
                alert('Error de conexión');
                btnRegistrar.disabled = false;
            });
        });
    }

    document.querySelectorAll('.btn-eliminar-consumo').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!confirm('¿Eliminar este consumo? El saldo se devolverá.')) return;
            var id = btn.getAttribute('data-id');
            btn.disabled = true;

            ajax('POST', 'bitacora/tiempo-adicional/eliminar/' + id, {})
                .then(function(resp) {
                    if (resp.ok) {
                        location.reload();
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
