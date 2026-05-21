<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de cumpleanos – Cycloid Talent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body style="background:#f0f2f5;">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm text-center">
                <div class="card-body p-5">
                    <?php if ($ok): ?>
                        <div style="font-size:54px;">🔕</div>
                        <h3 class="mt-3 text-success">Recordatorio silenciado</h3>
                    <?php else: ?>
                        <div style="font-size:54px;">⚠️</div>
                        <h3 class="mt-3 text-danger">No se pudo procesar</h3>
                    <?php endif; ?>
                    <p class="text-muted mt-3"><?= esc($mensaje) ?></p>
                    <p class="small text-muted mt-4">Puedes cerrar esta ventana.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
