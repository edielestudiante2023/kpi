<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades de Rutina – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <?= view('components/back_to_dashboard') ?>
            <h1 class="h3 mb-0"><i class="bi bi-list-task me-2"></i>Actividades de Rutina</h1>
        </div>
        <a href="<?= base_url('rutinas/actividades/add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva Actividad
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="tblActividades" class="table table-striped table-hover nowrap" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Categoria</th>
                        <th>Descripcion</th>
                        <th>Frecuencia</th>
                        <th>Peso</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $colorCategoria = function($cat) {
                    $colors = ['Operativa'=>'primary','Comercial'=>'success','SST'=>'danger','Bitacora'=>'warning','Reportes'=>'info','General'=>'secondary'];
                    return $colors[$cat] ?? 'dark';
                };
                ?>
                <?php foreach ($actividades as $a): ?>
                    <tr>
                        <td><?= esc($a['nombre']) ?></td>
                        <td><span class="badge bg-<?= $colorCategoria($a['categoria'] ?? 'General') ?>"><?= esc($a['categoria'] ?? 'General') ?></span></td>
                        <td><?= esc($a['descripcion'] ?? '-') ?></td>
                        <td>
                            <?php if (($a['frecuencia'] ?? '') === 'diaria'): ?>
                                <span class="badge bg-info" data-bs-toggle="tooltip" title="Diaria (incluye fines de semana)">📅 diaria</span>
                            <?php else: ?>
                                <span class="badge bg-info" data-bs-toggle="tooltip" title="Lunes a Viernes">📆 L-V</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($a['peso']) ?></td>
                        <td>
                            <?= $a['activa']
                                ? '<span class="badge bg-success">Activa</span>'
                                : '<span class="badge bg-secondary">Inactiva</span>'
                            ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('rutinas/actividades/edit/' . $a['id_actividad']) ?>"
                               class="btn btn-sm btn-warning me-1"><i class="bi bi-pencil"></i> Editar</a>
                            <a href="<?= base_url('rutinas/actividades/delete/' . $a['id_actividad']) ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Eliminar esta actividad?')"><i class="bi bi-trash"></i> Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

    $('#tblActividades').DataTable({
        pageLength: 20,
        responsive: true,
        language: {
            search: "Buscar:", lengthMenu: "Mostrar _MENU_",
            info: "_START_ a _END_ de _TOTAL_", emptyTable: "Sin datos",
            paginate: { previous: "Ant", next: "Sig" }
        }
    });
});
</script>
</body>
</html>
