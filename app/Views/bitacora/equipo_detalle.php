<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<?php
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$dias = ['Dom','Lun','Mar','Mie','Jue','Vie','Sab'];
?>

<h6 class="text-muted mb-2">
    <a href="<?= base_url("bitacora/equipo/{$anio}/{$mes}") ?>" class="text-decoration-none text-muted">
        <i class="bi bi-arrow-left me-1"></i>
    </a>
    <i class="bi bi-person me-1"></i> <?= esc($nombreUsuario) ?>
</h6>
<div class="text-muted small mb-3"><?= $meses[$mes] ?> <?= $anio ?></div>

<!-- Totales -->
<?php
$totalMin = 0;
$totalAct = 0;
foreach ($resumen as $r) {
    $totalMin += (float)$r['total_minutos'];
    $totalAct += (int)$r['num_actividades'];
}
?>
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <strong><?= formatMinutosHoras($totalMin) ?></strong>
            <div class="small" style="opacity:0.7;">Total</div>
        </div>
    </div>
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <strong><?= count($resumen) ?></strong>
            <div class="small" style="opacity:0.7;">Dias</div>
        </div>
    </div>
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <strong><?= $totalAct ?></strong>
            <div class="small" style="opacity:0.7;">Actividades</div>
        </div>
    </div>
</div>

<!-- Tabla de dias -->
<?php if (empty($resumen)): ?>
    <div class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Sin registros
    </div>
<?php else: ?>
    <?php foreach ($resumen as $r):
        $ts = strtotime($r['fecha']);
        $diaSemana = $dias[(int)date('w', $ts)];
        $diaNum = date('d', $ts);
        $minutos = (float)$r['total_minutos'];
        $horasDecimal = $minutos / 60;
        $colorBorder = '#dc3545';
        if ($horasDecimal >= 8) $colorBorder = '#198754';
        elseif ($horasDecimal >= 6) $colorBorder = '#ffc107';
    ?>
        <div class="actividad-card mb-2" style="border-left: 4px solid <?= $colorBorder ?>;">
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
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
