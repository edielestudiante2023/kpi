<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snapshot #<?= (int) $snap['id_snapshot'] ?> – CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .kpi-card { background: #fff; border-radius: 8px; padding: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .kpi-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; }
        .kpi-value { font-size: 1.4rem; font-weight: 700; color: #2c3e50; line-height: 1.1; }
        .kpi-delta { font-size: 0.75rem; margin-top: 4px; font-weight: 600; }
        .kpi-delta.pos { color: #198754; }
        .kpi-delta.neg { color: #dc3545; }
        .kpi-delta.zero { color: #6c757d; }
        .seccion { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 16px; }
        .seccion h6 { font-weight: 600; color: #2c3e50; margin-bottom: 12px; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php
function delta($actual, $anterior, $isMoney = false, $isPercent = false) {
    if ($anterior === null) return '<span class="kpi-delta zero">primera vez</span>';
    $diff = $actual - $anterior;
    if ($diff == 0) return '<span class="kpi-delta zero">sin cambio</span>';
    $cls = $diff > 0 ? 'pos' : 'neg';
    $arrow = $diff > 0 ? '▲' : '▼';
    if ($isMoney) {
        $txt = '$' . number_format(abs($diff), 0, ',', '.');
    } elseif ($isPercent) {
        $txt = number_format(abs($diff), 1) . ' pts';
    } else {
        $txt = abs($diff);
    }
    return "<span class=\"kpi-delta {$cls}\">{$arrow} {$txt}</span>";
}
?>

<div class="container-fluid py-3 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-0">
                <i class="bi bi-camera me-2"></i>Snapshot #<?= (int) $snap['id_snapshot'] ?>
                <small class="text-muted ms-2"><?= date('d/m/Y H:i', strtotime($snap['fecha_corte'])) ?></small>
            </h1>
            <?php if (!empty($snap['notas'])): ?>
                <div class="small text-muted mt-1"><i class="bi bi-pencil me-1"></i><?= esc($snap['notas']) ?></div>
            <?php endif; ?>
            <div class="small text-muted">Generado por <?= esc($snap['autor_nombre'] ?? '—') ?></div>
        </div>
        <a href="<?= base_url('crm/snapshots') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if ($anterior): ?>
    <div class="alert alert-info py-2 small">
        <strong>Comparativo:</strong> deltas vs snapshot anterior del
        <?= date('d/m/Y H:i', strtotime($anterior['fecha_corte'])) ?>.
    </div>
    <?php endif; ?>

    <!-- KPIs principales -->
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Pipeline abierto</div>
            <div class="kpi-value text-primary"><?= (int) $snap['total_abiertas'] ?></div>
            <div class="kpi-sub small text-muted">$<?= number_format((float) $snap['valor_pipeline'], 0, ',', '.') ?></div>
            <?= delta((int) $snap['total_abiertas'], $anterior['total_abiertas'] ?? null) ?>
        </div></div>
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Ganadas año</div>
            <div class="kpi-value text-success"><?= (int) $snap['total_ganadas_anio'] ?></div>
            <div class="kpi-sub small text-muted">$<?= number_format((float) $snap['valor_ganadas_anio'], 0, ',', '.') ?></div>
            <?= delta((int) $snap['total_ganadas_anio'], $anterior['total_ganadas_anio'] ?? null) ?>
        </div></div>
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Perdidas año</div>
            <div class="kpi-value text-danger"><?= (int) $snap['total_perdidas_anio'] ?></div>
            <div class="kpi-sub small text-muted">$<?= number_format((float) $snap['valor_perdidas_anio'], 0, ',', '.') ?></div>
            <?= delta((int) $snap['total_perdidas_anio'], $anterior['total_perdidas_anio'] ?? null) ?>
        </div></div>
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Tasa conversión</div>
            <div class="kpi-value"><?= (float) $snap['tasa_conversion_anio'] ?>%</div>
            <div class="kpi-sub small text-muted">Ganadas / cerradas año</div>
            <?= delta((float) $snap['tasa_conversion_anio'], $anterior['tasa_conversion_anio'] ?? null, false, true) ?>
        </div></div>
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Ciclo promedio</div>
            <div class="kpi-value"><?= $snap['ciclo_promedio_dias'] !== null ? (int) $snap['ciclo_promedio_dias'] . ' d' : '—' ?></div>
            <div class="kpi-sub small text-muted">Días creación → cierre</div>
            <?php if ($snap['ciclo_promedio_dias'] !== null): ?>
                <?= delta((int) $snap['ciclo_promedio_dias'], $anterior['ciclo_promedio_dias'] ?? null) ?>
            <?php endif; ?>
        </div></div>
        <div class="col-md-3"><div class="kpi-card">
            <div class="kpi-label">Estancadas (>30d)</div>
            <div class="kpi-value text-warning"><?= (int) $snap['oportunidades_estancadas_30d'] ?></div>
            <div class="kpi-sub small text-muted">Sin actividad</div>
            <?= delta((int) $snap['oportunidades_estancadas_30d'], $anterior['oportunidades_estancadas_30d'] ?? null) ?>
        </div></div>
    </div>

    <!-- Breakdown por etapa -->
    <div class="seccion">
        <h6><i class="bi bi-diagram-3 me-1"></i>Pipeline por etapa</h6>
        <?php if (empty($snap['por_etapa'])): ?>
            <div class="small text-muted text-center py-3">Sin oportunidades abiertas en este snapshot.</div>
        <?php else: ?>
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Etapa</th><th class="text-end">Cantidad</th><th class="text-end">Valor total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($snap['por_etapa'] as $e): ?>
                        <tr>
                            <td><span class="badge" style="background-color: <?= esc($e['color'] ?? '#6c757d') ?>"><?= esc($e['nombre']) ?></span></td>
                            <td class="text-end"><?= (int) $e['cantidad'] ?></td>
                            <td class="text-end">$<?= number_format((float) $e['valor_total'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="row g-3">
        <!-- Ranking responsables -->
        <div class="col-md-7">
            <div class="seccion">
                <h6><i class="bi bi-people me-1"></i>Ranking por responsable</h6>
                <?php if (empty($snap['por_responsable'])): ?>
                    <div class="small text-muted text-center py-3">Sin datos.</div>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vendedor</th>
                                <th class="text-end">Abiertas</th>
                                <th class="text-end">Pipeline</th>
                                <th class="text-end">Ganadas año</th>
                                <th class="text-end">Valor ganado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($snap['por_responsable'] as $r): ?>
                                <tr>
                                    <td class="small"><?= esc($r['nombre_completo']) ?></td>
                                    <td class="text-end"><?= (int) $r['abiertas'] ?></td>
                                    <td class="text-end">$<?= number_format((float) $r['valor_abierto'], 0, ',', '.') ?></td>
                                    <td class="text-end fw-bold text-success"><?= (int) $r['ganadas'] ?></td>
                                    <td class="text-end fw-bold text-success">$<?= number_format((float) $r['valor_ganado'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top motivos de pérdida -->
        <div class="col-md-5">
            <div class="seccion">
                <h6><i class="bi bi-x-circle me-1"></i>Top motivos de pérdida (año)</h6>
                <?php if (empty($snap['motivos_perdida_top'])): ?>
                    <div class="small text-muted text-center py-3">Sin oportunidades perdidas con motivo asignado.</div>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Motivo</th><th class="text-end">Cant.</th><th class="text-end">Valor</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($snap['motivos_perdida_top'] as $m): ?>
                                <tr>
                                    <td class="small"><?= esc($m['nombre']) ?></td>
                                    <td class="text-end"><?= (int) $m['cantidad'] ?></td>
                                    <td class="text-end">$<?= number_format((float) $m['valor_total'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
