<?php // app/Views/trabajador/trabajadordashboard.php
$session = session();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Trabajador | KPI Cycloid</title>
    <?= $this->include('partials/pwa_head') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <h1 class="h3 mb-4">Bienvenido, <?= esc($session->get('nombre_completo')) ?></h1>
        <div class="row gy-4">
            <!-- MODULO DE ACTIVIDADES - PRIMERO -->
            <div class="col-12">
                <h5 class="text-muted mb-3"><i class="bi bi-kanban me-2"></i>Gestion de Actividades</h5>
            </div>
            <div class="col-md-4">
                <?= view('components/dashboard_card', [
                    'title' => 'Nueva Actividad',
                    'description' => 'Crea una nueva actividad o solicitud.',
                    'url' => base_url('actividades/nueva'),
                    'icon' => 'bi-plus-lg',
                    'btnText' => 'Crear Actividad',
                    'btnClass' => 'btn-success',
                    'cardClass' => 'border-success',
                ]) ?>
            </div>
            <div class="col-md-4">
                <?= view('components/dashboard_card', [
                    'title' => 'Mis Actividades',
                    'description' => 'Gestiona las actividades y tareas asignadas a ti.',
                    'url' => base_url('actividades/mis-actividades'),
                    'icon' => 'bi-person-check',
                    'btnText' => 'Ver Mis Actividades',
                    'btnClass' => 'btn-primary',
                    'cardClass' => 'border-primary',
                ]) ?>
            </div>
            <div class="col-md-4">
                <?= view('components/dashboard_card', [
                    'title' => 'Tablero de Actividades',
                    'description' => 'Vista Kanban de todas las actividades por estado.',
                    'url' => base_url('actividades/tablero'),
                    'icon' => 'bi-kanban',
                    'btnText' => 'Ver Tablero',
                    'btnClass' => 'btn-outline-primary',
                ]) ?>
            </div>

            <!-- INDICADORES -->
            <div class="col-12">
                <hr class="my-2">
                <h5 class="text-muted mb-3"><i class="bi bi-bar-chart-line me-2"></i>Indicadores</h5>
            </div>
            <div class="col-md-6">
                <?= view('components/dashboard_card', [
                    'title' => 'Mis Indicadores',
                    'description' => 'Consulta y reporta tus indicadores del periodo actual.',
                    'url' => base_url('trabajador/mis_indicadores'),
                    'icon' => 'bi-bar-chart-line',
                    'btnText' => 'Ver Indicadores',
                    'btnClass' => 'btn-primary',
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= view('components/dashboard_card', [
                    'title' => 'Historial de Resultados',
                    'description' => 'Revisa los resultados que has registrado en periodos anteriores.',
                    'url' => base_url('trabajador/historial_resultados'),
                    'icon' => 'bi-clock-history',
                    'btnText' => 'Ver Historial',
                    'btnClass' => 'btn-secondary',
                ]) ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->include('partials/pwa_scripts') ?>
</body>

</html>
