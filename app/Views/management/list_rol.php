<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Roles – Afilogro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Listado de Roles</h1>
        <a href="<?= base_url('roles/add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Rol
        </a>
    </div>
    <?php if(session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <table id="rolTable" class="table table-striped table-bordered nowrap" style="width:100%">
        <thead class="table-dark">
            <tr>
                <th>Nombre Rol</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($roles as $r): ?>
            <tr>
                <td><?= esc($r['nombre_rol']) ?></td>
                <td class="text-center">
                    <a href="<?= base_url('roles/edit/'.$r['id_roles']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                    <a href="<?= base_url('roles/delete/'.$r['id_roles']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este rol?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->include('partials/logout') ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#rolTable').DataTable({ responsive:true, autoWidth:false, language: {search:'Buscar:',paginate:{next:'Siguiente',previous:'Anterior'}} });
});
</script>
</body>
</html>