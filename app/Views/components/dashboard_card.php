<?php
/**
 * Componente reutilizable para cards de dashboard.
 *
 * Uso:
 * <?= view('components/dashboard_card', [
 *     'title' => 'Título de la card',
 *     'description' => 'Descripción breve',
 *     'url' => '/ruta/destino',
 *     'icon' => 'bi-graph-up',        // Clase de Bootstrap Icons
 *     'btnText' => 'Ir',              // Texto del botón
 *     'btnClass' => 'btn-primary',    // Clase del botón (opcional, default: btn-primary)
 *     'cardClass' => '',              // Clase adicional para la card (opcional)
 * ]) ?>
 */

$btnClass = $btnClass ?? 'btn-primary';
$cardClass = $cardClass ?? '';
$icon = $icon ?? 'bi-arrow-right';
?>
<div class="card shadow-sm h-100 <?= esc($cardClass) ?>">
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-3">
            <i class="bi <?= esc($icon) ?> fs-3 text-primary me-2"></i>
            <h5 class="card-title mb-0"><?= esc($title) ?></h5>
        </div>
        <p class="card-text flex-grow-1"><?= esc($description) ?></p>
        <a href="<?= esc($url) ?>" class="btn <?= esc($btnClass) ?> mt-auto">
            <i class="bi <?= esc($icon) ?> me-1"></i> <?= esc($btnText) ?>
        </a>
    </div>
</div>
