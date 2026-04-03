<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación Cruda – Conciliaciones – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('conciliaciones/cruda/facturacion') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1 class="h3 mb-0">Facturación Cruda (<?= number_format(count($registros), 0, ',', '.') ?> registros)</h1>
        </div>
        <a href="<?= base_url('conciliaciones/cruda/facturacion') ?>" class="btn btn-primary">
            <i class="bi bi-upload me-1"></i> Cargar CSV
        </a>
    </div>

    <table id="factCrudaTable" class="table table-striped table-hover nowrap" style="width:100%; font-size:0.85rem;">
        <thead class="table-dark">
            <tr>
                <th>Comprobante</th>
                <th>Fecha Elab.</th>
                <th>NIT</th>
                <th>Sucursal</th>
                <th>Cliente</th>
                <th class="text-end">Base Gravada</th>
                <th class="text-end">Base Exenta</th>
                <th class="text-end">IVA</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= esc($r['comprobante']) ?></td>
                <td><?= date('d/m/Y', strtotime($r['fecha_elaboracion'])) ?></td>
                <td><?= esc($r['identificacion']) ?></td>
                <td><?= esc($r['sucursal'] ?? '') ?></td>
                <td><?= esc($r['nombre_tercero']) ?></td>
                <td class="text-end"><?= number_format((float)$r['base_gravada'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format((float)$r['base_exenta'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format((float)$r['iva'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format((float)$r['total'], 0, ',', '.') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#factCrudaTable').DataTable({
        pageLength: 50, lengthMenu: [[50,100,200,-1],[50,100,200,'Todos']],
        responsive: true, autoWidth: false, order: [[1,'desc']],
        language: { search:"Buscar:", lengthMenu:"Mostrar _MENU_ registros",
            info:"Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate:{first:"Primero",last:"Último",next:"Siguiente",previous:"Anterior"},
            zeroRecords:"No se encontraron registros" }
    });
});
</script>
</body>
</html>
