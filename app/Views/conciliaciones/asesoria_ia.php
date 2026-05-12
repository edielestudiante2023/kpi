<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asesoría Financiera IA – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .preset-card {
            cursor:pointer; transition: transform 0.15s, box-shadow 0.15s;
            border:2px solid #dee2e6;
        }
        .preset-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.10); border-color:#0d6efd; }
        .preset-icon { font-size: 2.2rem; }
        .preset-card.premium { background: linear-gradient(135deg, #fff 0%, #fffaeb 100%); border-color:#e7c100; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

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
<?php if (session()->getFlashdata('success')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div class="toast show align-items-center text-white bg-success border-0">
        <div class="d-flex">
            <div class="toast-body"><?= session()->getFlashdata('success') ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0"><i class="bi bi-robot me-2"></i>Asesoría Financiera IA</h1>
            <small class="text-muted">Análisis ejecutivo del estado de Cycloid Talent con Claude Sonnet</small>
        </div>
        <?php if ((int) session()->get('id_roles') !== 5): ?>
        <div class="text-end" style="min-width:230px;">
            <small class="text-muted">Consumo del mes</small>
            <div class="progress" style="height:18px;">
                <div class="progress-bar <?= $porcentaje > 80 ? 'bg-danger' : ($porcentaje > 50 ? 'bg-warning' : 'bg-success') ?>"
                     style="width: <?= $porcentaje ?>%;">
                    $<?= number_format($costoMes, 3) ?> / $<?= number_format($budgetMes, 2) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cards presets -->
    <div class="row g-3 mb-4">
        <?php foreach ($presets as $key => $p):
            $esPremium = $key === 'estrategia';
            $needsParams = in_array($key, ['analisis_cierre', 'comparativo']);
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card preset-card h-100 <?= $esPremium ? 'premium' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="preset-icon"><?= explode(' ', $p['titulo'])[0] ?></div>
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1 fw-bold">
                                <?= esc(implode(' ', array_slice(explode(' ', $p['titulo']), 1))) ?>
                                <?php if ($esPremium): ?>
                                    <span class="badge bg-warning text-dark" style="font-size:0.6rem;">PREMIUM</span>
                                <?php endif; ?>
                            </h6>
                            <p class="text-muted small mb-2"><?= esc($p['descripcion']) ?></p>
                        </div>
                    </div>

                    <form method="post" action="<?= base_url('conciliaciones/asesoria-ia/analizar') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="preset" value="<?= esc($key) ?>">

                        <?php if ($key === 'analisis_cierre'): ?>
                            <select name="id_snapshot" class="form-select form-select-sm mb-2" required>
                                <option value="">Snapshot a analizar...</option>
                                <?php foreach ($snapshots as $s): ?>
                                    <option value="<?= $s['id_snapshot'] ?>">
                                        <?= date('d/m/Y', strtotime($s['fecha_corte'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($snapshots)): ?>
                                <small class="text-danger d-block mb-2">Sin snapshots. <a href="<?= base_url('conciliaciones/balance') ?>">Crear primero</a></small>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($key === 'comparativo'): ?>
                            <div class="row g-1 mb-2">
                                <div class="col-6"><input type="date" name="fecha_a" class="form-control form-control-sm" required title="Fecha A"></div>
                                <div class="col-6"><input type="date" name="fecha_b" class="form-control form-control-sm" required title="Fecha B"></div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-sm <?= $esPremium ? 'btn-warning' : 'btn-primary' ?> w-100"
                                <?= $key === 'analisis_cierre' && empty($snapshots) ? 'disabled' : '' ?>>
                            <i class="bi bi-magic me-1"></i>Generar análisis
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Historial -->
    <div class="card shadow-sm">
        <div class="card-header bg-white"><i class="bi bi-clock-history me-2"></i>Análisis recientes</div>
        <div class="card-body p-0">
            <?php if (empty($historial)): ?>
                <p class="text-muted text-center py-4 mb-0">Aún no hay análisis generados.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Título</th>
                            <th>Creado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historial as $h): ?>
                        <tr>
                            <td><small><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></small></td>
                            <td><span class="badge bg-secondary"><?= esc($h['tipo']) ?></span></td>
                            <td><?= esc($h['titulo']) ?></td>
                            <td><small class="text-muted"><?= esc($h['creado_por']) ?></small></td>
                            <td>
                                <a href="<?= base_url('conciliaciones/asesoria-ia/ver/' . $h['id_conversacion']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a href="<?= base_url('conciliaciones/asesoria-ia/eliminar/' . $h['id_conversacion']) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Eliminar este análisis?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
