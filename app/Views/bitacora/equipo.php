<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<?php
// URL de una quincena: 'actual' → vigente (sin id), cerrada → con id_liquidacion
$urlQuincena = function ($p) {
    return $p['id'] === 'actual'
        ? base_url('bitacora/equipo')
        : base_url('bitacora/equipo/' . $p['id']);
};
// Sufijo para los links de detalle por usuario
$sufijoDetalle = $periodo['id'] === 'actual' ? '' : '/' . $periodo['id'];
?>

<h6 class="text-muted mb-3">
    <i class="bi bi-people me-1"></i> Productividad del Equipo
</h6>

<!-- Selector de quincena -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between">
            <?php if ($prev): ?>
                <a href="<?= $urlQuincena($prev) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-secondary disabled"><i class="bi bi-chevron-left"></i></span>
            <?php endif; ?>

            <div class="text-center">
                <span class="fw-bold"><?= esc($periodo['label']) ?></span>
                <?php if (!$periodo['cerrada']): ?>
                    <span class="badge bg-success ms-1">Vigente</span>
                <?php endif; ?>
            </div>

            <?php if ($next): ?>
                <a href="<?= $urlQuincena($next) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-secondary disabled"><i class="bi bi-chevron-right"></i></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lista de usuarios -->
<?php if (empty($equipo)): ?>
    <div class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Sin datos para esta quincena
    </div>
<?php else: ?>
    <?php foreach ($equipo as $u):
        $minutos = (float)$u['total_minutos'];
        $horasDecimal = $minutos / 60;
        // Color del borde segun promedio diario
        $promDiario = (int)$u['dias_registrados'] > 0 ? $horasDecimal / (int)$u['dias_registrados'] : 0;
        $colorBorder = '#dc3545';
        if ($promDiario >= 8) $colorBorder = '#198754';
        elseif ($promDiario >= 6) $colorBorder = '#ffc107';

        // Progreso de la quincena (horas trabajadas vs meta) — mismo dato del email / liquidacion
        $q = $progreso[(int)$u['id_users']] ?? null;
        $porcentaje = $q ? min(100, round($q['porcentaje'])) : 0;
        // Color de la barra segun cumplimiento de la meta quincenal
        $colorBarra = '#dc3545';
        if ($q && $q['porcentaje'] >= 100) $colorBarra = '#198754';
        elseif ($q && $q['porcentaje'] >= 80) $colorBarra = '#ffc107';
    ?>
        <a href="<?= base_url("bitacora/equipo/detalle/{$u['id_users']}") . $sufijoDetalle ?>" class="text-decoration-none">
            <div class="actividad-card mb-2" style="border-left: 4px solid <?= $colorBorder ?>;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold small" style="color:#2c3e50;">
                            <?= esc($u['nombre_completo']) ?>
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            <span class="me-2"><i class="bi bi-clock me-1"></i><?= formatMinutosHoras($minutos) ?></span>
                            <span class="me-2"><i class="bi bi-calendar-check me-1"></i><?= $u['dias_registrados'] ?> dias</span>
                            <span><i class="bi bi-list-check me-1"></i><?= $u['num_actividades'] ?> act.</span>
                        </div>
                        <!-- Barra de progreso: horas trabajadas vs meta de la quincena -->
                        <?php if ($q): ?>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: <?= $porcentaje ?>%; background-color: <?= $colorBarra ?>;"
                                     aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="text-muted" style="font-size:0.7rem;">
                                Quincena: <?= $q['horas_trabajadas'] ?>h / <?= $q['horas_meta'] ?>h meta
                                (<?= $q['porcentaje'] ?>%)
                            </div>
                        <?php endif; ?>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
