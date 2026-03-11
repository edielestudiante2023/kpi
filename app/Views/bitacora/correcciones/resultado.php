<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrección <?= $tipo === 'aprobada' ? 'Aprobada' : 'Rechazada' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Arial, sans-serif; }
        .card-main { max-width: 500px; margin: 60px auto; }
    </style>
</head>
<body>
    <div class="card-main">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body text-center p-5">
                <?php if ($tipo === 'aprobada'): ?>
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="text-success mb-3">Corrección Aprobada</h4>
                    <p class="text-muted mb-2">
                        La actividad de <strong><?= esc($correccion['nombre_completo']) ?></strong> fue actualizada.
                    </p>
                    <div class="alert alert-success py-2 small">
                        <strong><?= esc($correccion['descripcion']) ?></strong><br>
                        Nueva hora fin: <?= date('h:i A', strtotime($correccion['valor_nuevo'])) ?>
                        <?php if (isset($duracion_nueva)): ?>
                            &middot; Duración: <?= floor($duracion_nueva / 60) ?>h <?= round($duracion_nueva - floor($duracion_nueva / 60) * 60) ?>min
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small">
                        Si esta actividad pertenece a un periodo ya liquidado, los totales fueron recalculados automáticamente.
                    </p>
                <?php else: ?>
                    <div class="mb-3">
                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="text-danger mb-3">Corrección Rechazada</h4>
                    <p class="text-muted">
                        La solicitud de corrección de <strong><?= esc($correccion['nombre_completo']) ?></strong> fue rechazada.
                        No se realizaron cambios.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">
            Bitácora Cycloid &mdash; Módulo de Correcciones
        </p>
    </div>
</body>
</html>
