<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTTO Coach de Marketing – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .preset-card {
            cursor: pointer; transition: transform 0.15s, box-shadow 0.15s;
            border: 2px solid #dee2e6;
        }
        .preset-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.10); border-color: #0d6efd; }
        .preset-card.estrella { background: linear-gradient(135deg, #fff 0%, #fffaeb 100%); border-color: #e7c100; }
        .preset-icon { font-size: 2.2rem; }
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
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-0 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center"
                      style="background:#1d2638; width:40px; height:40px; border-radius:50%; flex-shrink:0;">
                    <img src="<?= base_url('img/otto-avatar.png') ?>" alt="OTTO" style="width:28px; height:28px;">
                </span>
                OTTO Coach de Marketing
                <i class="bi bi-megaphone ms-1"></i>
            </h1>
            <small class="text-muted">
                Asistente IA para Solangel · responde "<strong>¿avanzamos?</strong>" y "<strong>¿qué hacer para crecer?</strong>" con los datos reales del embudo.
            </small>
        </div>
        <?php if ((int) session()->get('id_roles') !== 5): ?>
        <div class="text-end" style="min-width:230px;">
            <small class="text-muted">Consumo del mes (compartido con OTTO financiero/comercial)</small>
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
            $esEstrella = $key === 'plan_semana';
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card preset-card h-100 <?= $esEstrella ? 'estrella' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="preset-icon"><?= explode(' ', $p['titulo'])[0] ?></div>
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1 fw-bold">
                                <?= esc(implode(' ', array_slice(explode(' ', $p['titulo']), 1))) ?>
                                <?php if ($esEstrella): ?>
                                    <span class="badge bg-warning text-dark" style="font-size:0.6rem;">PLAN</span>
                                <?php endif; ?>
                            </h6>
                            <p class="text-muted small mb-2"><?= esc($p['descripcion']) ?></p>
                        </div>
                    </div>

                    <form method="post" action="<?= base_url('marketing/asesor-ia/analizar') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="preset" value="<?= esc($key) ?>">
                        <button type="submit" class="btn btn-sm <?= $esEstrella ? 'btn-warning' : 'btn-primary' ?> w-100">
                            <i class="bi bi-magic me-1"></i>Generar análisis
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info py-2 small">
        <i class="bi bi-info-circle me-1"></i>
        OTTO necesita <strong>datos para analizar</strong>: captura leads en
        <a href="<?= base_url('marketing/leads') ?>">Leads</a> y registra acciones en
        <a href="<?= base_url('marketing/acciones') ?>">Diario</a> regularmente.
        Sin datos, OTTO no puede responder con honestidad si avanzamos.
    </div>

    <!-- Historial -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clock-history me-1"></i>Conversaciones recientes</span>
            <small class="text-muted"><?= count($historial) ?> últimas</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($historial)): ?>
                <div class="text-center text-muted py-4 small">
                    Aún no hay análisis. Lanza uno desde las tarjetas de arriba o usa el widget flotante de la derecha.
                </div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Análisis</th>
                            <th>Creado por</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $h): ?>
                        <tr>
                            <td><small><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></small></td>
                            <td>
                                <a href="<?= base_url('marketing/asesor-ia/ver/' . $h['id_conversacion']) ?>" class="text-decoration-none">
                                    <?= esc($h['titulo']) ?>
                                </a>
                            </td>
                            <td><small><?= esc($h['creado_por'] ?? '—') ?></small></td>
                            <td class="text-center text-nowrap">
                                <a href="<?= base_url('marketing/asesor-ia/ver/' . $h['id_conversacion']) ?>"
                                   class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= base_url('marketing/asesor-ia/eliminar/' . $h['id_conversacion']) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Eliminar esta conversación?')" title="Eliminar">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
