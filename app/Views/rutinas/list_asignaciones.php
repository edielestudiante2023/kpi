<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignaciones de Rutina – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <?= view('components/back_to_dashboard') ?>
        <h1 class="h3 mb-0"><i class="bi bi-person-plus me-2"></i>Asignaciones de Rutina</h1>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario nueva asignación -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-plus-lg me-1"></i> Nueva Asignacion
        </div>
        <div class="card-body">
            <form method="post" action="<?= base_url('rutinas/asignaciones/add') ?>">
                <?= csrf_field() ?>
                <div class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Usuario</label>
                        <select name="id_users" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id_users'] ?>"><?= esc($u['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Actividades</label>
                        <div class="border rounded p-2" style="max-height:150px;overflow-y:auto;">
                            <?php foreach ($actividades as $act): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="actividades[]" value="<?= $act['id_actividad'] ?>"
                                           id="act_<?= $act['id_actividad'] ?>">
                                    <label class="form-check-label" for="act_<?= $act['id_actividad'] ?>">
                                        <?= esc($act['nombre']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>Asignar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de asignaciones -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table id="tblAsignaciones" class="table table-striped table-hover nowrap" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Actividad</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($asignaciones as $a): ?>
                    <tr>
                        <td><?= esc($a['nombre_completo']) ?></td>
                        <td><?= esc($a['correo']) ?></td>
                        <td><?= esc($a['actividad_nombre']) ?></td>
                        <td>
                            <?= $a['activa']
                                ? '<span class="badge bg-success">Activa</span>'
                                : '<span class="badge bg-secondary">Inactiva</span>'
                            ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('rutinas/asignaciones/delete/' . $a['id_asignacion']) ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Quitar esta asignacion?')">
                                <i class="bi bi-trash"></i> Quitar
                            </a>
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
    $('#tblAsignaciones').DataTable({
        pageLength: 20,
        responsive: true,
        language: {
            search: "Buscar:", lengthMenu: "Mostrar _MENU_",
            info: "_START_ a _END_ de _TOTAL_", emptyTable: "Sin asignaciones",
            paginate: { previous: "Ant", next: "Sig" }
        }
    });
});
</script>
</body>
</html>
