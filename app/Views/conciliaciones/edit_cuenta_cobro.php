<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cuenta de Cobro #<?= $cc['id_cuenta_cobro'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        .monto-input { text-align: right; font-family: monospace; }
        .dropzone-pdf { border:2px dashed #ced4da; border-radius:8px; padding:18px; text-align:center; cursor:pointer; }
        .dropzone-pdf:hover, .dropzone-pdf.dragover { background:#e7f1ff; border-color:#0d6efd; }
        .resumen-card { background:#f6f8fa; border-radius:8px; padding:12px; }
        .select2-container--bootstrap-5 .select2-selection { font-size: 0.875rem; min-height: 31px; padding: 2px 6px; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding: 0 4px; }
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
        <h1 class="h5 mb-0"><i class="bi bi-pencil-square me-2"></i>Editar Cuenta de Cobro #<?= $cc['id_cuenta_cobro'] ?></h1>
        <a href="<?= base_url('conciliaciones/cuentas-cobro/ver/' . $cc['id_cuenta_cobro']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <form method="post" action="<?= base_url('conciliaciones/cuentas-cobro/editar/' . $cc['id_cuenta_cobro']) ?>" enctype="multipart/form-data" id="formCCedit">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <!-- Tercero -->
                <div class="card shadow-sm mb-3 border-info">
                    <div class="card-header bg-info text-white"><i class="bi bi-person-vcard me-1"></i>Tercero</div>
                    <div class="card-body">
                        <label class="form-label small">Tercero asociado (opcional)</label>
                        <select name="id_tercero" id="sel_tercero" class="form-select form-select-sm select2-cc">
                            <option value="">— Capturar manualmente —</option>
                            <?php foreach ($terceros as $t): ?>
                                <option value="<?= $t['id_tercero'] ?>"
                                        <?= ((int) ($cc['id_tercero'] ?? 0) === (int) $t['id_tercero']) ? 'selected' : '' ?>
                                        data-tipo_documento="<?= esc($t['tipo_documento'], 'attr') ?>"
                                        data-documento="<?= esc($t['documento'], 'attr') ?>"
                                        data-nombre="<?= esc($t['nombre'], 'attr') ?>"
                                        data-email="<?= esc($t['email'] ?? '', 'attr') ?>"
                                        data-telefono="<?= esc($t['telefono'] ?? '', 'attr') ?>"
                                        data-banco="<?= esc($t['banco'] ?? '', 'attr') ?>"
                                        data-tipo_cuenta="<?= esc($t['tipo_cuenta'] ?? '', 'attr') ?>"
                                        data-numero_cuenta="<?= esc($t['numero_cuenta'] ?? '', 'attr') ?>"
                                        data-titular_cuenta="<?= esc($t['titular_cuenta'] ?? '', 'attr') ?>">
                                    <?= esc($t['nombre']) ?> — <?= esc($t['documento']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="text-muted small mt-1">
                            Si el tercero no existe, créalo primero en
                            <a href="<?= base_url('conciliaciones/terceros') ?>" target="_blank">Terceros</a>.
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white"><i class="bi bi-person me-1"></i>Datos del cobrador</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3"><label class="form-label small">Tipo doc.</label>
                                <select name="tipo_documento" class="form-select form-select-sm">
                                    <?php foreach (['CC','CE','TI','NIT','PASAPORTE'] as $td): ?>
                                        <option value="<?= $td ?>" <?= $cc['tipo_documento'] === $td ? 'selected' : '' ?>><?= $td ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label small">Documento *</label>
                                <input type="text" name="documento" class="form-control form-control-sm" value="<?= esc($cc['documento']) ?>" required>
                            </div>
                            <div class="col-md-5"><label class="form-label small">Nombre *</label>
                                <input type="text" name="nombre_cobrador" class="form-control form-control-sm" value="<?= esc($cc['nombre_cobrador']) ?>" required>
                            </div>
                            <div class="col-md-6"><label class="form-label small">Email</label>
                                <input type="email" name="email_cobrador" class="form-control form-control-sm" value="<?= esc($cc['email_cobrador']) ?>">
                            </div>
                            <div class="col-md-6"><label class="form-label small">Teléfono</label>
                                <input type="text" name="telefono_cobrador" class="form-control form-control-sm" value="<?= esc($cc['telefono_cobrador']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white"><i class="bi bi-briefcase me-1"></i>Servicio</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label small">Centro de costo *</label>
                                <select name="id_centro_costo" class="form-select form-select-sm select2-cc" required>
                                    <?php foreach ($centros as $c): ?>
                                        <option value="<?= $c['id_centro_costo'] ?>" <?= $cc['id_centro_costo'] == $c['id_centro_costo'] ? 'selected' : '' ?>><?= esc($c['centro_costo']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label small">Clasificación</label>
                                <select name="id_clasificacion" class="form-select form-select-sm select2-cc">
                                    <option value="">— Sin clasificar —</option>
                                    <?php foreach ($clasificaciones as $cl): ?>
                                        <option value="<?= $cl['id_clasificacion'] ?>" <?= $cc['id_clasificacion'] == $cl['id_clasificacion'] ? 'selected' : '' ?>><?= esc($cl['categoria'] . ' / ' . $cl['llave_item']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label small">Descripción *</label>
                                <textarea name="descripcion_servicio" class="form-control form-control-sm" rows="2" required><?= esc($cc['descripcion_servicio']) ?></textarea>
                            </div>
                            <div class="col-md-4"><label class="form-label small">Fecha del gasto *</label>
                                <input type="date" name="fecha_gasto" class="form-control form-control-sm" value="<?= esc($cc['fecha_gasto']) ?>" required>
                            </div>
                            <div class="col-md-4"><label class="form-label small">Período desde</label>
                                <input type="date" name="periodo_desde" class="form-control form-control-sm" value="<?= esc($cc['periodo_desde']) ?>">
                            </div>
                            <div class="col-md-4"><label class="form-label small">Período hasta</label>
                                <input type="date" name="periodo_hasta" class="form-control form-control-sm" value="<?= esc($cc['periodo_hasta']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white"><i class="bi bi-credit-card me-1"></i>Datos bancarios</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label small">Banco</label>
                                <input type="text" name="banco_destino" class="form-control form-control-sm" value="<?= esc($cc['banco_destino']) ?>">
                            </div>
                            <div class="col-md-2"><label class="form-label small">Tipo</label>
                                <select name="tipo_cuenta_destino" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    <option value="ahorros"   <?= $cc['tipo_cuenta_destino'] === 'ahorros'   ? 'selected' : '' ?>>Ahorros</option>
                                    <option value="corriente" <?= $cc['tipo_cuenta_destino'] === 'corriente' ? 'selected' : '' ?>>Corriente</option>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label small">N° cuenta</label>
                                <input type="text" name="numero_cuenta_destino" class="form-control form-control-sm" value="<?= esc($cc['numero_cuenta_destino']) ?>">
                            </div>
                            <div class="col-md-3"><label class="form-label small">Titular</label>
                                <input type="text" name="titular_cuenta" class="form-control form-control-sm" value="<?= esc($cc['titular_cuenta']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Notas</label>
                    <textarea name="notas" class="form-control form-control-sm" rows="2"><?= esc($cc['notas']) ?></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-3 border-primary">
                    <div class="card-header bg-primary text-white"><i class="bi bi-cash me-1"></i>Valores</div>
                    <div class="card-body">
                        <label class="form-label small">Valor bruto *</label>
                        <input type="text" name="valor_bruto" id="valor_bruto" class="form-control form-control-sm monto-input mb-2" value="<?= number_format((float)$cc['valor_bruto'], 0, ',', '.') ?>" required>
                        <label class="form-label small">Ret. fuente</label>
                        <input type="text" name="retencion_fuente" id="ret_fuente" class="form-control form-control-sm monto-input mb-2" value="<?= number_format((float)$cc['retencion_fuente'], 0, ',', '.') ?>">
                        <label class="form-label small">Ret. IVA</label>
                        <input type="text" name="retencion_iva" id="ret_iva" class="form-control form-control-sm monto-input mb-2" value="<?= number_format((float)$cc['retencion_iva'], 0, ',', '.') ?>">
                        <label class="form-label small">Ret. ICA</label>
                        <input type="text" name="retencion_ica" id="ret_ica" class="form-control form-control-sm monto-input mb-2" value="<?= number_format((float)$cc['retencion_ica'], 0, ',', '.') ?>">
                        <label class="form-label small">Otras deducciones</label>
                        <input type="text" name="otras_deducciones" id="otras_ded" class="form-control form-control-sm monto-input mb-2" value="<?= number_format((float)$cc['otras_deducciones'], 0, ',', '.') ?>">
                        <hr>
                        <div class="resumen-card mb-2">
                            <div class="d-flex justify-content-between"><span class="small text-muted">Bruto:</span><strong id="r_bruto">$0</strong></div>
                            <div class="d-flex justify-content-between"><span class="small text-muted">Retenciones:</span><strong class="text-danger" id="r_ret">$0</strong></div>
                            <div class="d-flex justify-content-between"><span class="small fw-bold">Neto:</span><strong class="text-success" id="r_neto">$0</strong></div>
                        </div>
                        <label class="form-label small">Valor neto a pagar *</label>
                        <input type="text" name="valor_neto_a_pagar" id="valor_neto" class="form-control form-control-sm monto-input fw-bold" value="<?= number_format((float)$cc['valor_neto_a_pagar'], 0, ',', '.') ?>" required data-manual="1">
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white"><i class="bi bi-file-pdf me-1"></i>PDF actual</div>
                    <div class="card-body">
                        <p class="small mb-2">
                            <i class="bi bi-file-pdf-fill text-danger"></i>
                            <strong><?= esc($cc['nombre_pdf_original']) ?></strong>
                            <br><small class="text-muted"><?= number_format($cc['tamano_pdf']/1024, 0) ?> KB</small>
                        </p>
                        <a href="<?= base_url('conciliaciones/cuentas-cobro/pdf/' . $cc['id_cuenta_cobro']) ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100 mb-2">
                            <i class="bi bi-eye"></i> Ver PDF actual
                        </a>
                        <hr>
                        <label class="form-label small">Reemplazar PDF (opcional)</label>
                        <div class="dropzone-pdf" id="dropzone">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <p class="mb-0 small" id="dz-text">Arrastra un nuevo PDF aquí</p>
                            <input type="file" name="archivo_pdf" id="archivo_pdf" accept="application/pdf,.pdf" hidden>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100"><i class="bi bi-save me-1"></i>Guardar cambios</button>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
<script>
$(function () {
    $('.select2-cc').select2({
        theme: 'bootstrap-5',
        language: 'es',
        placeholder: 'Buscar...',
        allowClear: true,
        width: '100%',
    });

    // Autocompletar al cambiar de tercero
    $('#sel_tercero').on('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;
        const f = document.getElementById('formCCedit');
        function setIfEmpty(name, val) {
            const el = f.querySelector('[name=' + name + ']');
            if (el) el.value = val || '';
        }
        setIfEmpty('tipo_documento',        opt.dataset.tipo_documento);
        setIfEmpty('documento',             opt.dataset.documento);
        setIfEmpty('nombre_cobrador',       opt.dataset.nombre);
        setIfEmpty('email_cobrador',        opt.dataset.email);
        setIfEmpty('telefono_cobrador',     opt.dataset.telefono);
        setIfEmpty('banco_destino',         opt.dataset.banco);
        setIfEmpty('tipo_cuenta_destino',   opt.dataset.tipo_cuenta);
        setIfEmpty('numero_cuenta_destino', opt.dataset.numero_cuenta);
        setIfEmpty('titular_cuenta',        opt.dataset.titular_cuenta);
    });
});
function parseMonto(str) { if (!str) return 0; let s = String(str).replace(/[\$\s]/g,''); if (s.indexOf(',') !== -1) s = s.replace(/\./g,'').replace(',','.'); else s = s.replace(/\./g,''); return parseFloat(s) || 0; }
function fmtMonto(n) { return '$' + new Intl.NumberFormat('es-CO',{maximumFractionDigits:0}).format(n); }
const ids = ['valor_bruto','ret_fuente','ret_iva','ret_ica','otras_ded'];
function recalc() {
    const b = parseMonto(document.getElementById('valor_bruto').value);
    const rf = parseMonto(document.getElementById('ret_fuente').value);
    const ri = parseMonto(document.getElementById('ret_iva').value);
    const rc = parseMonto(document.getElementById('ret_ica').value);
    const od = parseMonto(document.getElementById('otras_ded').value);
    const ret = rf+ri+rc+od;
    document.getElementById('r_bruto').textContent = fmtMonto(b);
    document.getElementById('r_ret').textContent = fmtMonto(ret);
    document.getElementById('r_neto').textContent = fmtMonto(b - ret);
}
ids.forEach(id => {
    document.getElementById(id).addEventListener('input', recalc);
    document.getElementById(id).addEventListener('blur', e => {
        const n = parseMonto(e.target.value);
        e.target.value = n > 0 ? new Intl.NumberFormat('es-CO').format(n) : '';
        recalc();
    });
});
document.getElementById('valor_neto').addEventListener('blur', e => {
    const n = parseMonto(e.target.value);
    e.target.value = n > 0 ? new Intl.NumberFormat('es-CO').format(n) : '';
});
recalc();

// Antes de submit: normalizar campos monetarios a entero puro
document.getElementById('formCCedit').addEventListener('submit', () => {
    ['valor_bruto','ret_fuente','ret_iva','ret_ica','otras_ded','valor_neto'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = String(parseMonto(el.value));
    });
});

const $dz=document.getElementById('dropzone'),$file=document.getElementById('archivo_pdf'),$text=document.getElementById('dz-text');
$dz.addEventListener('click',()=>$file.click());
$dz.addEventListener('dragover',e=>{e.preventDefault();$dz.classList.add('dragover');});
$dz.addEventListener('dragleave',()=>$dz.classList.remove('dragover'));
$dz.addEventListener('drop',e=>{e.preventDefault();$dz.classList.remove('dragover');if(e.dataTransfer.files.length){$file.files=e.dataTransfer.files;update();}});
$file.addEventListener('change',update);
function update(){if($file.files.length){const f=$file.files[0];$text.innerHTML=`<i class="bi bi-file-pdf-fill text-danger"></i> <strong>${f.name}</strong>`;}}
</script>
</body>
</html>
