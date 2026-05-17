<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar lead – Marketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-pencil-square me-2"></i>Editar lead</h1>
        <a href="<?= base_url('marketing/leads/ver/' . $lead['id_lead']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('marketing/leads/editar/' . $lead['id_lead']) ?>" class="card shadow-sm">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small">Nombre completo *</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" value="<?= esc(old('nombre', $lead['nombre'])) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Cargo / posición</label>
                    <input type="text" name="cargo" class="form-control form-control-sm" value="<?= esc(old('cargo', $lead['cargo'])) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Empresa (texto libre)</label>
                    <input type="text" name="empresa_text" class="form-control form-control-sm" value="<?= esc(old('empresa_text', $lead['empresa_text'])) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <?php foreach (['nuevo','contactado','calificado','descartado'] as $e): ?>
                            <option value="<?= $e ?>" <?= old('estado', $lead['estado']) === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Fuente</label>
                    <select name="id_fuente" class="form-select form-select-sm">
                        <option value="">—</option>
                        <?php foreach ($fuentes as $f): ?>
                            <option value="<?= $f['id_fuente'] ?>"
                                <?= old('id_fuente', $lead['id_fuente']) == $f['id_fuente'] ? 'selected' : '' ?>>
                                <?= esc($f['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm" value="<?= esc(old('email', $lead['email'])) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Teléfono</label>
                    <input type="text" name="telefono" class="form-control form-control-sm" value="<?= esc(old('telefono', $lead['telefono'])) ?>">
                </div>

                <div class="col-md-12">
                    <label class="form-label small">Responsable</label>
                    <select name="id_responsable" class="form-select form-select-sm">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id_users'] ?>"
                                <?= old('id_responsable', $lead['id_responsable']) == $u['id_users'] ? 'selected' : '' ?>>
                                <?= esc($u['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small">Notas</label>
                    <textarea name="notas" class="form-control form-control-sm" rows="3"><?= esc(old('notas', $lead['notas'])) ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="<?= base_url('marketing/leads/eliminar/' . $lead['id_lead']) ?>"
               class="btn btn-outline-danger btn-sm"
               onclick="return confirm('¿Eliminar este lead? No se puede deshacer.')">
                <i class="bi bi-trash me-1"></i> Eliminar
            </a>
            <div>
                <a href="<?= base_url('marketing/leads/ver/' . $lead['id_lead']) ?>" class="btn btn-secondary btn-sm">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg me-1"></i> Guardar
                </button>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
