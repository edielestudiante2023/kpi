<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrección de Bitácora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Arial, sans-serif; }
        .card-main { max-width: 600px; margin: 30px auto; }
        .header-bar { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 12px 12px 0 0; }
        .diff-table td, .diff-table th { padding: 10px 14px; }
        .badge-before { background: #dc3545; }
        .badge-after { background: #198754; }
    </style>
</head>
<body>
    <div class="card-main">
        <div class="header-bar">
            <h4 class="mb-1"><i class="bi bi-pencil-square me-2"></i>Solicitud de Corrección</h4>
            <small style="opacity:0.7;">Bitácora Cycloid</small>
        </div>
        <div class="card border-0 shadow-sm" style="border-radius: 0 0 12px 12px;">
            <div class="card-body p-4">
                <!-- Info del usuario y actividad -->
                <div class="mb-3">
                    <div class="text-muted small">Usuario</div>
                    <div class="fw-bold"><?= esc($correccion['nombre_completo']) ?></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Actividad</div>
                    <div class="fw-bold"><?= esc($correccion['descripcion']) ?></div>
                    <div class="text-muted small">
                        <i class="bi bi-building"></i> <?= esc($correccion['centro_costo_nombre'] ?? '-') ?>
                        &middot;
                        <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($correccion['fecha'])) ?>
                    </div>
                </div>

                <hr>

                <!-- Tabla comparativa -->
                <h6 class="text-muted mb-2"><i class="bi bi-arrow-left-right me-1"></i> Comparación</h6>
                <table class="table table-bordered diff-table text-center mb-3" style="font-size: 0.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>Hora Fin</th>
                            <th>Duración</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-before">Antes</span></td>
                            <td><?= date('h:i A', strtotime($correccion['valor_anterior'])) ?></td>
                            <td>
                                <?php
                                $h = floor(abs($duracion_anterior) / 60);
                                $m = round(abs($duracion_anterior) - ($h * 60));
                                echo "{$h}h {$m}min";
                                ?>
                            </td>
                        </tr>
                        <tr class="table-success">
                            <td><span class="badge badge-after">Después</span></td>
                            <td class="fw-bold"><?= date('h:i A', strtotime($correccion['valor_nuevo'])) ?></td>
                            <td class="fw-bold">
                                <?php
                                $h = floor(abs($duracion_nueva) / 60);
                                $m = round(abs($duracion_nueva) - ($h * 60));
                                echo "{$h}h {$m}min";
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Diferencia -->
                <div class="alert <?= $diferencia < 0 ? 'alert-warning' : 'alert-info' ?> py-2 text-center" style="font-size: 0.85rem;">
                    <i class="bi bi-clock-history me-1"></i>
                    Diferencia: <strong>
                    <?php
                    $abs = abs($diferencia);
                    $dh = floor($abs / 60);
                    $dm = round($abs - ($dh * 60));
                    echo ($diferencia < 0 ? '-' : '+') . "{$dh}h {$dm}min";
                    ?>
                    </strong>
                    <?php if ($diferencia < 0): ?>
                        (se reduce la duración)
                    <?php else: ?>
                        (se aumenta la duración)
                    <?php endif; ?>
                </div>

                <?php if (!empty($correccion['motivo'])): ?>
                <div class="alert alert-light border py-2" style="font-size: 0.85rem;">
                    <strong>Motivo:</strong> <?= esc($correccion['motivo']) ?>
                </div>
                <?php endif; ?>

                <hr>

                <!-- Botones -->
                <div class="d-flex gap-2">
                    <form action="<?= base_url("bitacora-correccion/aprobar/{$token}") ?>" method="post" class="flex-fill">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success w-100 py-2"
                                onclick="return confirm('Confirmar aprobación. Se actualizará la actividad y se reliquidará el periodo si aplica.')">
                            <i class="bi bi-check-circle me-1"></i> Aprobar Corrección
                        </button>
                    </form>
                    <form action="<?= base_url("bitacora-correccion/rechazar/{$token}") ?>" method="post" class="flex-fill">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger w-100 py-2"
                                onclick="return confirm('¿Rechazar esta corrección?')">
                            <i class="bi bi-x-circle me-1"></i> Rechazar
                        </button>
                    </form>
                </div>

                <p class="text-center text-muted small mt-3 mb-0">
                    <i class="bi bi-clock me-1"></i>
                    Expira: <?= date('d/m/Y h:i A', strtotime($correccion['token_expira'])) ?>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
