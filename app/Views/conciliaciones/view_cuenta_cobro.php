<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta de Cobro #<?= $cc['id_cuenta_cobro'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .field-row { padding: 4px 0; border-bottom: 1px solid #f1f3f5; }
        .field-row:last-child { border-bottom: none; }
        .field-label { font-size: 0.72rem; text-transform: uppercase; color: #6c757d; font-weight: 600; }
        .field-value { font-size: 0.92rem; color: #212529; }
        .pdf-wrap { height: calc(100vh - 200px); min-height: 600px; }
        .pdf-wrap iframe { width: 100%; height: 100%; border: 1px solid #dee2e6; border-radius: 6px; }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div class="toast show align-items-center text-white bg-success border-0">
        <div class="d-flex"><div class="toast-body"><?= session()->getFlashdata('success') ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
    </div>
</div>
<?php endif; ?>

<?php
$estadoBadge = ['pendiente'=>'warning text-dark','pagada'=>'success','castigada'=>'secondary'];
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h5 mb-0">
                <i class="bi bi-file-earmark-pdf me-2"></i>Cuenta de Cobro #<?= $cc['id_cuenta_cobro'] ?>
                <span class="badge bg-<?= $estadoBadge[$cc['estado']] ?? 'secondary' ?>"><?= strtoupper($cc['estado']) ?></span>
            </h1>
            <small class="text-muted">
                Creada por <?= esc($cc['creado_por']) ?> el <?= date('d/m/Y H:i', strtotime($cc['created_at'])) ?>
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('conciliaciones/cuentas-cobro') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <a href="<?= base_url('conciliaciones/cuentas-cobro/editar/' . $cc['id_cuenta_cobro']) ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="<?= base_url('conciliaciones/cuentas-cobro/descargar/' . $cc['id_cuenta_cobro']) ?>" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-download"></i> Descargar PDF
            </a>
            <?php if ($cc['estado'] !== 'pagada'): ?>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPagar">
                <i class="bi bi-check-circle"></i> Marcar pagada
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Datos -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-bold"><i class="bi bi-person me-1"></i>Cobrador</div>
                <div class="card-body py-2">
                    <div class="field-row">
                        <div class="field-label">Documento</div>
                        <div class="field-value"><?= esc($cc['tipo_documento']) ?> <strong><?= esc($cc['documento']) ?></strong></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Nombre</div>
                        <div class="field-value"><?= esc($cc['nombre_cobrador']) ?></div>
                    </div>
                    <?php if (!empty($cc['email_cobrador'])): ?>
                    <div class="field-row"><div class="field-label">Email</div><div class="field-value"><a href="mailto:<?= esc($cc['email_cobrador']) ?>"><?= esc($cc['email_cobrador']) ?></a></div></div>
                    <?php endif; ?>
                    <?php if (!empty($cc['telefono_cobrador'])): ?>
                    <div class="field-row"><div class="field-label">Teléfono</div><div class="field-value"><?= esc($cc['telefono_cobrador']) ?></div></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-bold"><i class="bi bi-briefcase me-1"></i>Servicio</div>
                <div class="card-body py-2">
                    <div class="field-row"><div class="field-label">Centro de costo</div><div class="field-value"><?= esc($cc['centro_costo']) ?></div></div>
                    <?php if (!empty($cc['clasificacion'])): ?>
                    <div class="field-row"><div class="field-label">Clasificación</div><div class="field-value"><?= esc($cc['clasificacion']->categoria) ?> / <?= esc($cc['clasificacion']->llave_item) ?></div></div>
                    <?php endif; ?>
                    <div class="field-row"><div class="field-label">Descripción</div><div class="field-value"><?= nl2br(esc($cc['descripcion_servicio'])) ?></div></div>
                    <div class="field-row"><div class="field-label">Fecha del gasto</div><div class="field-value"><?= date('d/m/Y', strtotime($cc['fecha_gasto'])) ?></div></div>
                    <?php if (!empty($cc['periodo_desde']) || !empty($cc['periodo_hasta'])): ?>
                    <div class="field-row"><div class="field-label">Período</div><div class="field-value">
                        <?= $cc['periodo_desde'] ? date('d/m/Y', strtotime($cc['periodo_desde'])) : '—' ?>
                        →
                        <?= $cc['periodo_hasta'] ? date('d/m/Y', strtotime($cc['periodo_hasta'])) : '—' ?>
                    </div></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-3 border-primary">
                <div class="card-header bg-primary text-white fw-bold"><i class="bi bi-cash me-1"></i>Valores</div>
                <div class="card-body py-2">
                    <div class="field-row d-flex justify-content-between"><span class="field-label">Valor bruto</span><strong>$<?= number_format((float)$cc['valor_bruto'], 0, ',', '.') ?></strong></div>
                    <div class="field-row d-flex justify-content-between"><span>Ret. fuente</span><span class="text-danger">-$<?= number_format((float)$cc['retencion_fuente'], 0, ',', '.') ?></span></div>
                    <div class="field-row d-flex justify-content-between"><span>Ret. IVA</span><span class="text-danger">-$<?= number_format((float)$cc['retencion_iva'], 0, ',', '.') ?></span></div>
                    <div class="field-row d-flex justify-content-between"><span>Ret. ICA</span><span class="text-danger">-$<?= number_format((float)$cc['retencion_ica'], 0, ',', '.') ?></span></div>
                    <div class="field-row d-flex justify-content-between"><span>Otras deducciones</span><span class="text-danger">-$<?= number_format((float)$cc['otras_deducciones'], 0, ',', '.') ?></span></div>
                    <div class="field-row d-flex justify-content-between"><strong>Total retenciones</strong><strong class="text-danger">$<?= number_format((float)$cc['total_retenciones'], 0, ',', '.') ?></strong></div>
                    <div class="field-row d-flex justify-content-between mt-2 pt-2 border-top"><strong class="text-success">VALOR NETO A PAGAR</strong><strong class="text-success fs-5">$<?= number_format((float)$cc['valor_neto_a_pagar'], 0, ',', '.') ?></strong></div>
                </div>
            </div>

            <?php if (!empty($cc['banco_destino']) || !empty($cc['numero_cuenta_destino'])): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-bold"><i class="bi bi-credit-card me-1"></i>Datos bancarios</div>
                <div class="card-body py-2">
                    <?php if (!empty($cc['banco_destino'])): ?><div class="field-row"><div class="field-label">Banco</div><div class="field-value"><?= esc($cc['banco_destino']) ?></div></div><?php endif; ?>
                    <?php if (!empty($cc['tipo_cuenta_destino'])): ?><div class="field-row"><div class="field-label">Tipo cuenta</div><div class="field-value"><?= esc(ucfirst($cc['tipo_cuenta_destino'])) ?></div></div><?php endif; ?>
                    <?php if (!empty($cc['numero_cuenta_destino'])): ?><div class="field-row"><div class="field-label">N° cuenta</div><div class="field-value"><?= esc($cc['numero_cuenta_destino']) ?></div></div><?php endif; ?>
                    <?php if (!empty($cc['titular_cuenta'])): ?><div class="field-row"><div class="field-label">Titular</div><div class="field-value"><?= esc($cc['titular_cuenta']) ?></div></div><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($cc['estado'] === 'pagada'): ?>
            <div class="card shadow-sm mb-3 border-success">
                <div class="card-header bg-success text-white fw-bold"><i class="bi bi-check-circle me-1"></i>Información de pago</div>
                <div class="card-body py-2">
                    <div class="field-row"><div class="field-label">Fecha pago</div><div class="field-value"><?= $cc['fecha_pago'] ? date('d/m/Y', strtotime($cc['fecha_pago'])) : '—' ?></div></div>
                    <div class="field-row"><div class="field-label">Forma de pago</div><div class="field-value"><?= esc(ucfirst($cc['forma_pago'] ?? '')) ?></div></div>
                    <?php if (!empty($cc['referencia_pago'])): ?>
                    <div class="field-row"><div class="field-label">Referencia</div><div class="field-value"><?= esc($cc['referencia_pago']) ?></div></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($cc['notas'])): ?>
            <div class="card shadow-sm mb-3"><div class="card-header bg-white fw-bold"><i class="bi bi-sticky me-1"></i>Notas</div>
                <div class="card-body py-2"><?= nl2br(esc($cc['notas'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Preview PDF -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between">
                    <span class="fw-bold"><i class="bi bi-file-pdf me-1"></i><?= esc($cc['nombre_pdf_original']) ?></span>
                    <small class="text-muted"><?= number_format($cc['tamano_pdf']/1024, 0) ?> KB</small>
                </div>
                <div class="card-body p-2">
                    <div class="pdf-wrap">
                        <iframe src="<?= base_url('conciliaciones/cuentas-cobro/pdf/' . $cc['id_cuenta_cobro']) ?>"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagar -->
<?php if ($cc['estado'] !== 'pagada'): ?>
<div class="modal fade" id="modalPagar" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('conciliaciones/cuentas-cobro/pagar/' . $cc['id_cuenta_cobro']) ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-check-circle me-1"></i>Marcar como pagada</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light border">
            <strong>Cobrador:</strong> <?= esc($cc['nombre_cobrador']) ?><br>
            <strong>Valor a pagar:</strong> $<?= number_format((float)$cc['valor_neto_a_pagar'], 0, ',', '.') ?>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label">Fecha de pago</label>
                <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-6">
                <label class="form-label">Forma de pago</label>
                <select name="forma_pago" class="form-select" required>
                    <option value="transferencia">Transferencia</option>
                    <option value="cheque">Cheque</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Referencia / N° transacción</label>
                <input type="text" name="referencia_pago" class="form-control" placeholder="Ej: TRX-12345">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success">Confirmar pago</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>setTimeout(() => document.querySelectorAll('.toast').forEach(t => bootstrap.Toast.getOrCreateInstance(t).hide()), 7000);</script>
</body>
</html>
