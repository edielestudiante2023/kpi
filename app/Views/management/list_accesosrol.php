<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Accesos por Rol – Afilogro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        #tablaAccesos {
            width: 100% !important;
            table-layout: fixed;
        }
        #tablaAccesos th, #tablaAccesos td {
            white-space: normal !important;
            word-break: break-word;
        }
    </style>
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4">Listado de Accesos por Rol</h1>
        <a href="<?= base_url('accesosrol/add') ?>" class="btn btn-primary">+ Nuevo Acceso</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <table id="tablaAccesos" class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Rol</th>
                <th>Detalle</th>
                <th>Enlace</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accesos as $a): ?>
                <tr>
                    <td><?= esc($a['nombre_rol']) ?></td>
                    <td><?= esc($a['detalle']) ?></td>
                    <td class="text-center">
                        <a href="<?= base_url($a['enlace']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                            Abrir
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-<?= $a['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($a['estado']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="<?= base_url('accesosrol/edit/' . $a['id_acceso']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                        <a href="<?= base_url('accesosrol/delete/' . $a['id_acceso']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Deseas eliminar este acceso?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->include('partials/logout') ?>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        $('#tablaAccesos').DataTable({
            responsive: true,
            autoWidth: false,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            }
        });
    });
</script>
</body>
</html>
