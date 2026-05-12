<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas de Cobro – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div class="toast show align-items-center text-white bg-success border-0">
        <div class="d-flex">
            <div class="toast-body"><?= session()->getFlashdata('success') ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>
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

<?php
$pendiente = $resumenEstado['pendiente'] ?? ['cnt' => 0, 'total_neto' => 0];
$pagada    = $resumenEstado['pagada']    ?? ['cnt' => 0, 'total_neto' => 0];
$castigada = $resumenEstado['castigada'] ?? ['cnt' => 0, 'total_neto' => 0];
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><i class="bi bi-file-earmark-pdf me-2"></i>Cuentas de Cobro</h1>
        <div class="d-flex gap-2">
            <form method="get" class="d-flex gap-2 align-items-center">
                <select name="anio" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
                    <?php foreach ($anios as $a): ?>
                        <option value="<?= $a ?>" <?= $anioActual == $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                    <?php if (! in_array((int)date('Y'), array_map('intval', $anios), true)): ?>
                        <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                    <?php endif; ?>
                </select>
                <select name="estado" class="form-select form-select-sm" style="width:130px;" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?= $filtroEstado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="pagada"    <?= $filtroEstado === 'pagada'    ? 'selected' : '' ?>>Pagada</option>
                    <option value="castigada" <?= $filtroEstado === 'castigada' ? 'selected' : '' ?>>Castigada</option>
                </select>
                <select name="centro" class="form-select form-select-sm" style="width:150px;" onchange="this.form.submit()">
                    <option value="">Todos los centros</option>
                    <?php foreach ($centros as $c): ?>
                        <option value="<?= $c['id_centro_costo'] ?>" <?= $filtroCentro == $c['id_centro_costo'] ? 'selected' : '' ?>><?= esc($c['centro_costo']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="busqueda" value="<?= esc($filtroBusqueda) ?>" class="form-control form-control-sm" placeholder="Buscar..." style="width:160px;">
                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <a href="<?= base_url('conciliaciones/cuentas-cobro/crear') ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nueva cuenta
            </a>
        </div>
    </div>

    <!-- Cards de resumen -->
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body py-2 px-3">
                    <small class="text-muted">PENDIENTES <?= $anioActual ?></small>
                    <h4 class="mb-0 text-warning">$<?= number_format((float)$pendiente['total_neto'], 0, ',', '.') ?></h4>
                    <small><?= (int) $pendiente['cnt'] ?> cuenta(s) por pagar</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body py-2 px-3">
                    <small class="text-muted">PAGADAS <?= $anioActual ?></small>
                    <h4 class="mb-0 text-success">$<?= number_format((float)$pagada['total_neto'], 0, ',', '.') ?></h4>
                    <small><?= (int) $pagada['cnt'] ?> cuenta(s) pagadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-secondary">
                <div class="card-body py-2 px-3">
                    <small class="text-muted">CASTIGADAS <?= $anioActual ?></small>
                    <h4 class="mb-0 text-secondary">$<?= number_format((float)$castigada['total_neto'], 0, ',', '.') ?></h4>
                    <small><?= (int) $castigada['cnt'] ?> cuenta(s)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaCC" class="table table-striped table-hover mb-0" style="font-size:0.85rem;">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Documento</th>
                            <th>Cobrador</th>
                            <th>Centro</th>
                            <th>Servicio</th>
                            <th class="text-end">Bruto</th>
                            <th class="text-end">Retenciones</th>
                            <th class="text-end">Neto</th>
                            <th>Estado</th>
                            <th>PDF</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($cuentas)): ?>
                        <tr><td colspan="12" class="text-center text-muted py-4">Sin cuentas de cobro. <a href="<?= base_url('conciliaciones/cuentas-cobro/crear') ?>">Crear la primera</a>.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($cuentas as $cc): ?>
                        <tr>
                            <td><?= $cc['id_cuenta_cobro'] ?></td>
                            <td><?= date('d/m/Y', strtotime($cc['fecha_gasto'])) ?></td>
                            <td><small><?= esc($cc['tipo_documento']) ?></small> <?= esc($cc['documento']) ?></td>
                            <td><?= esc($cc['nombre_cobrador']) ?></td>
                            <td><?= esc($cc['centro_costo']) ?></td>
                            <td><?= esc(mb_substr($cc['descripcion_servicio'] ?? '', 0, 60)) ?><?= mb_strlen($cc['descripcion_servicio'] ?? '') > 60 ? '…' : '' ?></td>
                            <td class="text-end">$<?= number_format((float)$cc['valor_bruto'], 0, ',', '.') ?></td>
                            <td class="text-end text-danger">$<?= number_format((float)$cc['total_retenciones'], 0, ',', '.') ?></td>
                            <td class="text-end fw-bold">$<?= number_format((float)$cc['valor_neto_a_pagar'], 0, ',', '.') ?></td>
                            <td>
                                <?php $b = ['pendiente'=>'warning text-dark','pagada'=>'success','castigada'=>'secondary']; ?>
                                <span class="badge bg-<?= $b[$cc['estado']] ?? 'secondary' ?>"><?= strtoupper($cc['estado']) ?></span>
                            </td>
                            <td>
                                <a href="<?= base_url('conciliaciones/cuentas-cobro/pdf/' . $cc['id_cuenta_cobro']) ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Ver PDF">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                            </td>
                            <td>
                                <a href="<?= base_url('conciliaciones/cuentas-cobro/ver/' . $cc['id_cuenta_cobro']) ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= base_url('conciliaciones/cuentas-cobro/editar/' . $cc['id_cuenta_cobro']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= base_url('conciliaciones/cuentas-cobro/eliminar/' . $cc['id_cuenta_cobro']) ?>"
                                   onclick="return confirm('¿Eliminar la cuenta #<?= $cc['id_cuenta_cobro'] ?>? También se borra el PDF.')"
                                   class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#tablaCC').DataTable({
        pageLength: 25,
        order: [[1, 'desc']],
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_",
            info: "_START_-_END_ de _TOTAL_",
            paginate: { first: "«", last: "»", next: "›", previous: "‹" },
            zeroRecords: "Sin resultados"
        }
    });
    setTimeout(() => $('.toast').toast('hide'), 8000);
});
</script>
</body>
</html>
