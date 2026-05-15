<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motivos de pérdida – CRM Config</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="bi bi-x-circle me-2"></i>Motivos de pérdida</h1>
        <div>
            <a href="<?= base_url('crm/config/etapas') ?>" class="btn btn-outline-secondary btn-sm">Etapas</a>
            <a href="<?= base_url('crm/config/fuentes') ?>" class="btn btn-outline-secondary btn-sm">Fuentes</a>
            <button class="btn btn-primary btn-sm" onclick="abrirNuevo()">
                <i class="bi bi-plus-lg me-1"></i> Nuevo motivo
            </button>
        </div>
    </div>
    <p class="text-muted small">Razones por las que se pierde una oportunidad (precio, timing, competencia, etc.). Útiles para análisis posterior.</p>

    <table class="table table-sm table-striped">
        <thead class="table-dark">
            <tr><th>Nombre</th><th>Activo</th><th class="text-center">Acciones</th></tr>
        </thead>
        <tbody>
            <?php foreach ($motivos as $m): ?>
            <tr>
                <td><?= esc($m['nombre']) ?></td>
                <td><?= (int) $m['activa'] === 1 ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick='editar(<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminar(<?= $m['id_motivo_perdida'] ?>)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalMotivo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formMotivo">
                <div class="modal-header">
                    <h5 class="modal-title">Motivo de pérdida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_motivo_perdida" id="idMotivo" value="0">
                    <label class="form-label small">Nombre *</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" required>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="activa" value="1" class="form-check-input" id="activaMo" checked>
                        <label class="form-check-label small" for="activaMo">Activo</label>
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
const modal = new bootstrap.Modal(document.getElementById('modalMotivo'));
function abrirNuevo(){const f=document.getElementById('formMotivo');f.reset();document.getElementById('idMotivo').value=0;f.querySelector('[name=activa]').checked=true;modal.show();}
function editar(x){const f=document.getElementById('formMotivo');f.reset();document.getElementById('idMotivo').value=x.id_motivo_perdida;f.querySelector('[name=nombre]').value=x.nombre;f.querySelector('[name=activa]').checked=parseInt(x.activa,10)===1;modal.show();}
document.getElementById('formMotivo').addEventListener('submit',function(e){e.preventDefault();const fd=new FormData(this);fd.append(CSRF_NAME,CSRF_HASH);fetch(BASE+'crm/config/motivos/guardar',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error||'Error');});});
function eliminar(id){if(!confirm('¿Eliminar este motivo?'))return;const fd=new FormData();fd.append(CSRF_NAME,CSRF_HASH);fetch(BASE+'crm/config/motivos/eliminar/'+id,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error||'Error');});}
</script>
</body>
</html>
