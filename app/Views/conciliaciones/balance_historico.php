<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Balance – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div class="toast show align-items-center text-white bg-success border-0">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-check-circle me-1"></i><?= session()->getFlashdata('success') ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><i class="bi bi-clock-history me-2"></i>Histórico de Cierres Mensuales</h1>
        <a href="<?= base_url('conciliaciones/balance') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al balance
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha corte</th>
                            <th class="text-end">Cartera SST</th>
                            <th class="text-end">Cartera RPS</th>
                            <th class="text-end">Bancos SST</th>
                            <th class="text-end">Bancos RPS</th>
                            <th class="text-end">Activos</th>
                            <th class="text-end">Pasivos</th>
                            <th class="text-end">Estado</th>
                            <th>Cerrado por</th>
                            <th>Fecha cierre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($snapshots)): ?>
                        <tr><td colspan="11" class="text-center text-muted py-4">Sin snapshots todavía. Crear el primero desde <a href="<?= base_url('conciliaciones/balance') ?>">la vista de balance</a>.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($snapshots as $s): ?>
                        <tr>
                            <td><strong><?= date('d/m/Y', strtotime($s['fecha_corte'])) ?></strong></td>
                            <td class="text-end">$<?= number_format($s['cartera_sst'], 0, ',', '.') ?></td>
                            <td class="text-end">$<?= number_format($s['cartera_rps'], 0, ',', '.') ?></td>
                            <td class="text-end">$<?= number_format($s['saldo_banco_sst'], 0, ',', '.') ?></td>
                            <td class="text-end">$<?= number_format($s['saldo_banco_rps'], 0, ',', '.') ?></td>
                            <td class="text-end fw-bold">$<?= number_format($s['total_activos'], 0, ',', '.') ?></td>
                            <td class="text-end fw-bold">$<?= number_format($s['total_pasivos'], 0, ',', '.') ?></td>
                            <td class="text-end fw-bold <?= $s['estado_empresa'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $s['estado_empresa'] < 0 ? '-' : '' ?>$<?= number_format(abs($s['estado_empresa']), 0, ',', '.') ?>
                            </td>
                            <td><?= esc($s['creado_por'] ?? '—') ?></td>
                            <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></small></td>
                            <td>
                                <a href="<?= base_url('conciliaciones/balance?corte=' . $s['fecha_corte']) ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= base_url('conciliaciones/balance/eliminar-snapshot/' . $s['id_snapshot']) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Eliminar snapshot del <?= date('d/m/Y', strtotime($s['fecha_corte'])) ?>? Esta acción permitirá recerrar el mes con datos actualizados.')"
                                   title="Eliminar snapshot">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
