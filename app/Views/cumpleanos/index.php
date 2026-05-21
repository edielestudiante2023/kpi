<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cumpleanos – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <?= view('components/back_to_dashboard') ?>
        <h1 class="h3 mb-0"><i class="bi bi-cake2 me-2"></i>Cumpleanos del equipo</h1>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="alert alert-info py-2 small">
        <i class="bi bi-info-circle me-1"></i>
        El recordatorio se envia automaticamente a todo el equipo (menos al cumpleanero) los 30 dias previos.
        Cuando ya cuadren la celebracion, usa <strong>Silenciar</strong> para detener los correos; se reactivara solo el proximo ano.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Persona</th>
                        <th>Cumpleanos</th>
                        <th class="text-center">Faltan</th>
                        <th class="text-center">Cumple</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Accion</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($cumpleanos)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Sin fechas de nacimiento registradas.</td></tr>
                <?php endif; ?>
                <?php
                    $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                ?>
                <?php foreach ($cumpleanos as $c):
                    $ts = strtotime($c['proximo']);
                    $fechaFmt = (int)date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)];
                    $resaltar = $c['en_ventana'] && !$c['silenciado'];
                ?>
                    <tr class="<?= $resaltar ? 'table-warning' : '' ?>">
                        <td>
                            <strong><?= esc($c['nombre']) ?></strong>
                            <br><small class="text-muted"><?= esc($c['correo']) ?></small>
                        </td>
                        <td><?= $fechaFmt ?></td>
                        <td class="text-center">
                            <?php if ($c['dias_faltan'] === 0): ?>
                                <span class="badge bg-danger">¡HOY! 🎉</span>
                            <?php elseif ($c['en_ventana']): ?>
                                <span class="badge bg-warning text-dark"><?= $c['dias_faltan'] ?> dias</span>
                            <?php else: ?>
                                <span class="text-muted"><?= $c['dias_faltan'] ?> dias</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $c['edad'] ?> anos</td>
                        <td class="text-center">
                            <?php if ($c['silenciado']): ?>
                                <span class="badge bg-secondary" title="Silenciado hasta <?= esc($c['silenciado_hasta']) ?>">🔕 Silenciado</span>
                            <?php else: ?>
                                <span class="badge bg-success">🔔 Activo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($c['silenciado']): ?>
                                <form method="post" action="<?= base_url('cumpleanos/reactivar/'.$c['id_users']) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-success" type="submit">
                                        <i class="bi bi-bell"></i> Reactivar
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= base_url('cumpleanos/silenciar-panel/'.$c['id_users']) ?>" class="d-inline"
                                      onsubmit="return confirm('Silenciar el recordatorio de <?= esc($c['nombre']) ?>? Se reactivara solo el proximo ano.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">
                                        <i class="bi bi-bell-slash"></i> Silenciar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
