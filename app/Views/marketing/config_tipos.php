<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos de acción – Marketing Config</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-tags me-2"></i>Tipos de acción de marketing</h1>
        <div>
            <a href="<?= base_url('marketing/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <button class="btn btn-primary btn-sm" onclick="abrirNuevo()">
                <i class="bi bi-plus-lg me-1"></i> Nuevo tipo
            </button>
        </div>
    </div>
    <p class="text-muted small">Catálogo de tipos de acción que aparecen en el diario (post LinkedIn, evento, llamada, etc.). Edita el color para que se distinga en los gráficos.</p>

    <table class="table table-sm table-striped">
        <thead class="table-dark">
            <tr><th>Nombre</th><th>Color</th><th>Activa</th><th class="text-center">Acciones</th></tr>
        </thead>
        <tbody>
            <?php foreach ($tipos as $t): ?>
            <tr>
                <td><strong><?= esc($t['nombre']) ?></strong></td>
                <td>
                    <span class="d-inline-block" style="width:24px;height:24px;background:<?= esc($t['color']) ?>;border-radius:4px;vertical-align:middle;"></span>
                    <?= esc($t['color']) ?>
                </td>
                <td><?= (int) $t['activa'] === 1 ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick='editar(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminar(<?= $t['id_tipo_accion'] ?>)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalTipo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTipo">
                <div class="modal-header">
                    <h5 class="modal-title">Tipo de acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_tipo_accion" id="idTipo" value="0">
                    <label class="form-label small">Nombre *</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" required>
                    <label class="form-label small mt-2">Color</label>
                    <input type="color" name="color" class="form-control form-control-sm form-control-color" value="#6c757d">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="activa" value="1" class="form-check-input" id="actTipo" checked>
                        <label class="form-check-label small" for="actTipo">Activa</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE='<?= base_url() ?>', CSRF_NAME='<?= csrf_token() ?>', CSRF_HASH='<?= csrf_hash() ?>';
const modal = new bootstrap.Modal(document.getElementById('modalTipo'));
function abrirNuevo(){const f=document.getElementById('formTipo');f.reset();document.getElementById('idTipo').value=0;f.querySelector('[name=activa]').checked=true;f.querySelector('[name=color]').value='#6c757d';modal.show();}
function editar(t){const f=document.getElementById('formTipo');f.reset();document.getElementById('idTipo').value=t.id_tipo_accion;f.querySelector('[name=nombre]').value=t.nombre;f.querySelector('[name=color]').value=t.color;f.querySelector('[name=activa]').checked=parseInt(t.activa,10)===1;modal.show();}
document.getElementById('formTipo').addEventListener('submit',function(e){e.preventDefault();const fd=new FormData(this);fd.append(CSRF_NAME,CSRF_HASH);fetch(BASE+'marketing/config/tipos-accion/guardar',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error||'Error');});});
function eliminar(id){if(!confirm('¿Eliminar este tipo? Solo posible si no tiene acciones.'))return;const fd=new FormData();fd.append(CSRF_NAME,CSRF_HASH);fetch(BASE+'marketing/config/tipos-accion/eliminar/'+id,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error||'Error');});}
</script>
</body>
</html>
