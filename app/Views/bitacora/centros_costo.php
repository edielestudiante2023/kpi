<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<h6 class="text-muted mb-3">
    <i class="bi bi-building me-1"></i> Centros de Costo
</h6>

<!-- Formulario agregar -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-title mb-3">
            <i class="bi bi-plus-circle text-primary me-1"></i>
            <span id="formTitulo">Nuevo Centro de Costo</span>
        </h6>
        <input type="hidden" id="editId" value="">
        <div class="mb-2">
            <input type="text" class="form-control" id="txtNombre" placeholder="Nombre del centro de costo" required>
        </div>
        <div class="mb-2">
            <input type="text" class="form-control" id="txtDescripcion" placeholder="Descripcion (opcional)">
        </div>
        <!-- Sugerencias IA de duplicados -->
        <div id="ccSugerenciasIA" class="d-none mb-2">
            <div class="alert alert-warning small py-2 mb-0">
                <i class="bi bi-robot me-1"></i> <strong>Posibles duplicados:</strong>
                <div id="ccListaSugerencias" class="mt-1"></div>
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-success" id="btnConfirmarCC">
                        <i class="bi bi-check-lg me-1"></i> Crear de todas formas
                    </button>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" id="btnGuardar">
                <i class="bi bi-check-lg me-1"></i> Guardar
            </button>
            <button class="btn btn-outline-secondary d-none" id="btnCancelar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</div>

<!-- Lista -->
<div id="listaCentros">
    <?php if (empty($centrosCosto)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No hay centros de costo registrados
        </div>
    <?php else: ?>
        <?php foreach ($centrosCosto as $cc): ?>
            <div class="actividad-card mb-2" id="cc-<?= $cc['id_centro_costo'] ?>">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold small">
                            <?= esc($cc['nombre']) ?>
                            <?php if ($cc['activo'] == 0): ?>
                                <span class="badge bg-secondary ms-1">Inactivo</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($cc['descripcion']): ?>
                            <div class="text-muted" style="font-size: 0.75rem;"><?= esc($cc['descripcion']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($cc['activo'] == 1): ?>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary btn-editar"
                                data-id="<?= $cc['id_centro_costo'] ?>"
                                data-nombre="<?= esc($cc['nombre']) ?>"
                                data-descripcion="<?= esc($cc['descripcion'] ?? '') ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-eliminar"
                                data-id="<?= $cc['id_centro_costo'] ?>">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
(function() {
    const BASE = '<?= base_url() ?>';
    const CSRF_NAME = '<?= csrf_token() ?>';
    const CSRF_HASH = '<?= csrf_hash() ?>';

    function ajax(method, url, data) {
        const opts = { method: method };
        if (data) {
            const fd = new FormData();
            for (const k in data) fd.append(k, data[k]);
            fd.append(CSRF_NAME, CSRF_HASH);
            opts.body = fd;
        }
        return fetch(BASE + url, opts).then(r => r.json());
    }

    function guardarDirecto() {
        const nombre = document.getElementById('txtNombre').value.trim();
        const desc   = document.getElementById('txtDescripcion').value.trim();
        const editId = document.getElementById('editId').value;
        const btn    = document.getElementById('btnGuardar');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

        const data = { nombre: nombre, descripcion: desc };
        if (editId) data.id_centro_costo = editId;

        ajax('POST', 'bitacora/centros-costo/guardar', data)
            .then(function(resp) {
                if (resp.ok) {
                    location.reload();
                } else {
                    alert(resp.error || 'Error al guardar');
                }
            })
            .catch(function() { alert('Error de conexion'); })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Guardar';
            });
    }

    // Guardar con verificación IA
    document.getElementById('btnGuardar').addEventListener('click', function() {
        const nombre = document.getElementById('txtNombre').value.trim();
        const editId = document.getElementById('editId').value;

        if (!nombre) { alert('El nombre es obligatorio'); return; }

        // Si es edición, guardar directamente sin verificar duplicados
        if (editId) {
            guardarDirecto();
            return;
        }

        // Si es nuevo, verificar duplicados con IA
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verificando...';
        document.getElementById('ccSugerenciasIA').classList.add('d-none');

        ajax('POST', 'bitacora/centros-costo/verificar-duplicado', { nombre: nombre })
            .then(function(resp) {
                if (resp.similares && resp.similares.length > 0) {
                    var lista = document.getElementById('ccListaSugerencias');
                    lista.innerHTML = resp.similares.map(function(s) {
                        return '<div class="mb-1"><i class="bi bi-arrow-right me-1"></i><strong>' +
                            s.nombre + '</strong>' +
                            (s.razon ? ' <span class="text-muted">(' + s.razon + ')</span>' : '') +
                            '</div>';
                    }).join('');
                    document.getElementById('ccSugerenciasIA').classList.remove('d-none');
                    document.getElementById('btnGuardar').disabled = false;
                    document.getElementById('btnGuardar').innerHTML = '<i class="bi bi-check-lg me-1"></i> Guardar';
                } else {
                    guardarDirecto();
                }
            })
            .catch(function() {
                // Si falla la IA, guardar sin verificar
                guardarDirecto();
            });
    });

    // Confirmar crear a pesar de duplicados
    document.getElementById('btnConfirmarCC').addEventListener('click', function() {
        document.getElementById('ccSugerenciasIA').classList.add('d-none');
        guardarDirecto();
    });

    // Editar
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('txtNombre').value = this.dataset.nombre;
            document.getElementById('txtDescripcion').value = this.dataset.descripcion;
            document.getElementById('formTitulo').textContent = 'Editar Centro de Costo';
            document.getElementById('btnCancelar').classList.remove('d-none');
            document.getElementById('ccSugerenciasIA').classList.add('d-none');
            document.getElementById('txtNombre').focus();
        });
    });

    // Cancelar edición
    document.getElementById('btnCancelar').addEventListener('click', function() {
        document.getElementById('editId').value = '';
        document.getElementById('txtNombre').value = '';
        document.getElementById('txtDescripcion').value = '';
        document.getElementById('formTitulo').textContent = 'Nuevo Centro de Costo';
        document.getElementById('ccSugerenciasIA').classList.add('d-none');
        this.classList.add('d-none');
    });

    // Eliminar (desactivar)
    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Desactivar este centro de costo?')) return;
            const id = this.dataset.id;

            ajax('POST', 'bitacora/centros-costo/eliminar/' + id, {})
                .then(function(resp) {
                    if (resp.ok) location.reload();
                    else alert(resp.error || 'Error');
                })
                .catch(function() { alert('Error de conexion'); });
        });
    });
})();
</script>
<?= $this->endSection() ?>
