<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Deuda – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="<?= base_url('conciliaciones/deudas') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><?= esc($deuda['concepto']) ?></h1>
        <?= $deuda['estado'] === 'activa'
            ? '<span class="badge bg-danger">Activa</span>'
            : '<span class="badge bg-success">Saldada</span>'
        ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><p><?= esc($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Info deuda -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Monto Original</h6>
                    <p class="h4 fw-bold">$<?= number_format((float)$deuda['monto_original'], 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h6 class="text-muted">Total Abonado</h6>
                    <p class="h4 fw-bold text-success">$<?= number_format($totalAbonado, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Saldo Pendiente</h6>
                    <p class="h4 fw-bold <?= $saldoPendiente > 0 ? 'text-danger' : 'text-success' ?>">
                        $<?= number_format($saldoPendiente, 0, ',', '.') ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Progreso</h6>
                    <?php $pct = $deuda['monto_original'] > 0 ? round($totalAbonado / $deuda['monto_original'] * 100, 1) : 0; ?>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= min($pct, 100) ?>%">
                            <?= $pct ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col">
            <p class="mb-1"><strong>Acreedor:</strong> <?= esc($deuda['acreedor']) ?></p>
            <p class="mb-1"><strong>Fecha registro:</strong> <?= date('d/m/Y', strtotime($deuda['fecha_registro'])) ?></p>
            <?php if ($deuda['fecha_vencimiento']): ?>
                <p class="mb-1"><strong>Vencimiento:</strong> <?= date('d/m/Y', strtotime($deuda['fecha_vencimiento'])) ?></p>
            <?php endif; ?>
            <?php if ($deuda['notas']): ?>
                <p class="mb-1"><strong>Notas:</strong> <?= esc($deuda['notas']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <hr>

    <!-- Formulario agregar abono -->
    <?php if ($deuda['estado'] === 'activa'): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <i class="bi bi-plus-circle me-1"></i> Registrar Abono
        </div>
        <div class="card-body">
            <form action="<?= base_url('conciliaciones/deudas/abono/'.$deuda['id_deuda']) ?>" method="post">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Fecha del Abono</label>
                        <input type="date" name="fecha_abono" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Valor ($)</label>
                        <input type="number" step="0.01" name="valor_abono" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Referencia (opcional)</label>
                        <input type="text" name="referencia" class="form-control" placeholder="# transferencia, recibo, etc.">
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">Abonar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historial de abonos -->
    <h5>Historial de Abonos (<?= count($abonos) ?>)</h5>
    <?php if (empty($abonos)): ?>
        <p class="text-muted">Sin abonos registrados.</p>
    <?php else: ?>
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th class="text-end">Valor</th>
                <th>Referencia</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($abonos as $a): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($a['fecha_abono'])) ?></td>
                <td class="text-end text-success fw-bold">$<?= number_format((float)$a['valor_abono'], 0, ',', '.') ?></td>
                <td><?= esc($a['referencia'] ?? '') ?></td>
                <td class="text-center">
                    <a href="<?= base_url("conciliaciones/deudas/abono/delete/{$deuda['id_deuda']}/{$a['id_abono']}") ?>"
                       class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este abono?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
