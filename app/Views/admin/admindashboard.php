<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | KPI Cycloid</title>
    <?= $this->include('partials/pwa_head') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<div class="container py-4">

<?= $this->include('partials/nav') ?>

    <h1 class="mb-4">Dashboard Admin</h1>

    <!-- MODULO DE ACTIVIDADES - PRIMERO -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-kanban me-2"></i>Gestion de Actividades
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-primary w-100">
                        <i class="bi bi-kanban me-1"></i> Tablero Kanban
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= base_url('actividades/responsable') ?>" class="btn btn-outline-primary w-100">
                        <i class="bi bi-people me-1"></i> Por Responsable
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= base_url('actividades/dashboard') ?>" class="btn btn-outline-info w-100">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

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
<?= $this->include('partials/pwa_scripts') ?>
</body>
</html>



