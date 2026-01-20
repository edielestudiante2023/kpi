<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Actividad - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        /* Toast personalizado */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast {
            min-width: 300px;
        }
        .detail-card {
            max-width: 1400px;
            margin: 0 auto;
        }
        .codigo-actividad {
            font-family: monospace;
            background: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .badge-estado {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        .estado-pendiente { background-color: #6c757d; }
        .estado-en_progreso { background-color: #0d6efd; }
        .estado-en_revision { background-color: #6f42c1; }
        .estado-completada { background-color: #198754; }
        .estado-cancelada { background-color: #dc3545; }

        .badge-prioridad { padding: 0.3rem 0.6rem; }
        .prioridad-urgente { background-color: #dc3545; }
        .prioridad-alta { background-color: #fd7e14; }
        .prioridad-media { background-color: #ffc107; color: #212529; }
        .prioridad-baja { background-color: #198754; }

        .info-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 1rem;
            color: #212529;
        }
        .comentario-item {
            border-left: 3px solid #0d6efd;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .comentario-item.interno {
            border-left-color: #6f42c1;
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0 4px 4px 0;
        }
        .comentario-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .historial-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            font-size: 0.85rem;
        }
        .historial-item:last-child {
            border-bottom: none;
        }
        .avatar-md {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        /* Archivos - evitar desbordamiento */
        .archivo-item {
            overflow: hidden;
        }
        .archivo-item .text-truncate {
            max-width: 100%;
            overflow: hidden;
        }
        .archivo-item a {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 150px;
        }
        .min-width-0 {
            min-width: 0;
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <div class="detail-card">
            <!-- Usuario en sesion -->
            <div class="text-end mb-2">
                <span class="badge bg-primary fs-6">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= esc(session()->get('nombre_completo')) ?>
                </span>
            </div>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-2">
                    <?= view('components/back_to_dashboard') ?>
                    <div>
                        <div class="mb-2">
                            <span class="codigo-actividad"><?= esc($actividad['codigo']) ?></span>
                            <span class="badge badge-estado estado-<?= $actividad['estado'] ?> ms-2">
                                <?= ucfirst(str_replace('_', ' ', $actividad['estado'])) ?>
                            </span>
                            <span class="badge badge-prioridad prioridad-<?= $actividad['prioridad'] ?> ms-1">
                                <?= ucfirst($actividad['prioridad']) ?>
                            </span>
                        </div>
                        <h1 class="h3 mb-0"><?= esc($actividad['titulo']) ?></h1>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('actividades/tablero') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </a>
                    <?php
                    // Solo puede editar: el creador o superadmin (rol_id = 1)
                    $puedeEditar = (session()->get('id_users') == $actividad['id_creador'])
                                   || (session()->get('rol_id') == 1);
                    ?>
                    <?php if ($puedeEditar): ?>
                        <a href="<?= base_url('actividades/editar/' . $actividad['id_actividad']) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php
            // El asignado, creador o superadmin pueden cambiar el estado
            $puedeCambiarEstado = (session()->get('id_users') == $actividad['id_asignado'])
                                  || (session()->get('id_users') == $actividad['id_creador'])
                                  || (session()->get('rol_id') == 1);
            $estadoActual = $actividad['estado'];
            ?>

            <?php if ($puedeCambiarEstado && !in_array($estadoActual, ['completada', 'cancelada'])): ?>
            <!-- Botones de cambio rapido de estado -->
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="text-muted small">
                            <i class="bi bi-arrow-right-circle me-1"></i>Cambiar estado a:
                        </span>
                        <div class="btn-group btn-group-sm" role="group">
                            <?php if ($estadoActual !== 'pendiente'): ?>
                                <button type="button" class="btn btn-outline-secondary btn-cambiar-estado" data-estado="pendiente">
                                    <i class="bi bi-clock me-1"></i>Pendiente
                                </button>
                            <?php endif; ?>
                            <?php if ($estadoActual !== 'en_progreso'): ?>
                                <button type="button" class="btn btn-outline-primary btn-cambiar-estado" data-estado="en_progreso">
                                    <i class="bi bi-play-circle me-1"></i>En Progreso
                                </button>
                            <?php endif; ?>
                            <?php if ($estadoActual !== 'en_revision'): ?>
                                <button type="button" class="btn btn-outline-purple btn-cambiar-estado" data-estado="en_revision" style="border-color: #6f42c1; color: #6f42c1;">
                                    <i class="bi bi-eye me-1"></i>En Revision
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-success btn-cambiar-estado" data-estado="completada">
                                <i class="bi bi-check-circle me-1"></i>Completada
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Columna principal -->
                <div class="col-lg-8">
                    <!-- Descripcion -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-text-paragraph me-2"></i>Descripcion</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($actividad['descripcion'])): ?>
                                <p class="mb-0"><?= nl2br(esc($actividad['descripcion'])) ?></p>
                            <?php else: ?>
                                <p class="text-muted mb-0">Sin descripcion</p>
                            <?php endif; ?>

                            <?php if (!empty($actividad['observaciones'])): ?>
                                <hr>
                                <div class="info-label mb-1">Observaciones</div>
                                <p class="mb-0"><?= nl2br(esc($actividad['observaciones'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Comentarios -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comentarios (<?= count($comentarios) ?>)</h6>
                        </div>
                        <div class="card-body">
                            <!-- Lista de comentarios -->
                            <div id="lista-comentarios">
                                <?php if (empty($comentarios)): ?>
                                    <p class="text-muted text-center py-3" id="sin-comentarios">No hay comentarios aun</p>
                                <?php else: ?>
                                    <?php foreach ($comentarios as $com): ?>
                                        <div class="comentario-item <?= $com['es_interno'] ? 'interno' : '' ?>">
                                            <div class="comentario-meta mb-1">
                                                <strong><?= esc($com['nombre_completo']) ?></strong>
                                                <?php if ($com['es_interno']): ?>
                                                    <span class="badge bg-secondary ms-1">Interno</span>
                                                <?php endif; ?>
                                                <span class="float-end"><?= date('d/m/Y H:i', strtotime($com['created_at'])) ?></span>
                                            </div>
                                            <p class="mb-0"><?= nl2br(esc($com['comentario'])) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <hr>

                            <!-- Formulario nuevo comentario -->
                            <form id="form-comentario">
                                <input type="hidden" name="id_actividad" value="<?= $actividad['id_actividad'] ?>">
                                <div class="mb-2">
                                    <textarea name="comentario" class="form-control" rows="2"
                                              placeholder="Escribe un comentario..." required></textarea>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input type="checkbox" name="es_interno" class="form-check-input" id="esInterno">
                                        <label class="form-check-label small" for="esInterno">
                                            Comentario interno (solo visible para admins)
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-send me-1"></i> Enviar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Historial -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de cambios</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($historial)): ?>
                                <p class="text-muted text-center py-3">Sin historial de cambios</p>
                            <?php else: ?>
                                <?php foreach ($historial as $h): ?>
                                    <div class="historial-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?= esc($h['nombre_completo']) ?></strong>
                                                cambio <code><?= esc($h['campo']) ?></code>
                                                <?php if ($h['valor_anterior']): ?>
                                                    de <span class="text-danger"><?= esc($h['valor_anterior']) ?></span>
                                                <?php endif; ?>
                                                a <span class="text-success"><?= esc($h['valor_nuevo']) ?></span>
                                            </div>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral -->
                <div class="col-lg-4">
                    <!-- Informacion -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informacion</h6>
                        </div>
                        <div class="card-body">
                            <!-- Responsable -->
                            <div class="mb-3">
                                <div class="info-label">Asignado a</div>
                                <?php if (!empty($actividad['nombre_asignado'])): ?>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="avatar-md">
                                            <?= strtoupper(substr($actividad['nombre_asignado'], 0, 2)) ?>
                                        </span>
                                        <span class="info-value"><?= esc($actividad['nombre_asignado']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="info-value text-muted">Sin asignar</div>
                                <?php endif; ?>
                            </div>

                            <!-- Creador -->
                            <div class="mb-3">
                                <div class="info-label">Creado por</div>
                                <div class="info-value"><?= esc($actividad['nombre_creador']) ?></div>
                            </div>

                            <!-- Categoria -->
                            <?php if (!empty($actividad['nombre_categoria'])): ?>
                                <div class="mb-3">
                                    <div class="info-label">Categoria</div>
                                    <span class="badge" style="background-color: <?= $actividad['color_categoria'] ?>;">
                                        <?= esc($actividad['nombre_categoria']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Area -->
                            <?php if (!empty($actividad['nombre_area'])): ?>
                                <div class="mb-3">
                                    <div class="info-label">Area</div>
                                    <div class="info-value"><?= esc($actividad['nombre_area']) ?></div>
                                </div>
                            <?php endif; ?>

                            <hr>

                            <!-- Fechas -->
                            <div class="mb-3">
                                <div class="info-label">Fecha de creacion</div>
                                <div class="info-value">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($actividad['fecha_creacion'])) ?>
                                </div>
                            </div>

                            <?php if (!empty($actividad['fecha_limite'])): ?>
                                <div class="mb-3">
                                    <div class="info-label">Fecha limite</div>
                                    <?php
                                    $dias = $actividad['dias_restantes'] ?? 0;
                                    $claseVencimiento = '';
                                    if ($dias < 0 && !in_array($actividad['estado'], ['completada', 'cancelada'])) {
                                        $claseVencimiento = 'text-danger';
                                    } elseif ($dias <= 2 && $dias >= 0) {
                                        $claseVencimiento = 'text-warning';
                                    }
                                    ?>
                                    <div class="info-value <?= $claseVencimiento ?>">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?= date('d/m/Y', strtotime($actividad['fecha_limite'])) ?>
                                        <?php if ($dias < 0 && !in_array($actividad['estado'], ['completada', 'cancelada'])): ?>
                                            <span class="badge bg-danger ms-1">Vencida</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($actividad['fecha_inicio'])): ?>
                                <div class="mb-3">
                                    <div class="info-label">Fecha de inicio</div>
                                    <div class="info-value">
                                        <?= date('d/m/Y H:i', strtotime($actividad['fecha_inicio'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($actividad['fecha_cierre'])): ?>
                                <div class="mb-3">
                                    <div class="info-label">Fecha de cierre</div>
                                    <div class="info-value">
                                        <?= date('d/m/Y H:i', strtotime($actividad['fecha_cierre'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Progreso -->
                            <?php if ($actividad['porcentaje_avance'] > 0): ?>
                                <hr>
                                <div class="mb-2">
                                    <div class="info-label">Progreso</div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= $actividad['porcentaje_avance'] ?>%"></div>
                                        </div>
                                        <span class="info-value"><?= $actividad['porcentaje_avance'] ?>%</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Archivos -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>Archivos (<span id="contador-archivos"><?= count($archivos) ?></span>)</h6>
                        </div>
                        <div class="card-body">
                            <!-- Lista de archivos -->
                            <div id="lista-archivos">
                                <?php if (empty($archivos)): ?>
                                    <p class="text-muted text-center small" id="sin-archivos">Sin archivos adjuntos</p>
                                <?php else: ?>
                                    <?php foreach ($archivos as $arch): ?>
                                        <div class="archivo-item d-flex align-items-center justify-content-between mb-2 p-2 bg-light rounded" data-id="<?= $arch['id_archivo'] ?>">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1 min-width-0">
                                                <i class="bi bi-file-earmark-<?= $arch['tipo_mime'] === 'application/pdf' ? 'pdf text-danger' : (strpos($arch['tipo_mime'], 'image') !== false ? 'image text-primary' : 'text') ?>"></i>
                                                <div class="text-truncate">
                                                    <a href="<?= base_url('actividades/archivo/descargar/' . $arch['id_archivo']) ?>" class="text-decoration-none">
                                                        <?= esc($arch['nombre_original']) ?>
                                                    </a>
                                                    <div class="small text-muted">
                                                        <?= number_format($arch['tamanio'] / 1024, 1) ?> KB - <?= esc($arch['nombre_completo']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-archivo" data-id="<?= $arch['id_archivo'] ?>" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <hr>

                            <!-- Formulario subir archivo -->
                            <form id="form-archivo" enctype="multipart/form-data">
                                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                                <div class="mb-2">
                                    <input type="file" name="archivo" id="input-archivo" class="form-control form-control-sm" required>
                                    <div class="form-text">Max 10MB. PDF, DOC, XLS, IMG, ZIP</div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100" id="btn-subir">
                                    <i class="bi bi-upload me-1"></i> Subir Archivo
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div class="toast-container">
        <div id="toastNotificacion" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="20000">
            <div class="toast-header">
                <i class="bi bi-info-circle me-2" id="toast-icon"></i>
                <strong class="me-auto" id="toast-titulo">Notificacion</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toast-mensaje"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const csrfName = '<?= csrf_token() ?>';
        const csrfHash = '<?= csrf_hash() ?>';

        // Comentarios
        $('#form-comentario').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const formData = new FormData(form[0]);
            formData.append(csrfName, csrfHash);

            $.ajax({
                url: '<?= base_url('actividades/comentario') ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        const esInterno = form.find('[name="es_interno"]').is(':checked');
                        const html = `
                            <div class="comentario-item ${esInterno ? 'interno' : ''}">
                                <div class="comentario-meta mb-1">
                                    <strong>${res.usuario}</strong>
                                    ${esInterno ? '<span class="badge bg-secondary ms-1">Interno</span>' : ''}
                                    <span class="float-end">${res.fecha}</span>
                                </div>
                                <p class="mb-0">${form.find('[name="comentario"]').val()}</p>
                            </div>
                        `;

                        $('#sin-comentarios').remove();
                        $('#lista-comentarios').append(html);
                        form[0].reset();
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Error al enviar el comentario');
                }
            });
        });

        // Subir archivo
        $('#form-archivo').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const formData = new FormData(form[0]);
            const btnSubir = $('#btn-subir');

            btnSubir.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Subiendo...');

            $.ajax({
                url: '<?= base_url('actividades/archivo/subir/' . $actividad['id_actividad']) ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#sin-archivos').remove();
                        const html = `
                            <div class="archivo-item d-flex align-items-center justify-content-between mb-2 p-2 bg-light rounded" data-id="${res.archivo.id}">
                                <div class="d-flex align-items-center gap-2 flex-grow-1 min-width-0">
                                    <i class="bi bi-file-earmark"></i>
                                    <div class="text-truncate">
                                        <a href="<?= base_url('actividades/archivo/descargar/') ?>${res.archivo.id}" class="text-decoration-none">
                                            ${res.archivo.nombre}
                                        </a>
                                        <div class="small text-muted">${res.archivo.tamanio}</div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-archivo" data-id="${res.archivo.id}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                        $('#lista-archivos').append(html);

                        // Actualizar contador
                        const contador = parseInt($('#contador-archivos').text()) + 1;
                        $('#contador-archivos').text(contador);

                        form[0].reset();
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Error al subir el archivo');
                },
                complete: function() {
                    btnSubir.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Subir Archivo');
                }
            });
        });

        // Eliminar archivo
        $(document).on('click', '.btn-eliminar-archivo', function() {
            if (!confirm('¿Eliminar este archivo?')) return;

            const btn = $(this);
            const idArchivo = btn.data('id');
            const item = btn.closest('.archivo-item');

            $.ajax({
                url: '<?= base_url('actividades/archivo/eliminar/') ?>' + idArchivo,
                method: 'POST',
                data: { [csrfName]: csrfHash },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        item.fadeOut(300, function() {
                            $(this).remove();
                            // Actualizar contador
                            const contador = parseInt($('#contador-archivos').text()) - 1;
                            $('#contador-archivos').text(contador);

                            if (contador === 0) {
                                $('#lista-archivos').html('<p class="text-muted text-center small" id="sin-archivos">Sin archivos adjuntos</p>');
                            }
                        });
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Error al eliminar el archivo');
                }
            });
        });

        // Funcion para mostrar toast
        function mostrarToast(mensaje, tipo = 'success') {
            const toast = $('#toastNotificacion');
            const icon = $('#toast-icon');
            const titulo = $('#toast-titulo');

            // Configurar apariencia segun tipo
            toast.removeClass('bg-success bg-danger bg-warning text-white');
            icon.removeClass('text-success text-danger text-warning');

            if (tipo === 'success') {
                toast.addClass('bg-success text-white');
                icon.attr('class', 'bi bi-check-circle-fill me-2 text-white');
                titulo.text('Exito');
            } else if (tipo === 'error') {
                toast.addClass('bg-danger text-white');
                icon.attr('class', 'bi bi-exclamation-circle-fill me-2 text-white');
                titulo.text('Error');
            } else {
                toast.addClass('bg-warning');
                icon.attr('class', 'bi bi-exclamation-triangle-fill me-2');
                titulo.text('Aviso');
            }

            $('#toast-mensaje').text(mensaje);
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
        }

        // Cambio rapido de estado
        $(document).on('click', '.btn-cambiar-estado', function() {
            const btn = $(this);
            const nuevoEstado = btn.data('estado');
            const idActividad = <?= $actividad['id_actividad'] ?>;

            // Nombres legibles para los estados
            const nombresEstados = {
                'pendiente': 'Pendiente',
                'en_progreso': 'En Progreso',
                'en_revision': 'En Revision',
                'completada': 'Completada',
                'cancelada': 'Cancelada'
            };

            // Deshabilitar todos los botones mientras se procesa
            $('.btn-cambiar-estado').prop('disabled', true);
            btn.html('<i class="bi bi-hourglass-split me-1"></i>Cambiando...');

            $.ajax({
                url: '<?= base_url('actividades/cambiar-estado') ?>',
                method: 'POST',
                data: {
                    id_actividad: idActividad,
                    estado: nuevoEstado,
                    [csrfName]: csrfHash
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        mostrarToast('Estado cambiado a "' + nombresEstados[nuevoEstado] + '" correctamente', 'success');
                        // Recargar la pagina despues de 1.5 segundos para actualizar la vista
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        mostrarToast(res.message || 'Error al cambiar el estado', 'error');
                        $('.btn-cambiar-estado').prop('disabled', false);
                        // Restaurar texto del boton
                        restaurarBotones();
                    }
                },
                error: function(xhr, status, error) {
                    mostrarToast('Error de conexion: ' + error, 'error');
                    $('.btn-cambiar-estado').prop('disabled', false);
                    restaurarBotones();
                }
            });
        });

        function restaurarBotones() {
            $('.btn-cambiar-estado').each(function() {
                const btn = $(this);
                const estado = btn.data('estado');
                const iconos = {
                    'pendiente': 'clock',
                    'en_progreso': 'play-circle',
                    'en_revision': 'eye',
                    'completada': 'check-circle'
                };
                const nombres = {
                    'pendiente': 'Pendiente',
                    'en_progreso': 'En Progreso',
                    'en_revision': 'En Revision',
                    'completada': 'Completada'
                };
                btn.html('<i class="bi bi-' + iconos[estado] + ' me-1"></i>' + nombres[estado]);
            });
        }
    </script>
</body>
</html>
