<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<h6 class="text-muted mb-3">
    <i class="bi bi-clock-history me-1"></i> Historial de Actividades
</h6>

<!-- Selector de fecha -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('bitacora/historial/' . date('Y-m-d', strtotime($fecha . ' -1 day'))) ?>"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i>
            </a>
            <input type="date" class="form-control form-control-sm text-center" id="inputFecha"
                   value="<?= $fecha ?>" max="<?= date('Y-m-d') ?>">
            <a href="<?= base_url('bitacora/historial/' . date('Y-m-d', strtotime($fecha . ' +1 day'))) ?>"
               class="btn btn-sm btn-outline-secondary <?= $fecha >= date('Y-m-d') ? 'disabled' : '' ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        <div class="text-center text-muted small mt-1">
            <?php
            $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
            $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            $ts = strtotime($fecha);
            echo $dias[date('w', $ts)] . ' ' . date('d', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' ' . date('Y', $ts);
            ?>
        </div>
    </div>
</div>

<!-- Total del día -->
<?php if (!empty($actividades)): ?>
<div class="total-horas mb-3">
    <i class="bi bi-clock me-1"></i>
    Total: <strong><?= formatMinutosHoras($totalMinutos) ?></strong>
    <span class="d-block small" style="opacity:0.7;"><?= count($actividades) ?> actividad(es)</span>
</div>
<?php endif; ?>

<!-- Lista de actividades -->
<div>
    <?php if (empty($actividades)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Sin actividades en esta fecha
        </div>
    <?php else: ?>
        <?php foreach ($actividades as $act): ?>
            <div class="actividad-card <?= $act['estado'] ?>">
                <div class="d-flex align-items-start gap-2">
                    <span class="num"><?= $act['numero_actividad'] ?></span>
                    <div class="flex-grow-1">
                        <div class="fw-bold small"><?= esc($act['descripcion']) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-person-badge"></i> <?= esc($act['cliente'] ?? 'FRAMEWORK') ?>
                        </div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-building"></i> <?= esc($act['centro_costo_nombre'] ?? '') ?>
                        </div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <?= date('h:i A', strtotime($act['hora_inicio'])) ?>
                            <?php if ($act['hora_fin']): ?>
                                — <?= date('h:i A', strtotime($act['hora_fin'])) ?>
                                <span class="badge bg-secondary ms-1">
                                    <?= formatMinutosHoras((float)$act['duracion_minutos']) ?>
                                </span>
                            <?php else: ?>
                                — <span class="badge bg-success">En progreso</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($act['estado'] === 'finalizada'): ?>
                        <button type="button" class="btn btn-sm btn-outline-warning mt-1 py-0 px-2 btn-correccion"
                                data-id="<?= $act['id_bitacora'] ?>"
                                data-desc="<?= esc($act['descripcion']) ?>"
                                data-hora-fin="<?= date('H:i', strtotime($act['hora_fin'])) ?>"
                                data-fecha="<?= $act['fecha'] ?>"
                                style="font-size: 0.7rem;">
                            <i class="bi bi-pencil"></i> Solicitar corrección
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<!-- Modal Solicitar Corrección -->
<div class="modal fade" id="modalCorreccion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Solicitar Corrección</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <div class="text-muted small">Actividad</div>
                    <div class="fw-bold small" id="corrDescripcion"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Hora fin actual</label>
                    <input type="time" class="form-control form-control-sm" id="corrHoraActual" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Nueva hora fin</label>
                    <input type="time" class="form-control form-control-sm" id="corrHoraNueva" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1">Motivo (opcional)</label>
                    <textarea class="form-control form-control-sm" id="corrMotivo" rows="2" placeholder="Ej: La hora de fin real fue 6:15 PM"></textarea>
                </div>
                <input type="hidden" id="corrIdBitacora">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-warning" id="btnEnviarCorreccion">
                    <i class="bi bi-send me-1"></i> Enviar solicitud
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('inputFecha').addEventListener('change', function() {
    window.location.href = '<?= base_url('bitacora/historial/') ?>' + this.value;
});

// Correcciones
document.querySelectorAll('.btn-correccion').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('corrIdBitacora').value = this.dataset.id;
        document.getElementById('corrDescripcion').textContent = this.dataset.desc;
        document.getElementById('corrHoraActual').value = this.dataset.horaFin;
        document.getElementById('corrHoraNueva').value = '';
        document.getElementById('corrMotivo').value = '';
        new bootstrap.Modal(document.getElementById('modalCorreccion')).show();
    });
});

document.getElementById('btnEnviarCorreccion').addEventListener('click', async function() {
    const id = document.getElementById('corrIdBitacora').value;
    const horaNueva = document.getElementById('corrHoraNueva').value;
    const motivo = document.getElementById('corrMotivo').value;

    if (!horaNueva) {
        alert('Ingresa la nueva hora de fin');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';

    try {
        const resp = await fetch('<?= base_url('bitacora/correccion/solicitar') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                id_bitacora: id,
                hora_fin_nueva: horaNueva,
                motivo: motivo,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            })
        });
        const data = await resp.json();
        if (data.ok) {
            bootstrap.Modal.getInstance(document.getElementById('modalCorreccion')).hide();
            alert('Solicitud enviada. Se notificará al administrador por email para su aprobación.');
        } else {
            alert(data.error || 'Error al enviar');
        }
    } catch (e) {
        alert('Error de conexión');
    }

    this.disabled = false;
    this.innerHTML = '<i class="bi bi-send me-1"></i> Enviar solicitud';
});
</script>
<?= $this->endSection() ?>
