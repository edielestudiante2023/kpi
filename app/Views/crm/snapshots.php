<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snapshots semanales – CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .kpi-card { background: #fff; border-radius: 8px; padding: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .kpi-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; }
        .kpi-value { font-size: 1.3rem; font-weight: 700; color: #2c3e50; line-height: 1.1; }
        .kpi-sub { font-size: 0.7rem; color: #6c757d; margin-top: 2px; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-3 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-0"><i class="bi bi-camera me-2"></i>Snapshots semanales del pipeline</h1>
            <div class="text-muted small">Foto congelada del estado del CRM en un momento. Sirve para comparar avance y alimentar al asistente IA.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('crm/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al CRM
            </a>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGenerar">
                <i class="bi bi-camera-fill me-1"></i> Generar snapshot ahora
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($masReciente): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clock-history me-1"></i>Snapshot más reciente</span>
            <small class="text-muted">
                <?= date('d/m/Y H:i', strtotime($masReciente['fecha_corte'])) ?>
            </small>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3"><div class="kpi-card">
                    <div class="kpi-label">Pipeline abierto</div>
                    <div class="kpi-value text-primary"><?= (int) $masReciente['total_abiertas'] ?></div>
                    <div class="kpi-sub">$<?= number_format((float) $masReciente['valor_pipeline'], 0, ',', '.') ?></div>
                </div></div>
                <div class="col-md-3"><div class="kpi-card">
                    <div class="kpi-label">Ganadas año</div>
                    <div class="kpi-value text-success"><?= (int) $masReciente['total_ganadas_anio'] ?></div>
                    <div class="kpi-sub">$<?= number_format((float) $masReciente['valor_ganadas_anio'], 0, ',', '.') ?></div>
                </div></div>
                <div class="col-md-3"><div class="kpi-card">
                    <div class="kpi-label">Tasa conversión año</div>
                    <div class="kpi-value"><?= (float) $masReciente['tasa_conversion_anio'] ?>%</div>
                    <div class="kpi-sub">Ganadas / cerradas</div>
                </div></div>
                <div class="col-md-3"><div class="kpi-card">
                    <div class="kpi-label">Estancadas (>30d)</div>
                    <div class="kpi-value text-warning"><?= (int) $masReciente['oportunidades_estancadas_30d'] ?></div>
                    <div class="kpi-sub">Sin actividad reciente</div>
                </div></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historial -->
    <div class="card shadow-sm">
        <div class="card-header bg-white"><i class="bi bi-list-ul me-1"></i>Historial</div>
        <div class="card-body p-0">
            <?php if (empty($snapshots)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-camera fs-1 d-block mb-2"></i>
                    No hay snapshots todavía. Genera el primero con el botón de arriba.
                </div>
            <?php else: ?>
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Abiertas</th>
                            <th class="text-end">Valor pipeline</th>
                            <th class="text-end">Ganadas año</th>
                            <th class="text-end">Conv.</th>
                            <th class="text-end">Estancadas</th>
                            <th>Notas</th>
                            <th>Autor</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($snapshots as $s): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($s['fecha_corte'])) ?></td>
                            <td class="text-end"><?= (int) $s['total_abiertas'] ?></td>
                            <td class="text-end">$<?= number_format((float) $s['valor_pipeline'], 0, ',', '.') ?></td>
                            <td class="text-end"><?= (int) $s['total_ganadas_anio'] ?></td>
                            <td class="text-end"><?= (float) $s['tasa_conversion_anio'] ?>%</td>
                            <td class="text-end"><?= (int) $s['oportunidades_estancadas_30d'] ?></td>
                            <td><small><?= esc($s['notas'] ?? '—') ?></small></td>
                            <td><small><?= esc($s['autor_nombre'] ?? '—') ?></small></td>
                            <td class="text-center text-nowrap">
                                <a href="<?= base_url('crm/snapshots/ver/' . $s['id_snapshot']) ?>"
                                   class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= base_url('crm/snapshots/eliminar/' . $s['id_snapshot']) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Eliminar este snapshot?')" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: generar snapshot -->
<div class="modal fade" id="modalGenerar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= base_url('crm/snapshots/generar') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-camera-fill me-1"></i>Generar snapshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Esto capturará el estado actual del pipeline (oportunidades abiertas, ganadas, perdidas,
                        breakdown por etapa, ranking por responsable, top motivos de pérdida, etc.) y lo guardará
                        como referencia histórica.
                    </p>
                    <label class="form-label small">Notas (opcional)</label>
                    <textarea name="notas" class="form-control form-control-sm" rows="2"
                              placeholder="Ej: antes de la junta del 20 may, cierre de mes, etc."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Generar snapshot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
