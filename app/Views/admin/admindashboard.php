<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">

<?= $this->include('partials/nav') ?>

    <h1 class="mb-4">Dashboard Admin</h1>

    <div class="card">
        <div class="card-header">
            Accesos habilitados para Admin
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Detalle</th>
                        <th>Enlace</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($accesos as $a): ?>
                    <tr>
                        <td><?= esc($a['detalle']) ?></td>
                        <td>
                            <a href="<?= base_url($a['enlace']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                Ir
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-<?= $a['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($a['estado']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



