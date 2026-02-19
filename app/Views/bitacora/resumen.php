<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<?php
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$dias = ['Dom','Lun','Mar','Mie','Jue','Vie','Sab'];

$mesAnterior = $mes == 1 ? 12 : $mes - 1;
$anioAnterior = $mes == 1 ? $anio - 1 : $anio;
$mesSiguiente = $mes == 12 ? 1 : $mes + 1;
$anioSiguiente = $mes == 12 ? $anio + 1 : $anio;
$esMesActual = ($anio == date('Y') && $mes == date('n'));
?>

<h6 class="text-muted mb-3">
    <i class="bi bi-graph-up me-1"></i> Mi Productividad
</h6>

<!-- Selector de mes -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between">
            <a href="<?= base_url("bitacora/resumen/{$anioAnterior}/{$mesAnterior}") ?>"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span class="fw-bold"><?= $meses[$mes] ?> <?= $anio ?></span>
            <?php if (!$esMesActual): ?>
                <a href="<?= base_url("bitacora/resumen/{$anioSiguiente}/{$mesSiguiente}") ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-secondary disabled"><i class="bi bi-chevron-right"></i></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Totales del mes -->
<?php
$totalMinutosMes = 0;
$totalActividadesMes = 0;
foreach ($resumen as $r) {
    $totalMinutosMes += (float)$r['total_minutos'];
    $totalActividadesMes += (int)$r['num_actividades'];
}
?>
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-clock"></i><br>
            <strong><?= formatMinutosHoras($totalMinutosMes) ?></strong>
            <div class="small" style="opacity:0.7;">Total mes</div>
        </div>
    </div>
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-calendar-check"></i><br>
            <strong><?= count($resumen) ?></strong>
            <div class="small" style="opacity:0.7;">Dias</div>
        </div>
    </div>
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-list-check"></i><br>
            <strong><?= $totalActividadesMes ?></strong>
            <div class="small" style="opacity:0.7;">Actividades</div>
        </div>
    </div>
</div>

<!-- Tabla de dias -->
<?php if (empty($resumen)): ?>
    <div class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Sin registros en <?= $meses[$mes] ?> <?= $anio ?>
    </div>
<?php else: ?>
    <?php foreach ($resumen as $r):
        $ts = strtotime($r['fecha']);
        $diaSemana = $dias[(int)date('w', $ts)];
        $diaNum = date('d', $ts);
        $minutos = (float)$r['total_minutos'];
        $horasDecimal = $minutos / 60;
        // Color segun horas: <6 rojo, 6-8 amarillo, 8+ verde
        $colorBg = '#f8d7da'; $colorBorder = '#dc3545';
        if ($horasDecimal >= 8) { $colorBg = '#d1e7dd'; $colorBorder = '#198754'; }
        elseif ($horasDecimal >= 6) { $colorBg = '#fff3cd'; $colorBorder = '#ffc107'; }
    ?>
        <a href="<?= base_url('bitacora/historial/' . $r['fecha']) ?>" class="text-decoration-none">
            <div class="actividad-card mb-2" style="border-left: 4px solid <?= $colorBorder ?>;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="text-center" style="min-width:42px;">
                            <div class="text-muted" style="font-size:0.65rem;"><?= $diaSemana ?></div>
                            <div class="fw-bold" style="font-size:1.2rem; color:#2c3e50;"><?= $diaNum ?></div>
                        </div>
                        <div>
                            <div class="fw-bold small" style="color:#2c3e50;">
                                <?= formatMinutosHoras($minutos) ?>
                            </div>
                            <div class="text-muted" style="font-size:0.7rem;">
                                <?= $r['num_actividades'] ?> actividad(es)
                                &middot;
                                <?= date('h:i A', strtotime($r['primera_entrada'])) ?>
                                — <?= date('h:i A', strtotime($r['ultima_salida'])) ?>
                            </div>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
