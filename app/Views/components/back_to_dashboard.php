<?php
/**
 * Componente reutilizable para boton de volver al dashboard segun el rol.
 *
 * Uso: <?= view('components/back_to_dashboard') ?>
 *
 * Determina automaticamente la URL del dashboard basandose en el rol del usuario.
 */

$session = session();
$rolId = $session->get('id_roles');

// Determinar URL del dashboard segun el rol
switch ($rolId) {
    case 1: // Superadmin
        $dashboardUrl = base_url('superadmin/superadmindashboard');
        $dashboardName = 'Dashboard Superadmin';
        break;
    case 2: // Admin
        $dashboardUrl = base_url('admin/admindashboard');
        $dashboardName = 'Dashboard Admin';
        break;
    case 3: // Jefatura
        $dashboardUrl = base_url('jefatura/jefaturadashboard');
        $dashboardName = 'Dashboard Jefatura';
        break;
    case 4: // Trabajador
    default:
        $dashboardUrl = base_url('trabajador/trabajadordashboard');
        $dashboardName = 'Dashboard';
        break;
}

// Permitir sobrescribir desde la vista
$url = $url ?? $dashboardUrl;
$text = $text ?? $dashboardName;
$class = $class ?? 'btn-outline-secondary';
$icon = $icon ?? 'bi-house-door';
?>

<a href="<?= esc($url) ?>" class="btn <?= esc($class) ?>">
    <i class="bi <?= esc($icon) ?> me-1"></i><?= esc($text) ?>
</a>
