<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Cuenta de Cobro – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .monto-input { text-align: right; font-family: monospace; }
        .dropzone-pdf {
            border: 2px dashed #ced4da; border-radius: 8px; padding: 24px;
            text-align: center; cursor: pointer; transition: background 0.15s;
        }
        .dropzone-pdf:hover, .dropzone-pdf.dragover { background: #e7f1ff; border-color: #0d6efd; }
        .resumen-card { background: #f6f8fa; border-radius: 8px; padding: 12px; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php if (session()->getFlashdata('errors')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div class="toast show align-items-center text-white bg-danger border-0">
        <div class="d-flex">
            <div class="toast-body">
                <?php foreach (session()->getFlashdata('errors') as $e): ?><?= esc($e) ?><br><?php endforeach; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Nueva Cuenta de Cobro</h1>
        <a href="<?= base_url('conciliaciones/cuentas-cobro') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <form method="post" action="<?= base_url('conciliaciones/cuentas-cobro/crear') ?>" enctype="multipart/form-data" id="formCC">
        <?= csrf_field() ?>
        <div class="row g-3">
            <!-- COLUMNA IZQ -->
            <div class="col-md-8">
                <!-- Datos del cobrador -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white"><i class="bi bi-person me-1"></i>Datos del cobrador</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small">Tipo doc.</label>
                                <select name="tipo_documento" class="form-select form-select-sm">
                                    <option value="CC">CC</option>
                                    <option value="CE">CE</option>
                                    <option value="TI">TI</option>
                                    <option value="NIT">NIT</option>
                                    <option value="PASAPORTE">Pasaporte</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Documento *</label>
                                <input type="text" name="documento" class="form-control form-control-sm" value="<?= old('documento') ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small">Nombre completo *</label>
                                <input type="text" name="nombre_cobrador" class="form-control form-control-sm" value="<?= old('nombre_cobrador') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Email</label>
                                <input type="email" name="email_cobrador" class="form-control form-control-sm" value="<?= old('email_cobrador') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Teléfono</label>
                                <input type="text" name="telefono_cobrador" class="form-control form-control-sm" value="<?= old('telefono_cobrador') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Servicio -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white"><i class="bi bi-briefcase me-1"></i>Servicio prestado</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Centro de costo *</label>
                                <select name="id_centro_costo" class="form-select form-select-sm" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($centros as $c): ?>
                                        <option value="<?= $c['id_centro_costo'] ?>" <?= old('id_centro_costo') == $c['id_centro_costo'] ? 'selected' : '' ?>><?= esc($c['centro_costo']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Clasificación de costo (opcional)</label>
                                <select name="id_clasificacion" class="form-select form-select-sm">
                                    <option value="">— Sin clasificar —</option>
                                    <?php foreach ($clasificaciones as $cl): ?>
                                        <option value="<?= $cl['id_clasificacion'] ?>"><?= esc($cl['categoria'] . ' / ' . $cl['llave_item']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Descripción del servicio *</label>
                                <textarea name="descripcion_servicio" class="form-control form-control-sm" rows="2" required><?= old('descripcion_servicio') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Fecha del gasto *</label>
                                <input type="date" name="fecha_gasto" class="form-control form-control-sm" value="<?= old('fecha_gasto') ?: date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Período desde</label>
                                <input type="date" name="periodo_desde" class="form-control form-control-sm" value="<?= old('periodo_desde') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Período hasta</label>
                                <input type="date" name="periodo_hasta" class="form-control form-control-sm" value="<?= old('periodo_hasta') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos bancarios -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white"><i class="bi bi-credit-card me-1"></i>Datos bancarios (opcional)</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label small">Banco</label>
                                <input type="text" name="banco_destino" class="form-control form-control-sm" value="<?= old('banco_destino') ?>">
                            </div>
                            <div class="col-md-2"><label class="form-label small">Tipo cta</label>
                                <select name="tipo_cuenta_destino" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    <option value="ahorros">Ahorros</option>
                                    <option value="corriente">Corriente</option>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label small">Número cuenta</label>
                                <input type="text" name="numero_cuenta_destino" class="form-control form-control-sm" value="<?= old('numero_cuenta_destino') ?>">
                            </div>
                            <div class="col-md-3"><label class="form-label small">Titular</label>
                                <input type="text" name="titular_cuenta" class="form-control form-control-sm" value="<?= old('titular_cuenta') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Notas</label>
                    <textarea name="notas" class="form-control form-control-sm" rows="2"><?= old('notas') ?></textarea>
                </div>
            </div>

            <!-- COLUMNA DER: valores + PDF + submit -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-3 border-primary">
                    <div class="card-header bg-primary text-white"><i class="bi bi-cash me-1"></i>Valores</div>
                    <div class="card-body">
                        <label class="form-label small">Valor bruto *</label>
                        <input type="text" name="valor_bruto" id="valor_bruto" class="form-control form-control-sm monto-input mb-2" value="<?= old('valor_bruto') ?>" required>

                        <label class="form-label small">Retención en la fuente</label>
                        <input type="text" name="retencion_fuente" id="ret_fuente" class="form-control form-control-sm monto-input mb-2" value="<?= old('retencion_fuente', '0') ?>">

                        <label class="form-label small">Retención IVA</label>
                        <input type="text" name="retencion_iva" id="ret_iva" class="form-control form-control-sm monto-input mb-2" value="<?= old('retencion_iva', '0') ?>">

                        <label class="form-label small">Retención ICA</label>
                        <input type="text" name="retencion_ica" id="ret_ica" class="form-control form-control-sm monto-input mb-2" value="<?= old('retencion_ica', '0') ?>">

                        <label class="form-label small">Otras deducciones</label>
                        <input type="text" name="otras_deducciones" id="otras_ded" class="form-control form-control-sm monto-input mb-2" value="<?= old('otras_deducciones', '0') ?>">

                        <hr>
                        <div class="resumen-card mb-2">
                            <div class="d-flex justify-content-between"><span class="small text-muted">Bruto:</span><strong id="r_bruto">$0</strong></div>
                            <div class="d-flex justify-content-between"><span class="small text-muted">Retenciones:</span><strong class="text-danger" id="r_ret">$0</strong></div>
                            <div class="d-flex justify-content-between"><span class="small fw-bold">Neto a pagar:</span><strong class="text-success" id="r_neto">$0</strong></div>
                        </div>

                        <label class="form-label small">Valor neto a pagar *</label>
                        <input type="text" name="valor_neto_a_pagar" id="valor_neto" class="form-control form-control-sm monto-input fw-bold" value="<?= old('valor_neto_a_pagar') ?>" required>
                        <small class="text-muted">Editable. Debe cuadrar con bruto - retenciones (tolerancia $1).</small>
                    </div>
                </div>

                <div class="card shadow-sm mb-3 border-danger">
                    <div class="card-header bg-danger text-white"><i class="bi bi-file-pdf me-1"></i>PDF de la cuenta *</div>
                    <div class="card-body">
                        <div class="dropzone-pdf" id="dropzone">
                            <i class="bi bi-cloud-arrow-up" style="font-size:2rem;"></i>
                            <p class="mb-1 small" id="dz-text">Arrastra el PDF aquí o haz clic</p>
                            <small class="text-muted">Máximo 10 MB</small>
                            <input type="file" name="archivo_pdf" id="archivo_pdf" accept="application/pdf,.pdf" required hidden>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle me-1"></i>Guardar cuenta de cobro</button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function parseMonto(str) {
    if (!str) return 0;
    let s = String(str).replace(/[\$\s]/g, '');
    if (s.indexOf(',') !== -1) {
        s = s.replace(/\./g, '').replace(',', '.');
    } else if ((s.match(/\./g) || []).length > 1) {
        s = s.replace(/\./g, '');
    }
    return parseFloat(s) || 0;
}
function fmtMonto(n) {
    return '$' + new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(n);
}

const ids = ['valor_bruto', 'ret_fuente', 'ret_iva', 'ret_ica', 'otras_ded'];
function recalcular() {
    const bruto = parseMonto(document.getElementById('valor_bruto').value);
    const retF  = parseMonto(document.getElementById('ret_fuente').value);
    const retI  = parseMonto(document.getElementById('ret_iva').value);
    const retC  = parseMonto(document.getElementById('ret_ica').value);
    const otras = parseMonto(document.getElementById('otras_ded').value);
    const ret = retF + retI + retC + otras;
    const neto = bruto - ret;
    document.getElementById('r_bruto').textContent = fmtMonto(bruto);
    document.getElementById('r_ret').textContent   = fmtMonto(ret);
    document.getElementById('r_neto').textContent  = fmtMonto(neto);
    // Auto-rellenar el neto solo si el usuario no lo ha tocado manualmente
    const inpNeto = document.getElementById('valor_neto');
    if (! inpNeto.dataset.manual) {
        inpNeto.value = neto > 0 ? new Intl.NumberFormat('es-CO').format(neto) : '';
    }
}
ids.forEach(id => {
    document.getElementById(id).addEventListener('input', recalcular);
    document.getElementById(id).addEventListener('blur', e => {
        const n = parseMonto(e.target.value);
        e.target.value = n > 0 ? new Intl.NumberFormat('es-CO').format(n) : '';
        recalcular();
    });
});
document.getElementById('valor_neto').addEventListener('input', e => {
    e.target.dataset.manual = '1';
});
document.getElementById('valor_neto').addEventListener('blur', e => {
    const n = parseMonto(e.target.value);
    e.target.value = n > 0 ? new Intl.NumberFormat('es-CO').format(n) : '';
});
recalcular();

// Dropzone PDF
const $dz = document.getElementById('dropzone');
const $file = document.getElementById('archivo_pdf');
const $text = document.getElementById('dz-text');
$dz.addEventListener('click', () => $file.click());
$dz.addEventListener('dragover', e => { e.preventDefault(); $dz.classList.add('dragover'); });
$dz.addEventListener('dragleave', () => $dz.classList.remove('dragover'));
$dz.addEventListener('drop', e => {
    e.preventDefault();
    $dz.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        $file.files = e.dataTransfer.files;
        actualizarTextoPdf();
    }
});
$file.addEventListener('change', actualizarTextoPdf);
function actualizarTextoPdf() {
    if ($file.files.length) {
        const f = $file.files[0];
        const mb = (f.size / 1024 / 1024).toFixed(2);
        $text.innerHTML = `<i class="bi bi-file-pdf-fill text-danger"></i> <strong>${f.name}</strong><br><small class="text-muted">${mb} MB</small>`;
    }
}
</script>
</body>
</html>
