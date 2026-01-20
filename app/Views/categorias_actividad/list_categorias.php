<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías de Actividades - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .color-badge {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: inline-block;
            vertical-align: middle;
        }
        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-tags me-2"></i>Categorías de Actividades</h1>
                <p class="text-muted mb-0">Gestiona las categorías para clasificar actividades</p>
            </div>
            <a href="<?= base_url('categorias-actividad/nueva') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nueva Categoría
            </a>
        </div>

        <!-- Alertas -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabla -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($categorias)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mt-3">No hay categorías registradas</p>
                        <a href="<?= base_url('categorias-actividad/nueva') ?>" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Crear primera categoría
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">Color</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th class="text-center" style="width: 100px;">Estado</th>
                                    <th class="text-center" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $cat): ?>
                                    <tr>
                                        <td>
                                            <span class="color-badge" style="background-color: <?= esc($cat['color']) ?>;"></span>
                                        </td>
                                        <td>
                                            <strong><?= esc($cat['nombre_categoria']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?= esc($cat['descripcion']) ?: '—' ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input toggle-estado"
                                                       type="checkbox"
                                                       data-id="<?= $cat['id_categoria'] ?>"
                                                       <?= $cat['estado'] === 'activa' ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= base_url('categorias-actividad/editar/' . $cat['id_categoria']) ?>"
                                                   class="btn btn-outline-primary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-outline-danger btn-eliminar"
                                                        data-id="<?= $cat['id_categoria'] ?>"
                                                        data-nombre="<?= esc($cat['nombre_categoria']) ?>"
                                                        title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info -->
        <div class="mt-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Las categorías inactivas no aparecerán al crear nuevas actividades.
            </small>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de eliminar la categoría <strong id="nombreCategoria"></strong>?</p>
                    <p class="text-muted small mb-0">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="formEliminar" method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle estado
        document.querySelectorAll('.toggle-estado').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const id = this.dataset.id;

                fetch(`<?= base_url('categorias-actividad/toggle-estado') ?>/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Error al cambiar estado');
                        this.checked = !this.checked;
                    }
                })
                .catch(() => {
                    alert('Error de conexión');
                    this.checked = !this.checked;
                });
            });
        });

        // Modal eliminar
        const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nombre = this.dataset.nombre;

                document.getElementById('nombreCategoria').textContent = nombre;
                document.getElementById('formEliminar').action = `<?= base_url('categorias-actividad/eliminar') ?>/${id}`;

                modalEliminar.show();
            });
        });
    </script>
</body>
</html>
