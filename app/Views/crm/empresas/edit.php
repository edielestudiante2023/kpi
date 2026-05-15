<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar empresa – CRM – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-pencil-square me-2"></i>Editar empresa</h1>
        <a href="<?= base_url('crm/empresas/ver/' . $empresa['id_empresa']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('crm/empresas/editar/' . $empresa['id_empresa']) ?>" class="card shadow-sm">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-8">
                    <label class="form-label small">Razón social *</label>
                    <input type="text" name="razon_social" class="form-control form-control-sm"
                           value="<?= esc(old('razon_social', $empresa['razon_social'])) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">NIT</label>
                    <input type="text" name="nit" class="form-control form-control-sm"
                           value="<?= esc(old('nit', $empresa['nit'])) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Sector</label>
                    <input type="text" name="sector" class="form-control form-control-sm"
                           value="<?= esc(old('sector', $empresa['sector'])) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Tamaño</label>
                    <select name="tamano" class="form-select form-select-sm">
                        <option value="">—</option>
                        <?php foreach (['micro' => 'Micro', 'pequena' => 'Pequeña', 'mediana' => 'Mediana', 'grande' => 'Grande'] as $v => $l): ?>
                            <option value="<?= $v ?>"
                                <?= old('tamano', $empresa['tamano']) === $v ? 'selected' : '' ?>>
                                <?= $l ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Ciudad</label>
                    <input type="text" name="ciudad" class="form-control form-control-sm"
                           value="<?= esc(old('ciudad', $empresa['ciudad'])) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Teléfono</label>
                    <input type="text" name="telefono" class="form-control form-control-sm"
                           value="<?= esc(old('telefono', $empresa['telefono'])) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Email principal</label>
                    <input type="email" name="email_principal" class="form-control form-control-sm"
                           value="<?= esc(old('email_principal', $empresa['email_principal'])) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Sitio web</label>
                    <input type="url" name="sitio_web" class="form-control form-control-sm"
                           value="<?= esc(old('sitio_web', $empresa['sitio_web'])) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label small">Fuente del lead</label>
                    <select name="id_fuente" class="form-select form-select-sm">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($fuentes as $f): ?>
                            <option value="<?= $f['id_fuente'] ?>"
                                <?= old('id_fuente', $empresa['id_fuente']) == $f['id_fuente'] ? 'selected' : '' ?>>
                                <?= esc($f['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Responsable</label>
                    <select name="id_responsable" class="form-select form-select-sm">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($usuariosCrm as $u): ?>
                            <option value="<?= $u['id_users'] ?>"
                                <?= old('id_responsable', $empresa['id_responsable']) == $u['id_users'] ? 'selected' : '' ?>>
                                <?= esc($u['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small">Notas</label>
                    <textarea name="notas" class="form-control form-control-sm" rows="3"><?= esc(old('notas', $empresa['notas'])) ?></textarea>
                </div>

                <div class="col-12 form-check ms-2 mt-2">
                    <input type="checkbox" name="activo" value="1" class="form-check-input" id="activo"
                           <?= (int) old('activo', $empresa['activo']) === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="activo">Empresa activa</label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="<?= base_url('crm/empresas/eliminar/' . $empresa['id_empresa']) ?>"
               class="btn btn-outline-danger btn-sm"
               onclick="return confirm('¿Eliminar esta empresa? Solo es posible si no tiene oportunidades.')">
                <i class="bi bi-trash me-1"></i> Eliminar
            </a>
            <div>
                <a href="<?= base_url('crm/empresas/ver/' . $empresa['id_empresa']) ?>" class="btn btn-secondary btn-sm">Cancelar</a>
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
