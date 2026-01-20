<?php
/**
 * Modal para generar contenido con IA
 *
 * Parámetros:
 * - id: ID único del modal (default: 'aiGeneratorModal')
 * - tipo: 'indicador' o 'actividad'
 * - titulo: Título del modal (opcional)
 *
 * Uso:
 * <?= view('components/ai_generator_modal', ['id' => 'aiModal', 'tipo' => 'indicador']) ?>
 */

$id = $id ?? 'aiGeneratorModal';
$tipo = $tipo ?? 'indicador';
$titulo = $titulo ?? ($tipo === 'indicador' ? 'Crear Indicador con IA' : 'Crear Actividad con IA');
$placeholder = $tipo === 'indicador'
    ? 'Ej: Quiero medir el porcentaje de entregas realizadas a tiempo sobre el total de entregas del mes...'
    : 'Ej: Necesito implementar un sistema de notificaciones por correo para los usuarios...';
$endpoint = $tipo === 'indicador' ? 'ia/generar-indicador' : 'ia/generar-actividad';
$endpointRefinar = $tipo === 'indicador' ? 'ia/refinar-indicador' : 'ia/refinar-actividad';
?>

<!-- Modal Generador IA -->
<div class="modal fade" id="<?= $id ?>" tabindex="-1" aria-labelledby="<?= $id ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white" id="<?= $id ?>Label">
                    <i class="bi bi-stars me-2"></i><?= $titulo ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>Tip:</strong> Describe con detalle lo que quieres <?= $tipo === 'indicador' ? 'medir' : 'hacer' ?>.
                    Mientras más específico seas, mejor será el resultado.
                </div>

                <div class="mb-3">
                    <label for="<?= $id ?>Descripcion" class="form-label fw-semibold">
                        <i class="bi bi-chat-text me-1"></i>
                        <?= $tipo === 'indicador' ? '¿Qué quieres medir?' : '¿Qué actividad necesitas?' ?>
                    </label>
                    <textarea class="form-control" id="<?= $id ?>Descripcion" rows="3"
                              placeholder="<?= $placeholder ?>"></textarea>
                    <div class="form-text">
                        Mínimo 10 caracteres. Sé específico para obtener mejores resultados.
                    </div>
                </div>

                <!-- Área de resultado -->
                <div id="<?= $id ?>Resultado" class="d-none">
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Resultado generado:
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleRefinar_<?= $id ?>()" id="<?= $id ?>BtnToggleRefinar">
                            <i class="bi bi-pencil me-1"></i>Ajustar
                        </button>
                    </div>

                    <div id="<?= $id ?>Preview" class="bg-light rounded p-3 border mb-3">
                        <!-- Se llena dinámicamente -->
                    </div>

                    <!-- Sección de refinamiento -->
                    <div id="<?= $id ?>RefinarSection" class="d-none">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning bg-opacity-25 py-2">
                                <small class="fw-semibold">
                                    <i class="bi bi-chat-dots me-1"></i>
                                    ¿Qué quieres ajustar?
                                </small>
                            </div>
                            <div class="card-body py-2">
                                <div class="mb-2">
                                    <textarea class="form-control form-control-sm" id="<?= $id ?>Ajuste" rows="2"
                                              placeholder="Ej: Cambia la meta a 90%, usa otra fórmula, agrega más variables..."></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-warning" onclick="refinarConIA_<?= $id ?>()">
                                        <i class="bi bi-arrow-repeat me-1"></i>Regenerar con ajustes
                                    </button>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" onclick="ajusteRapido_<?= $id ?>('meta')">
                                            <i class="bi bi-bullseye"></i> Meta
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="ajusteRapido_<?= $id ?>('formula')">
                                            <i class="bi bi-calculator"></i> Fórmula
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="ajusteRapido_<?= $id ?>('nombre')">
                                            <i class="bi bi-tag"></i> Nombre
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div id="<?= $id ?>Loading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary mb-2" role="status">
                        <span class="visually-hidden">Generando...</span>
                    </div>
                    <p class="text-muted mb-0" id="<?= $id ?>LoadingText">La IA está pensando...</p>
                </div>

                <!-- Error -->
                <div id="<?= $id ?>Error" class="alert alert-danger d-none">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="<?= $id ?>ErrorText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="<?= $id ?>BtnGenerar" onclick="generarConIA_<?= $id ?>()">
                    <i class="bi bi-stars me-1"></i>Generar con IA
                </button>
                <button type="button" class="btn btn-success d-none" id="<?= $id ?>BtnAplicar" onclick="aplicarResultado_<?= $id ?>()">
                    <i class="bi bi-check-lg me-1"></i>Aplicar al formulario
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let resultadoIA_<?= $id ?> = null;
let historialContexto_<?= $id ?> = [];

function generarConIA_<?= $id ?>(ajuste = null) {
    let descripcion = document.getElementById('<?= $id ?>Descripcion').value.trim();

    if (descripcion.length < 10 && !ajuste) {
        mostrarError_<?= $id ?>('La descripción debe tener al menos 10 caracteres');
        return;
    }

    // Mostrar loading
    document.getElementById('<?= $id ?>Loading').classList.remove('d-none');
    document.getElementById('<?= $id ?>LoadingText').textContent = ajuste ? 'Ajustando resultado...' : 'La IA está pensando...';
    document.getElementById('<?= $id ?>Resultado').classList.add('d-none');
    document.getElementById('<?= $id ?>Error').classList.add('d-none');
    document.getElementById('<?= $id ?>BtnGenerar').disabled = true;
    document.getElementById('<?= $id ?>BtnAplicar').classList.add('d-none');

    // Construir cuerpo de la petición
    let body = 'descripcion=' + encodeURIComponent(descripcion);

    // Si hay ajuste, agregar contexto previo
    if (ajuste && resultadoIA_<?= $id ?>) {
        body += '&ajuste=' + encodeURIComponent(ajuste);
        body += '&contexto_previo=' + encodeURIComponent(JSON.stringify(resultadoIA_<?= $id ?>));
    }

    fetch('<?= base_url($endpoint) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: body
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('<?= $id ?>Loading').classList.add('d-none');
        document.getElementById('<?= $id ?>BtnGenerar').disabled = false;

        if (data.success) {
            resultadoIA_<?= $id ?> = data.data;
            mostrarResultado_<?= $id ?>(data.data);
            document.getElementById('<?= $id ?>Resultado').classList.remove('d-none');
            document.getElementById('<?= $id ?>BtnAplicar').classList.remove('d-none');

            // Limpiar campo de ajuste
            document.getElementById('<?= $id ?>Ajuste').value = '';
        } else {
            mostrarError_<?= $id ?>(data.error || 'Error al generar');
        }
    })
    .catch(error => {
        document.getElementById('<?= $id ?>Loading').classList.add('d-none');
        document.getElementById('<?= $id ?>BtnGenerar').disabled = false;
        mostrarError_<?= $id ?>('Error de conexión: ' + error.message);
    });
}

function refinarConIA_<?= $id ?>() {
    const ajuste = document.getElementById('<?= $id ?>Ajuste').value.trim();
    if (ajuste.length < 5) {
        alert('Describe qué quieres ajustar (mínimo 5 caracteres)');
        return;
    }
    generarConIA_<?= $id ?>(ajuste);
}

function ajusteRapido_<?= $id ?>(tipo) {
    const input = document.getElementById('<?= $id ?>Ajuste');
    const sugerencias = {
        'meta': 'Cambia la meta a ',
        'formula': 'Usa una fórmula diferente: ',
        'nombre': 'Cambia el nombre a algo más '
    };
    input.value = sugerencias[tipo] || '';
    input.focus();
}

function toggleRefinar_<?= $id ?>() {
    const section = document.getElementById('<?= $id ?>RefinarSection');
    const btn = document.getElementById('<?= $id ?>BtnToggleRefinar');
    if (section.classList.contains('d-none')) {
        section.classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-x me-1"></i>Cerrar ajustes';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-outline-warning');
    } else {
        section.classList.add('d-none');
        btn.innerHTML = '<i class="bi bi-pencil me-1"></i>Ajustar';
        btn.classList.add('btn-outline-secondary');
        btn.classList.remove('btn-outline-warning');
    }
}

function mostrarResultado_<?= $id ?>(data) {
    const preview = document.getElementById('<?= $id ?>Preview');
    <?php if ($tipo === 'indicador'): ?>
    preview.innerHTML = `
        <div class="row">
            <div class="col-md-6 mb-2">
                <strong>Nombre:</strong><br>
                <span class="text-primary">${data.nombre || '-'}</span>
            </div>
            <div class="col-md-3 mb-2">
                <strong>Meta:</strong><br>
                <span class="badge bg-success fs-6">${data.meta || '-'} ${data.unidad_medida || ''}</span>
            </div>
            <div class="col-md-3 mb-2">
                <strong>Frecuencia:</strong><br>
                <span class="badge bg-info">${data.frecuencia_sugerida || '-'}</span>
            </div>
        </div>
        <div class="mb-2">
            <strong>Descripción:</strong><br>
            <small class="text-muted">${data.descripcion || '-'}</small>
        </div>
        <div class="mb-2">
            <strong>Fórmula:</strong><br>
            <code class="bg-dark text-light px-2 py-1 rounded">${data.formula_legible || '-'}</code>
        </div>
        ${data.variables_necesarias ? `
        <div>
            <strong>Variables a ingresar:</strong><br>
            ${data.variables_necesarias.map(v => `<span class="badge bg-secondary me-1">${v}</span>`).join('')}
        </div>
        ` : ''}
    `;
    <?php else: ?>
    preview.innerHTML = `
        <div class="row">
            <div class="col-md-8 mb-2">
                <strong>Título:</strong><br>
                <span class="text-primary">${data.titulo || '-'}</span>
            </div>
            <div class="col-md-4 mb-2">
                <strong>Prioridad:</strong><br>
                <span class="badge bg-${data.prioridad === 'alta' ? 'danger' : (data.prioridad === 'media' ? 'warning text-dark' : 'secondary')}">${data.prioridad || '-'}</span>
            </div>
        </div>
        <div class="mb-2">
            <strong>Descripción:</strong><br>
            <small class="text-muted">${data.descripcion || '-'}</small>
        </div>
        ${data.pasos_sugeridos ? `
        <div class="mb-2">
            <strong>Pasos sugeridos:</strong>
            <ol class="small mb-0 mt-1">
                ${data.pasos_sugeridos.map(p => `<li>${p}</li>`).join('')}
            </ol>
        </div>
        ` : ''}
        <div class="row">
            <div class="col-md-6">
                <strong>Duración estimada:</strong> ${data.duracion_estimada_dias || '-'} días
            </div>
            <div class="col-md-6">
                <strong>Categoría:</strong> ${data.categoria_sugerida || '-'}
            </div>
        </div>
    `;
    <?php endif; ?>
}

function mostrarError_<?= $id ?>(mensaje) {
    document.getElementById('<?= $id ?>Error').classList.remove('d-none');
    document.getElementById('<?= $id ?>ErrorText').textContent = mensaje;
}

function aplicarResultado_<?= $id ?>() {
    if (!resultadoIA_<?= $id ?>) return;

    <?php if ($tipo === 'indicador'): ?>
    // Llenar campos del formulario de indicador
    if (document.getElementById('nombre_indicador')) {
        document.getElementById('nombre_indicador').value = resultadoIA_<?= $id ?>.nombre || '';
    }
    if (document.getElementById('descripcion')) {
        document.getElementById('descripcion').value = resultadoIA_<?= $id ?>.descripcion || '';
    }
    if (document.getElementById('meta')) {
        document.getElementById('meta').value = resultadoIA_<?= $id ?>.meta || '';
    }
    if (document.getElementById('unidad_medida')) {
        document.getElementById('unidad_medida').value = resultadoIA_<?= $id ?>.unidad_medida || '';
    }

    // Guardar partes de fórmula para uso posterior
    if (resultadoIA_<?= $id ?>.partes_formula) {
        window.partesFormulaIA = resultadoIA_<?= $id ?>.partes_formula;
        console.log('Partes de fórmula guardadas:', window.partesFormulaIA);
    }
    <?php else: ?>
    // Llenar campos del formulario de actividad
    if (document.getElementById('titulo')) {
        document.getElementById('titulo').value = resultadoIA_<?= $id ?>.titulo || '';
    }
    if (document.getElementById('descripcion')) {
        document.getElementById('descripcion').value = resultadoIA_<?= $id ?>.descripcion || '';
        // Si hay pasos sugeridos, agregarlos a la descripción
        if (resultadoIA_<?= $id ?>.pasos_sugeridos && resultadoIA_<?= $id ?>.pasos_sugeridos.length > 0) {
            let desc = resultadoIA_<?= $id ?>.descripcion + '\n\nPasos sugeridos:\n';
            resultadoIA_<?= $id ?>.pasos_sugeridos.forEach((paso, i) => {
                desc += (i + 1) + '. ' + paso + '\n';
            });
            document.getElementById('descripcion').value = desc;
        }
    }
    if (document.getElementById('prioridad')) {
        document.getElementById('prioridad').value = resultadoIA_<?= $id ?>.prioridad || 'media';
    }
    // Fecha límite basada en duración estimada
    if (document.getElementById('fecha_limite') && resultadoIA_<?= $id ?>.duracion_estimada_dias) {
        const hoy = new Date();
        hoy.setDate(hoy.getDate() + resultadoIA_<?= $id ?>.duracion_estimada_dias);
        document.getElementById('fecha_limite').value = hoy.toISOString().split('T')[0];
    }
    <?php endif; ?>

    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('<?= $id ?>'));
    if (modal) modal.hide();

    // Notificación de éxito
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Aplicado',
            text: 'Los datos se han copiado al formulario',
            timer: 2000,
            showConfirmButton: false
        });
    } else {
        alert('Datos aplicados al formulario');
    }
}

// Limpiar al cerrar modal
document.getElementById('<?= $id ?>').addEventListener('hidden.bs.modal', function() {
    document.getElementById('<?= $id ?>Descripcion').value = '';
    document.getElementById('<?= $id ?>Resultado').classList.add('d-none');
    document.getElementById('<?= $id ?>Error').classList.add('d-none');
    document.getElementById('<?= $id ?>BtnAplicar').classList.add('d-none');
    document.getElementById('<?= $id ?>RefinarSection').classList.add('d-none');
    document.getElementById('<?= $id ?>BtnToggleRefinar').innerHTML = '<i class="bi bi-pencil me-1"></i>Ajustar';
    document.getElementById('<?= $id ?>BtnToggleRefinar').classList.add('btn-outline-secondary');
    document.getElementById('<?= $id ?>BtnToggleRefinar').classList.remove('btn-outline-warning');
    resultadoIA_<?= $id ?> = null;
});
</script>
