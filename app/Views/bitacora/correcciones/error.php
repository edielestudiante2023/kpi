<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlace no válido</title>
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
                <div class="mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem;"></i>
                </div>
                <h4 class="mb-3">Enlace no válido</h4>
                <p class="text-muted"><?= esc($mensaje) ?></p>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">
            Bitácora Cycloid &mdash; Módulo de Correcciones
        </p>
    </div>
</body>
</html>
