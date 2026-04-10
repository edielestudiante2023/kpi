<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<?php
$filtrosActivos = array_filter([
    'anio'        => $anioActual,
    'rango'       => $rangoActual,
    'desde'       => $fechaDesde,
    'hasta'       => $fechaHasta,
    'pagado'      => $filtroPagado,
    'portafolio'  => $filtroPortafolio,
    'vencida'     => $filtroVencida,
    'estado_pago' => $filtroEstadoPago,
    'anticipo'    => $filtroAnticipo,
], fn($v) => $v !== null && $v !== '');

function cardUrl(array $base, array $override): string {
    $params = array_merge($base, $override);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return 'conciliaciones/facturacion?' . http_build_query($params);
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('conciliaciones/facturacion/upload') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1 class="h3 mb-0">Facturación (<?= number_format(count($registros), 0, ',', '.') ?> registros)</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="get" class="d-flex gap-2">
                <select name="anio" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
                    <option value="todos" <?= ($anioActual ?? '') === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <?php foreach ($anios as $a): ?>
                        <option value="<?= $a['anio'] ?>" <?= ($anioActual ?? '') == $a['anio'] ? 'selected' : '' ?>><?= $a['anio'] ?></option>
                    <?php endforeach; ?>
                </select>
                <?php
                $mesesNombre = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                $rangos = [
                    'todos' => 'Todo el año',
                    'mes_actual' => 'Mes actual',
                    'mes_anterior' => 'Mes anterior',
                    'bimestre_anterior' => 'Bimestre anterior',
                    'trimestre_anterior' => 'Trimestre anterior',
                    'cuatrimestre_anterior' => 'Cuatrimestre anterior',
                    'semestre_anterior' => 'Semestre anterior',
                ];
                ?>
                <select name="rango" class="form-select form-select-sm" style="width:170px;" onchange="this.form.submit()">
                    <?php foreach ($rangos as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($rangoActual ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                    <option value="personalizado" <?= ($rangoActual ?? '') === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
                    <optgroup label="Mes específico">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= sprintf('%02d', $m) ?>" <?= ($rangoActual ?? '') === sprintf('%02d', $m) ? 'selected' : '' ?>><?= $mesesNombre[$m] ?></option>
                        <?php endfor; ?>
                    </optgroup>
                </select>
                <input type="date" name="desde" class="form-control form-control-sm" style="width:140px;" value="<?= $fechaDesde ?? '' ?>" onchange="document.querySelector('[name=rango]').value='personalizado'; this.form.submit()">
                <input type="date" name="hasta" class="form-control form-control-sm" style="width:140px;" value="<?= $fechaHasta ?? '' ?>" onchange="document.querySelector('[name=rango]').value='personalizado'; this.form.submit()">
                <select name="estado_pago" class="form-select form-select-sm" style="width:130px;" onchange="this.form.submit()">
                    <option value="">Pagado</option>
                    <option value="pagado" <?= ($filtroEstadoPago ?? '') === 'pagado' ? 'selected' : '' ?>>SI</option>
                    <option value="pendiente" <?= ($filtroEstadoPago ?? '') === 'pendiente' ? 'selected' : '' ?>>NO</option>
                    <option value="brecha" <?= ($filtroEstadoPago ?? '') === 'brecha' ? 'selected' : '' ?>>BRECHA</option>
                    <option value="castigada" <?= ($filtroEstadoPago ?? '') === 'castigada' ? 'selected' : '' ?>>CASTIGADA</option>
                </select>
                <?php if ($filtroPortafolio): ?><input type="hidden" name="portafolio" value="<?= $filtroPortafolio ?>"><?php endif; ?>
                <?php if ($filtroPagado !== null && $filtroPagado !== ''): ?><input type="hidden" name="pagado" value="<?= $filtroPagado ?>"><?php endif; ?>
                <?php if ($filtroVencida): ?><input type="hidden" name="vencida" value="<?= $filtroVencida ?>"><?php endif; ?>
                <?php if ($filtroAnticipo): ?><input type="hidden" name="anticipo" value="<?= $filtroAnticipo ?>"><?php endif; ?>
            </form>
            <a href="<?= base_url('conciliaciones/facturacion/exportar?' . http_build_query($filtrosActivos)) ?>" class="btn btn-outline-success btn-sm" title="Descargar Excel">
                <i class="bi bi-download"></i>
            </a>
            <a href="<?= base_url('conciliaciones/facturacion') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
                <i class="bi bi-eraser"></i>
            </a>
            <a href="<?= base_url('conciliaciones/facturacion/upload') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-upload me-1"></i> Cargar Excel
            </a>
        </div>
    </div>

    <!-- Cards: Totales financieros -->
    <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
        <div class="card border-success" style="min-width:130px;" data-bs-toggle="tooltip" title="Suma de la base gravada de todas las facturas en el período. Es el valor antes de IVA y retenciones.">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold">BASE GRAVADA</small>
                <p class="fw-bold mb-0 text-success">$<?= number_format($totalBaseGravada, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="card border-warning" style="min-width:130px;" data-bs-toggle="tooltip" title="Total del IVA (19%) calculado sobre la base gravada de las facturas.">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold">IVA</small>
                <p class="fw-bold mb-0 text-warning">$<?= number_format($totalIva, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="card border-danger" style="min-width:130px;" data-bs-toggle="tooltip" title="Retención en la fuente del 4% aplicada por el cliente al momento del pago. Se descuenta del valor a recibir.">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold">RETENCIÓN 4%</small>
                <p class="fw-bold mb-0 text-danger">-$<?= number_format($totalRetencion, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="card border-primary bg-primary bg-opacity-10" style="min-width:130px;" data-bs-toggle="tooltip" title="Valor líquido = Base Gravada + IVA - Retención 4%. Es el monto efectivo a recibir por las facturas.">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold">LÍQUIDO</small>
                <p class="fw-bold mb-0 text-primary">$<?= number_format($totalLiquido, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <!-- Cards: Pagado/No Pagado + Cartera Vencida + Portafolios -->
    <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
        <?php foreach ($resumenPagado as $rpg): ?>
        <a href="<?= base_url(cardUrl($filtrosActivos, ['pagado' => $rpg['pagado'], 'estado_pago' => '', 'vencida' => ''])) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="<?= $rpg['pagado'] ? 'Facturas ya pagadas por el cliente. Clic para filtrar solo las pagadas.' : 'Facturas pendientes de cobro (cartera). Clic para filtrar solo las pendientes.' ?>">
            <div class="card <?= ($filtroPagado ?? '') === (string)$rpg['pagado'] ? ($rpg['pagado'] ? 'bg-success text-white' : 'bg-danger text-white') : ($rpg['pagado'] ? 'border-success' : 'border-danger') ?>" style="cursor:pointer; min-width:130px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small class="fw-bold"><?= $rpg['pagado'] ? 'PAGADAS' : 'CARTERA' ?></small>
                    <p class="fw-bold mb-0">$<?= number_format((float)$rpg['total_base'], 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $rpg['facturas'] ?> facturas</small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <?php if (($resumenEstadoPago['brecha']['facturas'] ?? 0) > 0): ?>
        <a href="<?= base_url(cardUrl($filtrosActivos, ['estado_pago' => 'brecha', 'pagado' => '', 'vencida' => ''])) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Facturas con diferencia >= $2.000 entre liquidado y pagado. Clic para filtrar.">
            <div class="card <?= ($filtroEstadoPago ?? '') === 'brecha' ? 'bg-warning text-dark' : 'border-warning' ?>" style="cursor:pointer; min-width:130px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small class="fw-bold <?= ($filtroEstadoPago ?? '') === 'brecha' ? '' : 'text-warning' ?>">BRECHA</small>
                    <p class="fw-bold mb-0 <?= ($filtroEstadoPago ?? '') === 'brecha' ? '' : 'text-warning' ?>">$<?= number_format((float)($resumenEstadoPago['brecha']['total_base'] ?? 0), 0, ',', '.') ?></p>
                    <small class="<?= ($filtroEstadoPago ?? '') === 'brecha' ? '' : 'text-muted' ?>"><?= $resumenEstadoPago['brecha']['facturas'] ?> facturas</small>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <?php if ($facturasVencidas > 0): ?>
        <a href="<?= base_url(cardUrl($filtrosActivos, ['vencida' => '1', 'pagado' => '', 'estado_pago' => ''])) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Facturas no pagadas con más de 30 días desde su elaboración. Requieren gestión de cobro urgente. Clic para filtrar.">
        <div class="card border-dark <?= ($filtroVencida ?? '') === '1' ? 'bg-danger text-white' : 'bg-danger bg-opacity-10' ?>" style="cursor:pointer; min-width:140px;">
            <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                <small class="fw-bold <?= ($filtroVencida ?? '') === '1' ? '' : 'text-danger' ?>">CARTERA VENCIDA</small>
                <p class="fw-bold mb-0 <?= ($filtroVencida ?? '') === '1' ? '' : 'text-danger' ?>">$<?= number_format($totalCarteraVencida, 0, ',', '.') ?></p>
                <small class="<?= ($filtroVencida ?? '') === '1' ? '' : 'text-muted' ?>"><?= $facturasVencidas ?> facturas &gt;30 días</small>
            </div>
        </div>
        </a>
        <?php endif; ?>

        <span class="border-start mx-1" style="height:40px;"></span>

        <a href="<?= base_url('conciliaciones/facturacion?' . http_build_query(array_filter(['anio' => $anioActual, 'rango' => $rangoActual, 'desde' => $fechaDesde, 'hasta' => $fechaHasta]))) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Quitar filtros de estado y portafolio. Muestra todas las facturas.">
            <div class="card <?= empty($filtroPortafolio) && ($filtroPagado === null || $filtroPagado === '') && empty($filtroEstadoPago) ? 'bg-dark text-white' : 'border-dark' ?>" style="cursor:pointer; min-width:70px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small>Todas</small>
                    <p class="fw-bold mb-0"><?= number_format(count($registros)) ?></p>
                </div>
            </div>
        </a>
        <?php foreach ($resumenPortafolios as $rp): ?>
        <a href="<?= base_url(cardUrl($filtrosActivos, ['portafolio' => $rp['id_portafolio']])) ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Portafolio: <?= esc($rp['portafolio']) ?>. <?= $rp['facturas'] ?> facturas por $<?= number_format((float)$rp['total_base'], 0, ',', '.') ?>. Clic para filtrar.">
            <div class="card <?= ($filtroPortafolio ?? '') == $rp['id_portafolio'] ? 'bg-primary text-white' : 'border-primary' ?>" style="cursor:pointer; min-width:100px;">
                <div class="card-body py-1 px-2 text-center" style="font-size:0.8rem;">
                    <small><?= esc($rp['portafolio']) ?></small>
                    <p class="fw-bold mb-0 text-success">$<?= number_format((float)$rp['total_base'], 0, ',', '.') ?></p>
                    <small class="text-muted"><?= $rp['facturas'] ?></small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive">
    <table id="facturacionTable" class="table table-striped table-hover nowrap" style="width:100%; font-size:0.85rem;">
        <thead class="table-dark">
            <tr>
                <th>Portafolio</th>
                <th>Año</th>
                <th>Mes</th>
                <th>Comprobante</th>
                <th>Fecha Elab.</th>
                <th>NIT</th>
                <th>Cliente</th>
                <th class="text-end">Base Gravada</th>
                <th class="text-end">IVA</th>
                <th class="text-end">Ret. 4%</th>
                <th class="text-end">Líquido</th>
                <th>Pagado</th>
                <th>Fecha Pago</th>
                <th class="text-end">Valor Pagado</th>
                <th>Fecha Anticipo</th>
                <th class="text-end">Anticipo</th>
                <th>Vendedor</th>
                <th>Detallado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Comprobante..."></th>
                <th></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="NIT..."></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Cliente..."></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th><select class="form-select form-select-sm"><option value="">Todos</option></select></th>
                <th></th>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= esc($r['portafolio'] ?? '') ?></td>
                <td><?= esc($r['anio']) ?></td>
                <td><?= esc($r['mes']) ?></td>
                <td><?= esc($r['comprobante']) ?></td>
                <td data-order="<?= $r['fecha_elaboracion'] ?? '' ?>"><?= $r['fecha_elaboracion'] ? date('d/m/Y', strtotime($r['fecha_elaboracion'])) : '' ?></td>
                <td><?= esc($r['identificacion']) ?></td>
                <td><?= esc($r['nombre_tercero']) ?></td>
                <td class="text-end"><?= number_format((float)$r['base_gravada'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format((float)$r['iva'], 0, ',', '.') ?></td>
                <td class="text-end text-danger"><?= number_format(abs((float)$r['retefuente_4']), 0, ',', '.') ?></td>
                <td class="text-end text-success fw-bold"><?= number_format((float)$r['base_gravada'] - abs((float)$r['retefuente_4']) - (float)($r['anticipo'] ?? 0), 0, ',', '.') ?></td>
                <td>
                    <?php if (($r['estado_pago'] ?? '') === 'castigada'): ?>
                        <span class="badge bg-dark">CASTIGADA</span>
                    <?php elseif (($r['estado_pago'] ?? '') === 'anticipo'): ?>
                        <span class="badge bg-info">ANTICIPO</span>
                    <?php elseif (($r['estado_pago'] ?? '') === 'brecha'): ?>
                        <span class="badge bg-warning text-dark">BRECHA</span>
                    <?php elseif ($r['pagado']): ?>
                        <span class="badge bg-success">SI</span>
                    <?php else: ?>
                        <span class="badge bg-danger">NO</span>
                    <?php endif; ?>
                </td>
                <td data-order="<?= $r['fecha_pago'] ?? '' ?>"><?= $r['fecha_pago'] ? date('d/m/Y', strtotime($r['fecha_pago'])) : '' ?></td>
                <td class="text-end"><?= $r['valor_pagado'] ? number_format((float)$r['valor_pagado'], 0, ',', '.') : '' ?></td>
                <td data-order="<?= $r['fecha_anticipo'] ?? '' ?>"><?= $r['fecha_anticipo'] ? date('d/m/Y', strtotime($r['fecha_anticipo'])) : '' ?></td>
                <td class="text-end <?= (float)($r['anticipo'] ?? 0) > 0 ? 'text-info fw-bold' : '' ?>"><?= (float)($r['anticipo'] ?? 0) > 0 ? number_format((float)$r['anticipo'], 0, ',', '.') : '' ?></td>
                <td><?= esc($r['vendedor'] ?? '') ?></td>
                <td><?= esc($r['portafolio_detallado'] ?? '') ?></td>
                <td>
                    <button class="btn btn-outline-secondary btn-sm btn-conciliar" title="Conciliar pago"
                        data-id="<?= $r['id_facturacion'] ?>"
                        data-comprobante="<?= esc($r['comprobante']) ?>"
                        data-cliente="<?= esc($r['nombre_tercero']) ?>"
                        data-base="<?= number_format((float)$r['base_gravada'], 0, ',', '.') ?>"
                        data-liquido="<?= number_format((float)$r['base_gravada'] - abs((float)$r['retefuente_4']) - (float)($r['anticipo'] ?? 0), 0, ',', '.') ?>"
                        data-estado="<?= $r['estado_pago'] ?? 'pendiente' ?>"
                        data-fecha-pago="<?= $r['fecha_pago'] ?? '' ?>"
                        data-valor-pagado="<?= (float)($r['valor_pagado'] ?? 0) ?>"
                        data-fecha-anticipo="<?= $r['fecha_anticipo'] ?? '' ?>"
                        data-anticipo="<?= (float)($r['anticipo'] ?? 0) ?>">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Modal Conciliar Pago -->
<div class="modal fade" id="modalConciliar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Conciliar Pago</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light border mb-3">
            <div class="row">
                <div class="col-6"><small class="text-muted">Comprobante</small><br><strong id="concComprobante"></strong></div>
                <div class="col-6"><small class="text-muted">Cliente</small><br><strong id="concCliente"></strong></div>
            </div>
            <div class="row mt-2">
                <div class="col-6"><small class="text-muted">Base Gravada</small><br><strong id="concBase"></strong></div>
                <div class="col-6"><small class="text-muted">Líquido</small><br><strong id="concLiquido" class="text-success"></strong></div>
            </div>
        </div>
        <input type="hidden" id="concIdFacturacion" value="">

        <div class="mb-3">
            <label class="form-label fw-bold">Estado de pago</label>
            <select id="concEstado" class="form-select">
                <option value="pendiente">NO (Pendiente)</option>
                <option value="pagado">SI (Pagado)</option>
                <option value="brecha">BRECHA</option>
                <option value="castigada">CASTIGADA</option>
            </select>
        </div>

        <div id="seccionPago">
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label">Fecha de pago</label>
                    <input type="date" id="concFechaPago" class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label">Valor pagado ($)</label>
                    <input type="number" id="concValorPagado" class="form-control" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
        </div>

        <hr>
        <p class="fw-bold mb-2"><i class="bi bi-cash-coin me-1"></i>Anticipo</p>
        <div class="row">
            <div class="col-6">
                <label class="form-label">Fecha anticipo</label>
                <input type="date" id="concFechaAnticipo" class="form-control">
            </div>
            <div class="col-6">
                <label class="form-label">Valor anticipo ($)</label>
                <input type="number" id="concValorAnticipo" class="form-control" step="0.01" min="0" placeholder="0.00">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarConciliar">
            <i class="bi bi-save me-1"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>

<script>
$(document).ready(function() {
    // Activar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

    var selectCols = [0, 1, 2, 11, 16, 17];
    var inputCols = [3, 5, 6];
    var table = $('#facturacionTable').DataTable({
        pageLength: 50,
        lengthMenu: [[50, 100, 200, -1], [50, 100, 200, 'Todos']],
        responsive: false,
        scrollX: true,
        autoWidth: false,
        order: [[4, 'desc']],
        initComplete: function () {
            var api = this.api();
            api.columns(selectCols).every(function () {
                var column = this;
                var select = $('select', column.footer());
                column.data().unique().sort().each(function (d) {
                    var txt = $('<div>').html(d).text().trim();
                    if (txt.length && select.find('option[value="'+txt+'"]').length === 0) {
                        select.append('<option value="'+txt+'">'+txt+'</option>');
                    }
                });
                select.on('change', function () {
                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                    column.search(val ? '^'+val+'$' : '', true, false).draw();
                });
            });
            // Inputs de texto
            api.columns(inputCols).every(function () {
                var column = this;
                $('input', column.footer()).on('keyup change', function () {
                    if (column.search() !== this.value) {
                        column.search(this.value).draw();
                    }
                });
            });
        },
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
            zeroRecords: "No se encontraron registros"
        }
    });

    // ── Modal Conciliar Pago ──
    // Mostrar/ocultar sección de pago según estado
    $('#concEstado').on('change', function() {
        var estado = $(this).val();
        if (estado === 'pagado' || estado === 'brecha') {
            $('#seccionPago').show();
        } else {
            $('#seccionPago').hide();
        }
    });

    // Abrir modal desde botón de la tabla
    $(document).on('click', '.btn-conciliar', function() {
        var btn = $(this);
        $('#concIdFacturacion').val(btn.data('id'));
        $('#concComprobante').text(btn.data('comprobante'));
        $('#concCliente').text(btn.data('cliente'));
        $('#concBase').text('$' + btn.data('base'));
        $('#concLiquido').text('$' + btn.data('liquido'));
        $('#concEstado').val(btn.data('estado')).trigger('change');
        $('#concFechaPago').val(btn.data('fecha-pago') || '');
        $('#concValorPagado').val(btn.data('valor-pagado') > 0 ? btn.data('valor-pagado') : '');
        $('#concFechaAnticipo').val(btn.data('fecha-anticipo') || '');
        $('#concValorAnticipo').val(btn.data('anticipo') > 0 ? btn.data('anticipo') : '');

        new bootstrap.Modal('#modalConciliar').show();
    });

    // Guardar conciliación
    $('#btnGuardarConciliar').on('click', function() {
        var id = $('#concIdFacturacion').val();
        if (!id) { alert('Error: factura no seleccionada.'); return; }

        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        $.post('<?= base_url("conciliaciones/facturacion/conciliar") ?>', {
            id_facturacion: id,
            estado_pago: $('#concEstado').val(),
            fecha_pago: $('#concFechaPago').val(),
            valor_pagado: $('#concValorPagado').val(),
            fecha_anticipo: $('#concFechaAnticipo').val(),
            anticipo: $('#concValorAnticipo').val(),
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        }).done(function(resp) {
            if (resp.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalConciliar')).hide();
                location.reload();
            } else {
                alert(resp.error || 'Error al guardar.');
            }
        }).fail(function() {
            alert('Error de conexión.');
        }).always(function() {
            btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Guardar');
        });
    });
});
</script>
</body>
</html>
