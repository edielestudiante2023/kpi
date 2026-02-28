<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<?php
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

$mesAnterior = $mes == 1 ? 12 : $mes - 1;
$anioAnterior = $mes == 1 ? $anio - 1 : $anio;
$mesSiguiente = $mes == 12 ? 1 : $mes + 1;
$anioSiguiente = $mes == 12 ? $anio + 1 : $anio;
$esMesActual = ($anio == date('Y') && $mes == date('n'));
?>

<h6 class="text-muted mb-3">
    <i class="bi bi-people me-1"></i> Productividad del Equipo
</h6>

<!-- Selector de mes -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between">
            <a href="<?= base_url("bitacora/equipo/{$anioAnterior}/{$mesAnterior}") ?>"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span class="fw-bold"><?= $meses[$mes] ?> <?= $anio ?></span>
            <?php if (!$esMesActual): ?>
                <a href="<?= base_url("bitacora/equipo/{$anioSiguiente}/{$mesSiguiente}") ?>"
                   class="btn btn-sm btn-outline-secondary">
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
        Sin datos para <?= $meses[$mes] ?> <?= $anio ?>
    </div>
<?php else: ?>
    <?php foreach ($equipo as $u):
        $minutos = (float)$u['total_minutos'];
        $horasDecimal = $minutos / 60;
        $diasLaborales = (int)date('t', mktime(0, 0, 0, $mes, 1, $anio));
        // Estimado burdo de dias laborales (22 aprox)
        $diasLab = min(22, $diasLaborales);
        $porcentaje = $diasLab > 0 ? min(100, round(((int)$u['dias_registrados'] / $diasLab) * 100)) : 0;
        // Color segun promedio diario
        $promDiario = (int)$u['dias_registrados'] > 0 ? $horasDecimal / (int)$u['dias_registrados'] : 0;
        $colorBorder = '#dc3545';
        if ($promDiario >= 8) $colorBorder = '#198754';
        elseif ($promDiario >= 6) $colorBorder = '#ffc107';
    ?>
        <a href="<?= base_url("bitacora/equipo/detalle/{$u['id_users']}/{$anio}/{$mes}") ?>" class="text-decoration-none">
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
                        <!-- Barra de progreso: dias registrados vs dias laborales -->
                        <div class="progress mt-1" style="height: 4px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $porcentaje ?>%;"
                                 aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
