<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error – Rutina Diaria</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5;
            display: flex; justify-content: center; align-items: center; min-height: 100vh;
        }
        .box {
            background: #fff; border-radius: 12px; padding: 40px; text-align: center;
            max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .box h1 { color: #dc3545; font-size: 24px; margin-bottom: 10px; }
        .box p { color: #6c757d; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Enlace no valido</h1>
        <p><?= esc($mensaje ?? 'Este enlace no es valido o ha expirado.') ?></p>
    </div>
</body>
</html>
