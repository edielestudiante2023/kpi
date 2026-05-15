<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuentes – CRM Config</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-funnel me-2"></i>Fuentes de lead</h1>
        <div>
            <a href="<?= base_url('crm/config/etapas') ?>" class="btn btn-outline-secondary btn-sm">Etapas</a>
            <a href="<?= base_url('crm/config/motivos') ?>" class="btn btn-outline-secondary btn-sm">Motivos</a>
            <button class="btn btn-primary btn-sm" onclick="abrirNueva()">
                <i class="bi bi-plus-lg me-1"></i> Nueva fuente
            </button>
        </div>
    </div>
    <p class="text-muted small">Catálogo de orígenes desde donde llegan los leads (referido, web, LinkedIn, etc.).</p>

    <table class="table table-sm table-striped">
        <thead class="table-dark">
            <tr><th>Nombre</th><th>Activa</th><th class="text-center">Acciones</th></tr>
        </thead>
        <tbody>
            <?php foreach ($fuentes as $f): ?>
            <tr>
                <td><?= esc($f['nombre']) ?></td>
                <td><?= (int) $f['activa'] === 1 ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick='editar(<?= json_encode($f, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminar(<?= $f['id_fuente'] ?>)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalFuente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formFuente">
                <div class="modal-header">
                    <h5 class="modal-title">Fuente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_fuente" id="idFuente" value="0">
                    <label class="form-label small">Nombre *</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" required>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="activa" value="1" class="form-check-input" id="activaFu" checked>
                        <label class="form-check-label small" for="activaFu">Activa</label>
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
const modal = new bootstrap.Modal(document.getElementById('modalFuente'));
function abrirNueva(){const f=document.getElementById('formFuente');f.reset();document.getElementById('idFuente').value=0;f.querySelector('[name=activa]').checked=true;modal.show();}
function editar(x){const f=document.getElementById('formFuente');f.reset();document.getElementById('idFuente').value=x.id_fuente;f.querySelector('[name=nombre]').value=x.nombre;f.querySelector('[name=activa]').checked=parseInt(x.activa,10)===1;modal.show();}
document.getElementById('formFuente').addEventListener('submit',function(e){e.preventDefault();const fd=new FormData(this);fd.append(CSRF_NAME,CSRF_HASH);fetch(BASE+'crm/config/fuentes/guardar',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error||'Error');});});
function eliminar(id){if(!confirm('¿Eliminar esta fuente?'))return;const fd=new FormData();fd.append(CSRF_NAME,CSRF_HASH);fetch(BASE+'crm/config/fuentes/eliminar/'+id,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error||'Error');});}
</script>
</body>
</html>
