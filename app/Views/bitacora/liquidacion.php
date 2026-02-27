<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<h6 class="mb-3"><i class="bi bi-calculator me-1"></i> Liquidación Quincenal</h6>

<!-- Periodo actual -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold">Periodo Actual</span>
            <span class="badge bg-primary"><?= $diasHabiles ?> días hábiles</span>
        </div>
        <div class="text-muted small">
            <i class="bi bi-calendar-range me-1"></i>
            <?= date('d/m/Y h:i A', strtotime($fechaInicio)) ?>
            <i class="bi bi-arrow-right mx-1"></i>
            Hoy (<?= date('d/m/Y h:i A', strtotime($fechaHoy)) ?>)
        </div>
    </div>
</div>

<!-- Preview de usuarios -->
<div class="card shadow-sm mb-3">
    <div class="card-body p-2">
        <table class="table table-sm table-borderless mb-0" style="font-size: 0.8rem;">
            <thead>
                <tr class="text-muted">
                    <th>Usuario</th>
                    <th class="text-center">Jornada</th>
                    <th class="text-center">Horas</th>
                    <th class="text-center">Meta</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview as $p): ?>
                <?php
                    $color = 'text-danger';
                    if ($p['porcentaje'] >= 100) $color = 'text-success';
                    elseif ($p['porcentaje'] >= 80) $color = 'text-warning';
                ?>
                <tr>
                    <td><?= esc(explode(' ', $p['nombre_completo'])[0]) ?></td>
                    <td class="text-center">
                        <span class="badge <?= $p['jornada'] === 'media' ? 'bg-info' : 'bg-secondary' ?>" style="font-size: 0.65rem;">
                            <?= $p['jornada'] === 'media' ? '½' : 'Full' ?>
                        </span>
                    </td>
                    <td class="text-center fw-bold"><?= $p['horas_trabajadas'] ?>h</td>
                    <td class="text-center"><?= $p['horas_meta'] ?>h</td>
                    <td class="text-center fw-bold <?= $color ?>"><?= $p['porcentaje'] ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Botón liquidar -->
<button class="btn btn-danger w-100 mb-3" data-bs-toggle="modal" data-bs-target="#modalLiquidar">
    <i class="bi bi-lock me-1"></i> Liquidar Periodo
</button>

<!-- Modal confirmación -->
<div class="modal fade" id="modalLiquidar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-1"></i> Confirmar Liquidación</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Esta acción:</p>
                <ul class="small">
                    <li>Cerrará todas las actividades en progreso al momento actual</li>
                    <li>Calculará el porcentaje de cumplimiento de cada persona</li>
                    <li>Enviará un email de liquidación a cada usuario</li>
                    <li><strong>No se puede deshacer</strong></li>
                </ul>
                <div class="mb-0">
                    <label class="form-label small">Notas (opcional):</label>
                    <textarea class="form-control form-control-sm" id="txtNotas" rows="2" placeholder="Observaciones del periodo..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnConfirmarLiquidar">
                    <i class="bi bi-lock me-1"></i> Confirmar Liquidación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Link a festivos -->
<a href="<?= base_url('bitacora/festivos') ?>" class="btn btn-outline-secondary btn-sm w-100 mb-3">
    <i class="bi bi-calendar-event me-1"></i> Gestionar Días Festivos
</a>

<!-- Historial -->
<?php if (!empty($historial)): ?>
<h6 class="mt-4 mb-2"><i class="bi bi-clock-history me-1"></i> Historial de Liquidaciones</h6>
<?php foreach ($historial as $h): ?>
<div class="card shadow-sm mb-2">
    <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold small">
                    <?= date('d/m/Y', strtotime($h['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime($h['fecha_corte'])) ?>
                </div>
                <div class="text-muted" style="font-size: 0.7rem;">
                    <?= $h['dias_habiles'] ?> días hábiles · Por: <?= esc($h['ejecutor']) ?>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-primary btn-ver-detalle" data-id="<?= $h['id_liquidacion'] ?>">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <div class="detalle-liquidacion mt-2" id="detalle-<?= $h['id_liquidacion'] ?>" style="display:none;"></div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

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

    // Confirmar liquidación
    var btnConfirmar = document.getElementById('btnConfirmarLiquidar');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Liquidando...';

            var notas = document.getElementById('txtNotas').value;
            ajax('POST', 'bitacora/liquidacion/ejecutar', { notas: notas })
                .then(function(resp) {
                    if (resp.ok) {
                        alert('Liquidación completada. Se cortaron ' + resp.actividades_cortadas + ' actividades en progreso. Emails enviados.');
                        location.reload();
                    } else {
                        alert(resp.error || 'Error al liquidar');
                        btnConfirmar.disabled = false;
                        btnConfirmar.innerHTML = '<i class="bi bi-lock me-1"></i> Confirmar Liquidación';
                    }
                })
                .catch(function() {
                    alert('Error de conexión');
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = '<i class="bi bi-lock me-1"></i> Confirmar Liquidación';
                });
        });
    }

    // Ver detalle de liquidación pasada
    document.querySelectorAll('.btn-ver-detalle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-id');
            var container = document.getElementById('detalle-' + id);
            if (container.style.display !== 'none') {
                container.style.display = 'none';
                return;
            }
            container.innerHTML = '<div class="text-center p-2"><span class="spinner-border spinner-border-sm"></span></div>';
            container.style.display = 'block';

            fetch(BASE + 'bitacora/liquidacion/detalle/' + id)
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    if (!resp.ok) { container.innerHTML = '<p class="text-danger small">Error</p>'; return; }
                    var html = '<table class="table table-sm mb-0" style="font-size:0.75rem;"><thead><tr><th>Usuario</th><th class="text-center">Horas</th><th class="text-center">Meta</th><th class="text-center">%</th></tr></thead><tbody>';
                    resp.detalle.forEach(function(d) {
                        var color = d.porcentaje_cumplimiento >= 100 ? 'text-success' : (d.porcentaje_cumplimiento >= 80 ? 'text-warning' : 'text-danger');
                        html += '<tr><td>' + d.nombre_completo + '</td><td class="text-center">' + d.horas_trabajadas + 'h</td><td class="text-center">' + d.horas_meta + 'h</td><td class="text-center fw-bold ' + color + '">' + d.porcentaje_cumplimiento + '%</td></tr>';
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                });
        });
    });
})();
</script>
<?= $this->endSection() ?>
