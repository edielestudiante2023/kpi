<?php
// Card de oportunidad para el Kanban. $c es una fila de getKanban().
$valorFmt = '$' . number_format((float) $c['valor'], 0, ',', '.');
$cierreFmt = $c['etapa_tipo'] !== 'abierta' && !empty($c['fecha_cierre_real'])
    ? date('d/m/Y', strtotime($c['fecha_cierre_real']))
    : (!empty($c['fecha_cierre_estimada']) ? date('d/m/Y', strtotime($c['fecha_cierre_estimada'])) : '');
?>
<div class="kanban-card" data-id="<?= $c['id_oportunidad'] ?>" data-valor="<?= (float) $c['valor'] ?>">
    <a href="<?= base_url('crm/oportunidades/ver/' . $c['id_oportunidad']) ?>" class="text-decoration-none text-dark d-block">
        <div class="codigo"><?= esc($c['codigo']) ?></div>
        <div class="titulo"><?= esc($c['titulo']) ?></div>
        <div class="empresa"><i class="bi bi-building me-1"></i><?= esc($c['empresa_nombre'] ?? '—') ?></div>
        <div class="valor mt-1"><?= $valorFmt ?></div>
        <div class="meta-bottom">
            <span>
                <?php if ($cierreFmt): ?>
                    <i class="bi bi-calendar3 me-1"></i><?= $cierreFmt ?>
                <?php endif; ?>
            </span>
            <span title="<?= esc($c['responsable_nombre'] ?? '') ?>">
                <i class="bi bi-person-circle"></i>
                <?= esc(mb_substr((string) ($c['responsable_nombre'] ?? ''), 0, 12)) ?>
            </span>
        </div>
    </a>
</div>
