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
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
document.getElementById('inputFecha').addEventListener('change', function() {
    window.location.href = '<?= base_url('bitacora/historial/') ?>' + this.value;
});
</script>
<?= $this->endSection() ?>
