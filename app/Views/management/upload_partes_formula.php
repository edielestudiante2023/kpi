<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar CSV de Fórmulas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h3>📤 Cargar CSV de Partes de Fórmula</h3>

    <form action="<?= site_url('partesformula/upload') ?>" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="csv_file" class="form-label">Archivo CSV</label>
            <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
        </div>
        <button type="submit" class="btn btn-success">Subir CSV</button>
        <a href="<?= site_url('partesformula/list') ?>" class="btn btn-secondary">Volver</a>
    </form>
</div>
</body>
</html>
