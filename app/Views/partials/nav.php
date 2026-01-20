<?php
// app/Views/partials/nav.php
$session = session();
$rolId = $session->get('id_roles');
$currentUrl = current_url();

// Determinar dashboard según rol
$dashboardUrls = [
    1 => 'superadmin/superadmindashboard',
    2 => 'admin/admindashboard',
    3 => 'jefatura/jefaturadashboard',
    4 => 'trabajador/trabajadordashboard'
];
$dashboardUrl = $dashboardUrls[$rolId] ?? 'login';
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
    <div class="container-fluid">
        <!-- Logo principal -->
        <a class="navbar-brand d-flex align-items-center" href="<?= base_url($dashboardUrl) ?>">
            <img src="<?= base_url('img/cycloid_sqe.jpg') ?>" alt="Logo" style="max-height: 50px;">
        </a>

        <!-- Toggle para móvil -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menú principal -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentUrl, 'dashboard') !== false ? 'active fw-bold' : '' ?>"
                       href="<?= base_url($dashboardUrl) ?>">
                        <i class="bi bi-house-door me-1"></i>Dashboard
                    </a>
                </li>

                <?php if ($rolId == 4): // Trabajador ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($currentUrl, 'trabajador') !== false ? 'active fw-bold' : '' ?>"
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-bar-chart me-1"></i>Indicadores
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('trabajador/mis_indicadores') ?>">
                                <i class="bi bi-list-check me-2"></i>Mis Indicadores
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('trabajador/historial_resultados') ?>">
                                <i class="bi bi-clock-history me-2"></i>Historial
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($currentUrl, 'actividades') !== false ? 'active fw-bold' : '' ?>"
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-kanban me-1"></i>Actividades
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('actividades/mis-actividades') ?>">
                                <i class="bi bi-person-check me-2"></i>Mis Actividades
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('actividades/tablero') ?>">
                                <i class="bi bi-kanban me-2"></i>Tablero
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('actividades/nueva') ?>">
                                <i class="bi bi-plus-lg me-2"></i>Nueva Actividad
                            </a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($rolId == 3): // Jefatura ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($currentUrl, 'jefatura') !== false && strpos($currentUrl, 'dashboard') === false ? 'active fw-bold' : '' ?>"
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-bar-chart me-1"></i>Indicadores
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('jefatura/misindicadorescomojefe') ?>">
                                <i class="bi bi-person me-2"></i>Mis Indicadores
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('jefatura/losindicadoresdemiequipo') ?>">
                                <i class="bi bi-people me-2"></i>Equipo
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('jefatura/historialmisindicadoresfeje') ?>">
                                <i class="bi bi-clock-history me-2"></i>Mi Historial
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('jefatura/historiallosindicadoresdemiequipo') ?>">
                                <i class="bi bi-clock-history me-2"></i>Historial Equipo
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($currentUrl, 'actividades') !== false ? 'active fw-bold' : '' ?>"
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-kanban me-1"></i>Actividades
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('actividades/mis-actividades') ?>">
                                <i class="bi bi-person-check me-2"></i>Mis Actividades
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('actividades/tablero') ?>">
                                <i class="bi bi-kanban me-2"></i>Tablero
                            </a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($rolId == 1 || $rolId == 2): // Admin/Superadmin ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($currentUrl, 'users') !== false || strpos($currentUrl, 'perfiles') !== false ? 'active fw-bold' : '' ?>"
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-people me-1"></i>Usuarios
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('users') ?>">
                                <i class="bi bi-person-lines-fill me-2"></i>Lista de Usuarios
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('perfiles') ?>">
                                <i class="bi bi-person-badge me-2"></i>Perfiles de Cargo
                            </a></li>
                            <?php if ($rolId == 1): ?>
                            <li><a class="dropdown-item" href="<?= base_url('roles') ?>">
                                <i class="bi bi-shield-check me-2"></i>Roles
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($currentUrl, 'indicadores') !== false ? 'active fw-bold' : '' ?>"
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-graph-up me-1"></i>Indicadores
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('indicadores') ?>">
                                <i class="bi bi-list-ul me-2"></i>Lista de Indicadores
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('indicadores_perfil') ?>">
                                <i class="bi bi-link me-2"></i>Asignaciones
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('auditoria-indicadores') ?>">
                                <i class="bi bi-journal-text me-2"></i>Auditoria
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($currentUrl, 'areas') !== false || strpos($currentUrl, 'equipos') !== false ? 'active fw-bold' : '' ?>"
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-building me-1"></i>Organizacion
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('areas') ?>">
                                <i class="bi bi-diagram-3 me-2"></i>Areas
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('equipos') ?>">
                                <i class="bi bi-people-fill me-2"></i>Equipos
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($currentUrl, 'actividades') !== false || strpos($currentUrl, 'categorias-actividad') !== false ? 'active fw-bold' : '' ?>"
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-kanban me-1"></i>Actividades
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('actividades/tablero') ?>">
                                <i class="bi bi-kanban me-2"></i>Tablero
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('actividades/lista') ?>">
                                <i class="bi bi-list-ul me-2"></i>Lista
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('actividades/nueva') ?>">
                                <i class="bi bi-plus-lg me-2"></i>Nueva Actividad
                            </a></li>
                            <?php if ($rolId == 1): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('categorias-actividad') ?>">
                                <i class="bi bi-tags me-2"></i>Categorías
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php if ($rolId == 1): // Solo Superadmin ?>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentUrl, 'sesiones') !== false ? 'active fw-bold' : '' ?>"
                           href="<?= base_url('sesiones/dashboard') ?>" target="_blank">
                            <i class="bi bi-clock-history me-1"></i>Tiempo Uso
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- Usuario y logout -->
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3 d-none d-md-inline">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= esc($session->get('nombre_completo')) ?>
                </span>
                <?= $this->include('partials/logout') ?>
            </div>
        </div>
    </div>
</nav>
