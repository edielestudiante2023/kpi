<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de la Empresa – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .balance-title { font-weight:bold; text-align:center; font-size:1.5rem; margin-bottom:20px; }
        .balance-section h5 { font-weight:bold; text-align:center; border-bottom:2px solid #212529; padding-bottom:6px; }
        .balance-row { display:flex; justify-content:space-between; padding:4px 0; font-size:0.95rem; }
        .balance-row.total { border-top:2px solid #212529; margin-top:8px; padding-top:8px; font-weight:bold; }
        .estado-empresa {
            font-size:1.3rem; font-weight:bold; padding:14px; margin-top:24px;
            border:2px solid #212529;
        }
        .badge-modo { font-size:0.7rem; vertical-align:middle; }
    </style>
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
<?php if (session()->getFlashdata('errors')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div class="toast show align-items-center text-white bg-danger border-0">
        <div class="d-flex">
            <div class="toast-body">
                <?php foreach (session()->getFlashdata('errors') as $e): ?><?= esc($e) ?><br><?php endforeach; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">
            <i class="bi bi-clipboard-data me-2"></i>Estado de la Empresa
            <?php if ($modo === 'snapshot'): ?>
                <span class="badge bg-secondary badge-modo" title="Snapshot inmutable">
                    <i class="bi bi-lock"></i> SNAPSHOT
                </span>
            <?php else: ?>
                <span class="badge bg-warning text-dark badge-modo" title="Cálculo dinámico, no congelado">
                    <i class="bi bi-arrow-clockwise"></i> DINÁMICO
                </span>
            <?php endif; ?>
        </h1>
        <div class="d-flex gap-2 align-items-center">
            <form method="get" class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0 small text-muted">Corte:</label>
                <input type="date" name="corte" value="<?= esc($corte) ?>" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php if (!empty($snapshots)): ?>
                    <select onchange="if(this.value){window.location='?corte='+this.value}" class="form-select form-select-sm" style="width:170px;">
                        <option value="">Saltar a snapshot...</option>
                        <?php foreach ($snapshots as $s): ?>
                            <option value="<?= $s['fecha_corte'] ?>"><?= date('d/m/Y', strtotime($s['fecha_corte'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </form>
            <a href="<?= base_url('conciliaciones/balance/historico') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-clock-history"></i> Histórico
            </a>
            <a href="<?= base_url('conciliaciones/deudas') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil-square"></i> Editar Deudas
            </a>
        </div>
    </div>

    <!-- Fecha grande tipo Excel -->
    <div class="balance-title"><?= date('n/j/Y', strtotime($corte)) ?></div>

    <div class="row g-4">
        <!-- ACTIVOS -->
        <div class="col-md-5 balance-section">
            <h5>ACTIVOS</h5>
            <div class="balance-row">
                <span>CARTERA SST</span>
                <span>$<?= number_format($cartera_sst, 0, ',', '.') ?></span>
            </div>
            <div class="balance-row">
                <span>CARTERA RPS</span>
                <span>$<?= number_format($cartera_rps, 0, ',', '.') ?></span>
            </div>
            <div class="balance-row">
                <span>SALDO EN BANCOS SST</span>
                <span>$<?= number_format($saldo_banco_sst, 0, ',', '.') ?></span>
            </div>
            <div class="balance-row">
                <span>SALDO EN BANCOS RPS</span>
                <span>$<?= number_format($saldo_banco_rps, 0, ',', '.') ?></span>
            </div>
            <div class="balance-row total">
                <span>SUMA ACTIVOS</span>
                <span>$<?= number_format($total_activos, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- PASIVOS -->
        <div class="col-md-7 balance-section">
            <h5>PASIVOS CORRIENTES</h5>
            <table class="table table-sm mb-0" style="font-size:0.9rem;">
                <thead>
                    <tr class="text-muted">
                        <th>Concepto</th>
                        <th class="text-end">Monto Original</th>
                        <th class="text-end">Abonado</th>
                        <th class="text-end">Saldo al corte</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pasivos)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sin deudas activas al corte. <a href="<?= base_url('conciliaciones/deudas/add') ?>">Crear deuda</a></td></tr>
                    <?php endif; ?>
                    <?php foreach ($pasivos as $p): ?>
                    <tr>
                        <td>
                            <strong><?= esc($p['concepto']) ?></strong>
                            <small class="text-muted d-block"><?= esc($p['acreedor']) ?></small>
                        </td>
                        <td class="text-end">$<?= number_format($p['monto_original'], 0, ',', '.') ?></td>
                        <td class="text-end text-success">$<?= number_format($p['abonado'], 0, ',', '.') ?></td>
                        <td class="text-end fw-bold">$<?= number_format($p['saldo_al_corte'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="balance-row total mt-2">
                <span>SUMA PASIVOS</span>
                <span>$<?= number_format($total_pasivos, 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <!-- ESTADO DE LA EMPRESA -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="estado-empresa d-flex justify-content-between <?= $estado_empresa >= 0 ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' ?>">
                <span>ESTADO DE LA EMPRESA</span>
                <span class="<?= $estado_empresa >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $estado_empresa < 0 ? '-' : '' ?>$<?= number_format(abs($estado_empresa), 0, ',', '.') ?>
                </span>
            </div>
        </div>
        <div class="col-md-6 d-flex align-items-center">
            <?php if ($modo === 'dinamico'): ?>
                <form method="post" action="<?= base_url('conciliaciones/balance/cerrar') ?>" onsubmit="return confirm('Se guardará un snapshot inmutable del balance al <?= date('d/m/Y', strtotime($corte)) ?>. ¿Continuar?');" class="w-100">
                    <?= csrf_field() ?>
                    <input type="hidden" name="corte" value="<?= esc($corte) ?>">
                    <div class="input-group">
                        <input type="text" name="notas" class="form-control" placeholder="Notas del cierre (opcional)">
                        <button class="btn btn-success">
                            <i class="bi bi-lock-fill me-1"></i> Cerrar mes (guardar snapshot)
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-secondary mb-0 w-100" style="font-size:0.85rem;">
                    <i class="bi bi-lock-fill me-1"></i>
                    Snapshot inmutable creado por
                    <strong><?= esc($snapshot_meta['creado_por'] ?? 'sistema') ?></strong>
                    el <?= date('d/m/Y H:i', strtotime($snapshot_meta['created_at'])) ?>.
                    <?php if (!empty($snapshot_meta['notas'])): ?>
                        <br><small><strong>Notas:</strong> <?= esc($snapshot_meta['notas']) ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
