<?php
/**
 * Partial reusable: modal "Nueva interacción".
 * Variables disponibles:
 *   - $contactos (array, opcional) — para dropdown de contactos
 *
 * Quien lo incluye debe definir en JS la función `crmInteraccionContext()` que
 * devuelva { id_oportunidad: number|null, id_empresa: number|null }.
 */
$contactos = $contactos ?? [];
?>
<div class="modal fade" id="modalInteraccion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formInteraccion">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-chat-dots me-1"></i>Nueva interacción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small">Tipo *</label>
                            <select name="tipo" class="form-select form-select-sm" required>
                                <option value="llamada">Llamada</option>
                                <option value="reunion">Reunión</option>
                                <option value="correo">Correo</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="propuesta_enviada">Propuesta enviada</option>
                                <option value="nota">Nota</option>
                                <option value="tarea">Tarea (pendiente)</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">Estado *</label>
                            <select name="estado" id="iEstado" class="form-select form-select-sm" required>
                                <option value="completada">Completada (ya pasó)</option>
                                <option value="pendiente">Pendiente (programada)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Asunto *</label>
                            <input type="text" name="asunto" class="form-control form-control-sm" required placeholder="Ej: Llamada de seguimiento">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Detalle</label>
                            <textarea name="detalle" class="form-control form-control-sm" rows="2"></textarea>
                        </div>

                        <?php if (!empty($contactos)): ?>
                        <div class="col-md-6">
                            <label class="form-label small">Contacto (opcional)</label>
                            <select name="id_contacto" class="form-select form-select-sm">
                                <option value="">—</option>
                                <?php foreach ($contactos as $c): ?>
                                    <option value="<?= $c['id_contacto'] ?>"><?= esc($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-6" id="grpFechaCompletada">
                            <label class="form-label small">Fecha (cuando ocurrió)</label>
                            <input type="datetime-local" name="fecha_completada" class="form-control form-control-sm">
                        </div>

                        <div class="col-md-6 d-none" id="grpFechaProgramada">
                            <label class="form-label small">Programada para *</label>
                            <input type="datetime-local" name="fecha_programada" class="form-control form-control-sm">
                        </div>

                        <div class="col-md-6 d-none" id="grpRecordatorio">
                            <label class="form-label small">Recordatorio</label>
                            <input type="datetime-local" name="recordatorio_at" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar interacción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('formInteraccion');
    if (!form) return;
    const grpComp = document.getElementById('grpFechaCompletada');
    const grpProg = document.getElementById('grpFechaProgramada');
    const grpRec  = document.getElementById('grpRecordatorio');

    document.getElementById('iEstado').addEventListener('change', function() {
        if (this.value === 'pendiente') {
            grpComp.classList.add('d-none');
            grpProg.classList.remove('d-none');
            grpRec.classList.remove('d-none');
            grpProg.querySelector('input').setAttribute('required', 'required');
        } else {
            grpComp.classList.remove('d-none');
            grpProg.classList.add('d-none');
            grpRec.classList.add('d-none');
            grpProg.querySelector('input').removeAttribute('required');
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const ctx = (typeof crmInteraccionContext === 'function') ? crmInteraccionContext() : {};
        const fd = new FormData(this);
        if (ctx.id_oportunidad) fd.append('id_oportunidad', ctx.id_oportunidad);
        if (ctx.id_empresa)     fd.append('id_empresa', ctx.id_empresa);
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch('<?= base_url('crm/interacciones/agregar') ?>', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(resp => {
                if (resp.ok) location.reload();
                else alert((resp.errors || [resp.error || 'Error']).join('\n'));
            })
            .catch(() => alert('Error de conexión'));
    });
})();
</script>
