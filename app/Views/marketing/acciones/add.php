<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva acción – Marketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-plus-circle me-2"></i>Registrar acción de marketing</h1>
        <a href="<?= base_url('marketing/acciones') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('marketing/acciones/nueva') ?>" class="card shadow-sm">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small">Fecha *</label>
                    <input type="date" name="fecha" class="form-control form-control-sm" value="<?= old('fecha', date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Tipo *</label>
                    <select name="id_tipo_accion" class="form-select form-select-sm" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= $t['id_tipo_accion'] ?>" <?= old('id_tipo_accion') == $t['id_tipo_accion'] ? 'selected' : '' ?>>
                                <?= esc($t['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Responsable</label>
                    <select name="id_responsable" class="form-select form-select-sm">
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id_users'] ?>"
                                <?= old('id_responsable', session()->get('id_users')) == $u['id_users'] ? 'selected' : '' ?>>
                                <?= esc($u['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small">Descripción *</label>
                    <input type="text" name="descripcion" class="form-control form-control-sm"
                           value="<?= old('descripcion') ?>" required
                           placeholder="Ej: Publiqué post sobre nuevo servicio SST en LinkedIn">
                </div>

                <div class="col-md-6">
                    <label class="form-label small">Costo (COP, opcional)</label>
                    <input type="number" name="costo" class="form-control form-control-sm" min="0" step="1000"
                           value="<?= old('costo') ?>" placeholder="0 o vacío si no aplica">
                    <small class="text-muted">Solo si la acción tuvo costo (anuncio, evento, regalo).</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Leads generados (opcional)</label>
                    <input type="number" name="leads_generados" class="form-control form-control-sm" min="0"
                           value="<?= old('leads_generados') ?>" placeholder="Cuántos leads atribuibles">
                    <small class="text-muted">Si sabes cuántos leads vinieron de esta acción.</small>
                </div>

                <div class="col-12">
                    <label class="form-label small">Notas</label>
                    <textarea name="notas" class="form-control form-control-sm" rows="2"><?= old('notas') ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="<?= base_url('marketing/acciones') ?>" class="btn btn-secondary btn-sm">Cancelar</a>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg me-1"></i> Registrar acción
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
