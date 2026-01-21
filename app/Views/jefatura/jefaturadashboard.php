<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Jefatura</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <?= $this->include('partials/nav') ?>
    <?php $session = session(); ?>
    <div class="container py-4">
        <h1 class="h3 mb-4">Bienvenido/a, <?= esc($session->get('nombre_completo')) ?> (Jefatura)</h1>
        <div class="row gy-4">
            <!-- MODULO DE ACTIVIDADES - PRIMERO -->
            <div class="col-12">
                <h5 class="text-muted mb-3"><i class="bi bi-kanban me-2"></i>Gestion de Actividades</h5>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-success">
                    <div class="card-body">
                        <h5 class="card-title">Nueva Actividad</h5>
                        <p class="card-text">Crea y asigna nuevas actividades a tu equipo.</p>
                        <a href="<?= base_url('actividades/nueva') ?>" class="btn btn-success">
                            <i class="bi bi-plus-lg me-1"></i> Crear Actividad
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-primary">
                    <div class="card-body">
                        <h5 class="card-title">Tablero de Actividades</h5>
                        <p class="card-text">Vista Kanban para gestionar actividades por estado.</p>
                        <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-primary">
                            <i class="bi bi-kanban me-1"></i> Ver Tablero
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Por Responsable</h5>
                        <p class="card-text">Ver actividades agrupadas por persona asignada.</p>
                        <a href="<?= base_url('actividades/responsable') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-people me-1"></i> Ver por Responsable
                        </a>
                    </div>
                </div>
            </div>

            <!-- INDICADORES -->
            <div class="col-12">
                <hr class="my-2">
                <h5 class="text-muted mb-3"><i class="bi bi-bar-chart-line me-2"></i>Indicadores</h5>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Mis Indicadores</h5>
                        <p class="card-text">Consulta tus indicadores personales para el periodo actual.</p>
                        <a href="<?= base_url('jefatura/misindicadorescomojefe') ?>" class="btn btn-primary">
                            <i class="bi bi-bar-chart-line me-1"></i> Ver Mis Indicadores
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Historial de Mis Indicadores</h5>
                        <p class="card-text">Revisa el historial de tus resultados en periodos anteriores.</p>
                        <a href="<?= base_url('jefatura/historialmisindicadoresfeje') ?>" class="btn btn-secondary">
                            <i class="bi bi-clock-history me-1"></i> Ver Mi Historial
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Modificar Indicadores del Equipo</h5>
                        <p class="card-text">Supervisa los indicadores reportados.</p>
                        <a href="<?= base_url('jefatura/losindicadoresdemiequipo') ?>" class="btn btn-info text-white">
                            <i class="bi bi-people-fill me-1"></i>Modifica Resultados
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Historial de Indicadores del Equipo</h5>
                        <p class="card-text">Consulta el historial de resultados de tu equipo.</p>
                        <a href="<?= base_url('jefatura/historiallosindicadoresdemiequipo') ?>" class="btn btn-warning">
                            <i class="bi bi-journal-text me-1"></i> Ver Historial de Equipo
                        </a>
                    </div>
                </div>
            </div>

            <!-- EQUIPO Y JERARQUIA -->
            <div class="col-12">
                <hr class="my-2">
                <h5 class="text-muted mb-3"><i class="bi bi-diagram-3 me-2"></i>Equipo y Jerarquia</h5>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Modulo de Revisión Directa</h5>
                        <p class="card-text">Módulo clásico de revisión directa.</p>
                        <a href="<?= base_url('jerarquia/historialjerarquico') ?>" class="btn btn-success">
                            <i class="bi bi-journal-text me-1"></i> Ver Historial de todas las ramas de la jerarquía
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Personal al Cargo</h5>
                        <p class="card-text">Equipo ramificado</p>
                        <a href="<?= base_url('jerarquia/equipoextendido') ?>" class="btn btn-success">
                            <i class="bi bi-diagram-3 me-1"></i> Ver Jerarquización
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>