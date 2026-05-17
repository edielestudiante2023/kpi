<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo lead – Marketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-person-plus me-2"></i>Nuevo lead</h1>
        <a href="<?= base_url('marketing/leads') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('marketing/leads/nuevo') ?>" class="card shadow-sm">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small">Nombre completo *</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" value="<?= old('nombre') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Cargo / posición</label>
                    <input type="text" name="cargo" class="form-control form-control-sm" value="<?= old('cargo') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Empresa (texto libre)</label>
                    <input type="text" name="empresa_text" class="form-control form-control-sm" value="<?= old('empresa_text') ?>"
                           placeholder="Aún no es una empresa formal en el CRM">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <?php foreach (['nuevo','contactado','calificado','descartado'] as $e): ?>
                            <option value="<?= $e ?>" <?= old('estado') === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Fuente del lead</label>
                    <select name="id_fuente" class="form-select form-select-sm">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($fuentes as $f): ?>
                            <option value="<?= $f['id_fuente'] ?>" <?= old('id_fuente') == $f['id_fuente'] ? 'selected' : '' ?>>
                                <?= esc($f['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm" value="<?= old('email') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Teléfono</label>
                    <input type="text" name="telefono" class="form-control form-control-sm" value="<?= old('telefono') ?>">
                </div>

                <div class="col-md-12">
                    <label class="form-label small">Responsable de seguimiento</label>
                    <select name="id_responsable" class="form-select form-select-sm">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id_users'] ?>"
                                <?= old('id_responsable', session()->get('id_users')) == $u['id_users'] ? 'selected' : '' ?>>
                                <?= esc($u['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small">Notas (de dónde salió, qué busca, contexto inicial)</label>
                    <textarea name="notas" class="form-control form-control-sm" rows="3"><?= old('notas') ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="<?= base_url('marketing/leads') ?>" class="btn btn-secondary btn-sm">Cancelar</a>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i> Crear lead</button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
