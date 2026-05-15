<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar oportunidad <?= esc($oportunidad['codigo']) ?> – CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        .select2-container--bootstrap-5 .select2-selection { font-size: 0.875rem; min-height: 31px; padding: 2px 6px; }
        .monto-input { text-align: right; font-family: monospace; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-pencil-square me-2"></i>Editar <?= esc($oportunidad['codigo']) ?></h1>
        <a href="<?= base_url('crm/oportunidades/ver/' . $oportunidad['id_oportunidad']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <?php foreach (session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('crm/oportunidades/editar/' . $oportunidad['id_oportunidad']) ?>" class="card shadow-sm" id="formOp">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-7">
                    <label class="form-label small">Empresa *</label>
                    <select name="id_empresa" id="selEmpresa" class="form-select form-select-sm" required>
                        <option value="<?= $empresa['id_empresa'] ?>" selected>
                            <?= esc($empresa['razon_social']) ?>
                            <?php if (!empty($empresa['nit'])): ?> — <?= esc($empresa['nit']) ?><?php endif; ?>
                        </option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Contacto principal</label>
                    <select name="id_contacto_principal" id="selContacto" class="form-select form-select-sm">
                        <option value="">— Sin contacto principal —</option>
                        <?php foreach ($contactos as $c): ?>
                            <option value="<?= $c['id_contacto'] ?>"
                                <?= $oportunidad['id_contacto_principal'] == $c['id_contacto'] ? 'selected' : '' ?>>
                                <?= esc($c['nombre']) ?><?= !empty($c['cargo']) ? ' — ' . esc($c['cargo']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small">Título *</label>
                    <input type="text" name="titulo" class="form-control form-control-sm"
                           value="<?= esc(old('titulo', $oportunidad['titulo'])) ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label small">Descripción</label>
                    <textarea name="descripcion" class="form-control form-control-sm" rows="2"><?= esc(old('descripcion', $oportunidad['descripcion'])) ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Valor (COP) *</label>
                    <input type="text" name="valor" id="valor" class="form-control form-control-sm monto-input"
                           value="<?= number_format((float) $oportunidad['valor'], 0, ',', '.') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Etapa *</label>
                    <select name="id_etapa" id="selEtapa" class="form-select form-select-sm" required>
                        <?php foreach ($etapas as $et): ?>
                            <option value="<?= $et['id_etapa'] ?>"
                                <?= $oportunidad['id_etapa'] == $et['id_etapa'] ? 'selected' : '' ?>
                                data-prob="<?= (int) $et['probabilidad_default'] ?>">
                                <?= esc($et['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Probabilidad (%)</label>
                    <input type="number" name="probabilidad" id="probabilidad" class="form-control form-control-sm"
                           min="0" max="100" value="<?= (int) $oportunidad['probabilidad'] ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label small">Fecha de cierre estimada</label>
                    <input type="date" name="fecha_cierre_estimada" class="form-control form-control-sm"
                           value="<?= esc($oportunidad['fecha_cierre_estimada']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Responsable</label>
                    <select name="id_responsable" class="form-select form-select-sm">
                        <?php foreach ($usuariosCrm as $u): ?>
                            <option value="<?= $u['id_users'] ?>"
                                <?= $oportunidad['id_responsable'] == $u['id_users'] ? 'selected' : '' ?>>
                                <?= esc($u['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small">Notas</label>
                    <textarea name="notas" class="form-control form-control-sm" rows="2"><?= esc(old('notas', $oportunidad['notas'])) ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="<?= base_url('crm/oportunidades/eliminar/' . $oportunidad['id_oportunidad']) ?>"
               class="btn btn-outline-danger btn-sm"
               onclick="return confirm('¿Eliminar esta oportunidad? Esta acción no se puede deshacer.')">
                <i class="bi bi-trash me-1"></i> Eliminar
            </a>
            <div>
                <a href="<?= base_url('crm/oportunidades/ver/' . $oportunidad['id_oportunidad']) ?>" class="btn btn-secondary btn-sm">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg me-1"></i> Guardar
                </button>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$('#selEmpresa').select2({ theme: 'bootstrap-5', minimumInputLength: 2,
    ajax: {
        url: '<?= base_url('crm/empresas/buscar') ?>',
        dataType: 'json', delay: 250,
        data: params => ({ q: params.term }),
        processResults: data => ({
            results: (data.items || []).map(e => ({
                id: e.id_empresa,
                text: e.razon_social + (e.nit ? ' — ' + e.nit : '')
            }))
        })
    }
});
$('#selContacto').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: '— Sin contacto principal —' });

$('#selEmpresa').on('change', function () {
    const idEmpresa = this.value;
    $('#selContacto').empty().append(new Option('— Sin contacto principal —', '', true, true));
    if (!idEmpresa) return;
    fetch('<?= base_url('crm/contactos/buscar') ?>?id_empresa=' + idEmpresa)
        .then(r => r.json())
        .then(d => {
            (d.items || []).forEach(c => {
                $('#selContacto').append(new Option(c.nombre + (c.cargo ? ' — ' + c.cargo : ''), c.id_contacto));
            });
        });
});

$('#selEtapa').on('change', function () {
    const prob = this.options[this.selectedIndex]?.dataset.prob;
    if (prob !== undefined && confirm('¿Actualizar la probabilidad al default de la nueva etapa (' + prob + '%)?')) {
        document.getElementById('probabilidad').value = prob;
    }
});

function parseMonto(s) {
    if (!s) return 0;
    let v = String(s).replace(/[\$\s]/g, '');
    if (v.indexOf(',') !== -1) v = v.replace(/\./g, '').replace(',', '.');
    else v = v.replace(/\./g, '');
    return parseFloat(v) || 0;
}
const $valor = document.getElementById('valor');
$valor.addEventListener('blur', () => {
    const n = parseMonto($valor.value);
    $valor.value = n > 0 ? new Intl.NumberFormat('es-CO').format(n) : '';
});
document.getElementById('formOp').addEventListener('submit', () => {
    $valor.value = String(parseMonto($valor.value));
});
</script>
</body>
</html>
