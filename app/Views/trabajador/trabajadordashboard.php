<?php // app/Views/trabajador/trabajadordashboard.php
$session = session();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Trabajador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <h1 class="h3 mb-4">Bienvenido, <?= esc($session->get('nombre_completo')) ?></h1>
        <div class="row gy-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Mis Indicadores</h5>
                        <p class="card-text">Consulta y reporta tus indicadores del periodo actual.</p>
                        <a href="<?= base_url('trabajador/mis_indicadores') ?>" class="btn btn-primary">Ver Indicadores</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Historial de Resultados</h5>
                        <p class="card-text">Revisa los resultados que has registrado en periodos anteriores.</p>
                        <a href="<?= base_url('trabajador/historial_resultados') ?>" class="btn btn-secondary">Ver Historial</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= $this->include('partials/logout') ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>