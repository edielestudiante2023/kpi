<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<?php
$hoy = date('Y-m-d');
$tieneActiva = !empty($actividadActiva);
?>

<!-- Formulario nueva actividad o cronómetro activo -->
<div class="mb-3">

    <?php if ($tieneActiva): ?>
        <!-- CRONÓMETRO ACTIVO -->
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">
                    Actividad <?= $actividadActiva['numero_actividad'] ?> en progreso
                </div>
                <div class="fw-bold mb-2"><?= esc($actividadActiva['descripcion']) ?></div>
                <div class="text-muted small mb-1">
                    <i class="bi bi-building"></i> <?= esc($actividadActiva['centro_costo_nombre'] ?? '') ?>
                </div>
                <div class="text-muted small mb-2">
                    <i class="bi bi-person-badge"></i> <?= esc($actividadActiva['cliente'] ?? 'FRAMEWORK') ?>
                </div>

                <button class="btn btn-sm w-100 mb-3" style="background-color:#6f42c1;color:#fff;" id="btnDescartar"
                        data-id="<?= $actividadActiva['id_bitacora'] ?>">
                    <i class="bi bi-trash me-1"></i> ¿Olvidaste detener el tiempo?
                </button>

                <div class="cronometro-display running" id="cronometro">00:00:00</div>

                <div class="text-muted small mt-1">
                    Inicio: <?= date('h:i A', strtotime($actividadActiva['hora_inicio'])) ?>
                </div>

                <button class="btn btn-danger btn-lg w-100 mt-3" id="btnTerminar"
                        data-id="<?= $actividadActiva['id_bitacora'] ?>">
                    <i class="bi bi-stop-circle me-1"></i> Terminar Actividad
                </button>
            </div>
        </div>

    <?php else: ?>
        <!-- FORMULARIO NUEVA ACTIVIDAD -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-plus-circle text-primary me-1"></i>
                    Nueva Actividad
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Descripción</label>
                    <textarea class="form-control" id="txtDescripcion" rows="2"
                              placeholder="¿Qué vas a trabajar?" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Centro de Costo</label>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-grow-1">
                            <select class="form-select" id="selCentroCosto" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($centrosCosto as $cc): ?>
                                    <option value="<?= $cc['id_centro_costo'] ?>"><?= esc($cc['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoCC" title="Nuevo centro de costo">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- ===== Cliente ===== -->
                <div class="mb-3">
                    <label class="form-label small fw-bold">Cliente</label>
                    <div class="text-muted small mb-2" style="font-size:0.75rem;">
                        Indique el cliente para el cual se está trabajando.
                        Si es una actividad <strong>transversal</strong>, seleccione FRAMEWORK.
                    </div>

                    <div class="btn-group btn-group-sm w-100 mb-2" role="group" aria-label="Tipo cliente">
                        <input type="radio" class="btn-check" name="tipoCliente" id="tipoFramework" value="framework" checked>
                        <label class="btn btn-outline-secondary" for="tipoFramework">
                            <i class="bi bi-diagram-3"></i> FRAMEWORK
                        </label>

                        <input type="radio" class="btn-check" name="tipoCliente" id="tipoActivo" value="activo">
                        <label class="btn btn-outline-success" for="tipoActivo">
                            <i class="bi bi-building-check"></i> Activo
                        </label>

                        <input type="radio" class="btn-check" name="tipoCliente" id="tipoProspecto" value="prospecto">
                        <label class="btn btn-outline-warning" for="tipoProspecto">
                            <i class="bi bi-search"></i> Prospecto
                        </label>
                    </div>

                    <!-- Modo Activo: origen + select remoto -->
                    <div id="bloqueClienteActivo" class="d-none">
                        <div class="d-flex gap-2 mb-2">
                            <select class="form-select form-select-sm" id="selOrigenCliente" style="max-width: 165px;">
                                <option value="PH">Propiedad Horizontal</option>
                                <option value="SST">Empresas SST</option>
                            </select>
                            <select class="form-select form-select-sm flex-grow-1" id="selClienteActivo">
                                <option value="">Buscar cliente...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Modo Prospecto: input texto libre -->
                    <div id="bloqueClienteProspecto" class="d-none">
                        <input type="text" class="form-control form-control-sm" id="txtClienteProspecto"
                               placeholder="Nombre del cliente prospecto" maxlength="150">
                    </div>
                </div>

                <button class="btn btn-success btn-lg w-100" id="btnIniciar">
                    <i class="bi bi-play-circle me-1"></i> Iniciar
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Total del día -->
<div class="total-horas mb-3">
    <i class="bi bi-clock me-1"></i>
    Total hoy: <strong id="totalHoras"><?= formatMinutosHoras($totalMinutos) ?></strong>
</div>

<!-- Tablero: ¿En qué está trabajando el equipo? -->
<div class="card shadow-sm mb-3" id="cardEquipoProgreso">
    <div class="card-body py-2">
        <h6 class="card-title mb-2 d-flex align-items-center gap-2">
            <i class="bi bi-people-fill text-success"></i>
            <span>Equipo trabajando ahora</span>
            <span class="badge bg-success rounded-pill ms-auto" id="conteoEquipo">0</span>
        </h6>
        <div id="listaEquipoProgreso">
            <div class="text-center text-muted small py-2">
                <span class="spinner-border spinner-border-sm me-1"></span> Cargando...
            </div>
        </div>
    </div>
</div>

<!-- Lista de actividades del día -->
<h6 class="text-muted mb-2">
    <i class="bi bi-list-check me-1"></i>
    Actividades de hoy (<?= date('d/m/Y') ?>)
</h6>

<div id="listaActividades">
    <?php if (empty($actividadesHoy)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Aún no has registrado actividades hoy
        </div>
    <?php else: ?>
        <?php foreach ($actividadesHoy as $act): ?>
            <div class="actividad-card <?= $act['estado'] ?>">
                <div class="d-flex align-items-start gap-2">
                    <span class="num"><?= $act['numero_actividad'] ?></span>
                    <div class="flex-grow-1">
                        <div class="fw-bold small"><?= esc($act['descripcion']) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-person-badge"></i> <?= esc($act['cliente'] ?? 'FRAMEWORK') ?>
                        </div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-building"></i> <?= esc($act['centro_costo_nombre'] ?? '') ?>
                        </div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <?= date('h:i A', strtotime($act['hora_inicio'])) ?>
                            <?php if ($act['hora_fin']): ?>
                                — <?= date('h:i A', strtotime($act['hora_fin'])) ?>
                                <span class="badge bg-secondary ms-1">
                                    <?= formatMinutosHoras((float)$act['duracion_minutos']) ?>
                                </span>
                            <?php else: ?>
                                — <span class="badge bg-success">En progreso</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Nuevo Centro de Costo -->
<div class="modal fade" id="modalNuevoCC" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-building me-1"></i> Nuevo Centro de Costo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nombre</label>
                    <input type="text" class="form-control" id="ccNombre" placeholder="Nombre del centro de costo" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Descripcion (opcional)</label>
                    <input type="text" class="form-control" id="ccDescripcion" placeholder="Breve descripcion">
                </div>
                <!-- Sugerencias IA de duplicados -->
                <div id="ccSugerenciasIA" class="d-none">
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
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnGuardarCC">
                    <i class="bi bi-check-lg me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
(function() {
    const BASE = '<?= base_url() ?>';
    const CSRF_NAME  = '<?= csrf_token() ?>';
    const CSRF_HASH  = '<?= csrf_hash() ?>';

    // ---- Inicializar Select2 en Centro de Costo ----
    const $selCC = $('#selCentroCosto');
    if ($selCC.length) {
        $selCC.select2({
            theme: 'bootstrap-5',
            placeholder: 'Selecciona...',
            allowClear: true,
            width: '100%'
        });
    }

    // ---- Selector híbrido de Cliente (FRAMEWORK / Activo / Prospecto) ----
    function inicializarSelectClientes(selectId) {
        const $sel = $('#' + selectId);
        if (!$sel.length || $sel.data('select2-initialized')) return;
        $sel.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Buscar cliente...',
            allowClear: true,
            minimumInputLength: 2,
            language: {
                inputTooShort: function() { return 'Escribe al menos 2 letras...'; },
                searching:     function() { return 'Buscando...'; },
                noResults:     function() { return 'Sin coincidencias'; },
                errorLoading:  function() { return 'Error al buscar'; }
            },
            ajax: {
                url: BASE + 'bitacora/clientes/buscar',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    const origen = $('#selOrigenCliente').val() || 'PH';
                    return { q: params.term || '', origen: origen };
                },
                processResults: function(data) {
                    const items = (data && data.items) ? data.items : [];
                    return {
                        results: items.map(function(it) {
                            return {
                                id: it.nombre,
                                text: it.nombre + (it.ciudad ? ' (' + it.ciudad + ')' : '')
                            };
                        })
                    };
                }
            }
        });
        $sel.data('select2-initialized', true);
    }

    function actualizarVisibilidadCliente() {
        const tipo = $('input[name="tipoCliente"]:checked').val();
        $('#bloqueClienteActivo').toggleClass('d-none', tipo !== 'activo');
        $('#bloqueClienteProspecto').toggleClass('d-none', tipo !== 'prospecto');
        if (tipo === 'activo') inicializarSelectClientes('selClienteActivo');
    }

    $('input[name="tipoCliente"]').on('change', actualizarVisibilidadCliente);
    // Reaplicar resultados al cambiar origen mientras el modo "activo" está activo
    $('#selOrigenCliente').on('change', function() {
        $('#selClienteActivo').val(null).trigger('change');
    });
    actualizarVisibilidadCliente();

    // Lee el valor de cliente desde el formulario híbrido
    function obtenerValorCliente() {
        const tipo = $('input[name="tipoCliente"]:checked').val();
        if (tipo === 'activo') {
            const v = $('#selClienteActivo').val();
            return v ? String(v).trim() : null;
        }
        if (tipo === 'prospecto') {
            const v = ($('#txtClienteProspecto').val() || '').trim();
            return v || null;
        }
        return 'FRAMEWORK';
    }

    let timerInterval = null;
    let timestampInicio = null;  // Date.now() referencia para calcular tiempo real
    let ultimaAlerta30 = 0;     // último bloque de 30 min alertado
    let audioDesbloqueado = false;
    const audio = document.getElementById('audioAlerta');

    // Desbloquear audio con primera interacción
    document.addEventListener('click', function desbloquear() {
        if (!audioDesbloqueado && audio) {
            audio.play().then(() => { audio.pause(); audio.currentTime = 0; })
                       .catch(() => {});
            audioDesbloqueado = true;
        }
    }, { once: false });

    // ---- Formatear segundos a HH:MM:SS ----
    function formatTime(totalSeg) {
        const h = Math.floor(totalSeg / 3600);
        const m = Math.floor((totalSeg % 3600) / 60);
        const s = totalSeg % 60;
        return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }

    // ---- Formatear minutos a "Xh Ym" ----
    function formatMinutosHoras(min) {
        const h = Math.floor(min / 60);
        const m = Math.round(min % 60);
        if (h > 0) return h + 'h ' + m + 'min';
        return m + ' min';
    }

    // ---- Calcular segundos reales transcurridos (inmune a suspensión) ----
    function getSegundosReales() {
        if (!timestampInicio) return 0;
        return Math.floor((Date.now() - timestampInicio) / 1000);
    }

    // ---- Heartbeat: mantener sesión viva solo mientras el cronómetro corre ----
    let heartbeatInterval = null;
    const HEARTBEAT_URL = BASE + 'sesion/heartbeat';

    function iniciarHeartbeat() {
        if (heartbeatInterval) return;
        heartbeatInterval = setInterval(function() {
            fetch(HEARTBEAT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: CSRF_NAME + '=' + CSRF_HASH
            }).catch(function() {});
        }, 60000);
    }

    function detenerHeartbeat() {
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
            heartbeatInterval = null;
        }
    }

    // ---- Actualizar display del cronómetro ----
    function actualizarCronometro() {
        const display = document.getElementById('cronometro');
        if (!display) return;

        const seg = getSegundosReales();
        display.textContent = formatTime(seg);

        // Alerta cada 30 minutos
        const bloque30 = Math.floor(seg / (30 * 60));
        if (bloque30 > 0 && bloque30 > ultimaAlerta30) {
            ultimaAlerta30 = bloque30;
            mostrarAlerta();
        }
    }

    // ---- Pedir permiso de notificaciones ----
    function pedirPermisoNotificaciones() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    // ---- Notificación del sistema (via Service Worker) ----
    function notificacionSistema(texto) {
        if ('Notification' in window && Notification.permission === 'granted' &&
            'serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'mostrar-alerta',
                title: 'Bitácora Cycloid',
                body: texto
            });
        }
    }

    // ---- Iniciar cronómetro visual ----
    function iniciarCronometro(segInicio) {
        // Guardar timestamp de referencia: "Date.now() cuando el cronómetro tenía 0 segundos"
        timestampInicio = Date.now() - ((segInicio || 0) * 1000);
        ultimaAlerta30 = Math.floor((segInicio || 0) / (30 * 60));

        // Pedir permiso para notificaciones del sistema
        pedirPermisoNotificaciones();

        actualizarCronometro();
        timerInterval = setInterval(actualizarCronometro, 1000);

        // Mantener sesión viva mientras el cronómetro corre
        iniciarHeartbeat();
    }

    // ---- Detener cronómetro ----
    function detenerCronometro() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        timestampInicio = null;
        detenerHeartbeat();
    }

    // ---- Recalcular al volver a la pantalla (después de suspensión) ----
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && timestampInicio) {
            actualizarCronometro();
            // Reproducir sonido si hubo alertas perdidas mientras estaba en segundo plano
            const seg = getSegundosReales();
            const bloque30 = Math.floor(seg / (30 * 60));
            if (bloque30 > 0 && bloque30 > ultimaAlerta30) {
                // Hay alertas perdidas: reproducir sonido ahora
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(() => {});
                }
            }
        }
    });

    // ---- Alerta sonora + notificación del sistema ----
    function mostrarAlerta() {
        const descEl = document.querySelector('.fw-bold.mb-2');
        const descripcion = descEl ? descEl.textContent : 'Actividad en progreso';
        const seg = getSegundosReales();
        const minutos = Math.floor(seg / 60);
        const textoNotif = descripcion + ' — ' + minutos + ' min';

        // Notificación del sistema (funciona con pantalla bloqueada)
        notificacionSistema(textoNotif);

        // Si la página está visible, también mostrar alerta in-page
        if (!document.hidden) {
            const alertaDiv = document.getElementById('alertaSonora');
            const alertaTexto = document.getElementById('alertaTexto');

            if (alertaTexto) alertaTexto.textContent = descripcion;
            if (alertaDiv) alertaDiv.style.display = 'block';

            // Reproducir sonido
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {});
            }
        }

        // Vibrar si es posible
        if (navigator.vibrate) navigator.vibrate([300, 100, 300]);
    }

    window.cerrarAlerta = function() {
        const alertaDiv = document.getElementById('alertaSonora');
        if (alertaDiv) alertaDiv.style.display = 'none';
    };

    // ---- AJAX helper ----
    function ajax(method, url, data) {
        const opts = { method: method, headers: {} };
        if (data) {
            const fd = new FormData();
            for (const k in data) fd.append(k, data[k]);
            fd.append(CSRF_NAME, CSRF_HASH);
            opts.body = fd;
        }
        return fetch(BASE + url, opts).then(r => r.json());
    }

    // ---- Si hay actividad activa, arrancar cronómetro ----
    <?php if ($tieneActiva):
        // Calcular segundos transcurridos en el servidor para evitar problemas de timezone/parsing
        $segTranscurridos = time() - strtotime($actividadActiva['hora_inicio']);
        if ($segTranscurridos < 0) $segTranscurridos = 0;
    ?>
    iniciarCronometro(<?= $segTranscurridos ?>);
    <?php endif; ?>

    // ---- Botón INICIAR ----
    const btnIniciar = document.getElementById('btnIniciar');
    if (btnIniciar) {
        btnIniciar.addEventListener('click', function() {
            const desc = document.getElementById('txtDescripcion').value.trim();
            const cc   = $('#selCentroCosto').val();
            const cli  = obtenerValorCliente();

            if (!desc) { alert('Escribe la descripción de la actividad'); return; }
            if (!cc)   { alert('Selecciona un centro de costo'); return; }
            if (cli === null) {
                const tipo = $('input[name="tipoCliente"]:checked').val();
                alert(tipo === 'activo'
                    ? 'Selecciona un cliente activo o cambia a Prospecto / FRAMEWORK'
                    : 'Escribe el nombre del cliente prospecto o cambia a Activo / FRAMEWORK');
                return;
            }

            btnIniciar.disabled = true;
            btnIniciar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Iniciando...';

            ajax('POST', 'bitacora/iniciar', { descripcion: desc, id_centro_costo: cc, cliente: cli })
                .then(function(resp) {
                    if (resp.ok) {
                        location.reload();
                    } else {
                        alert(resp.error || 'Error al iniciar');
                        btnIniciar.disabled = false;
                        btnIniciar.innerHTML = '<i class="bi bi-play-circle me-1"></i> Iniciar';
                    }
                })
                .catch(function() {
                    alert('Error de conexión');
                    btnIniciar.disabled = false;
                    btnIniciar.innerHTML = '<i class="bi bi-play-circle me-1"></i> Iniciar';
                });
        });
    }

    // ---- Modal Nuevo Centro de Costo con verificación IA ----
    const btnGuardarCC = document.getElementById('btnGuardarCC');
    const btnConfirmarCC = document.getElementById('btnConfirmarCC');
    if (btnGuardarCC) {
        btnGuardarCC.addEventListener('click', function() {
            const nombre = document.getElementById('ccNombre').value.trim();
            if (!nombre) { alert('El nombre es obligatorio'); return; }

            btnGuardarCC.disabled = true;
            btnGuardarCC.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verificando...';
            document.getElementById('ccSugerenciasIA').classList.add('d-none');

            // Verificar duplicados con IA
            ajax('POST', 'bitacora/centros-costo/verificar-duplicado', { nombre: nombre })
                .then(function(resp) {
                    if (resp.similares && resp.similares.length > 0) {
                        // Mostrar sugerencias
                        const lista = document.getElementById('ccListaSugerencias');
                        lista.innerHTML = resp.similares.map(function(s) {
                            return '<div class="d-flex align-items-center gap-2 mb-1">' +
                                '<i class="bi bi-arrow-right"></i> <strong>' + s.nombre + '</strong>' +
                                (s.razon ? ' <span class="text-muted">(' + s.razon + ')</span>' : '') +
                                ' <button class="btn btn-sm btn-outline-primary py-0 px-1 btn-usar-existente" data-id="' + s.id + '">' +
                                'Usar este</button></div>';
                        }).join('');
                        document.getElementById('ccSugerenciasIA').classList.remove('d-none');
                        btnGuardarCC.disabled = false;
                        btnGuardarCC.innerHTML = '<i class="bi bi-check-lg me-1"></i> Guardar';

                        // Listeners para "Usar este"
                        lista.querySelectorAll('.btn-usar-existente').forEach(function(b) {
                            b.addEventListener('click', function() {
                                $('#selCentroCosto').val(this.dataset.id).trigger('change');
                                bootstrap.Modal.getInstance(document.getElementById('modalNuevoCC')).hide();
                            });
                        });
                    } else {
                        // No hay duplicados, guardar directamente
                        guardarNuevoCC(nombre);
                    }
                })
                .catch(function() {
                    // Si falla la IA, guardar sin verificar
                    guardarNuevoCC(nombre);
                });
        });

        if (btnConfirmarCC) {
            btnConfirmarCC.addEventListener('click', function() {
                const nombre = document.getElementById('ccNombre').value.trim();
                guardarNuevoCC(nombre);
            });
        }
    }

    function guardarNuevoCC(nombre) {
        const desc = document.getElementById('ccDescripcion').value.trim();
        const btn = document.getElementById('btnGuardarCC');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

        ajax('POST', 'bitacora/centros-costo/guardar', { nombre: nombre, descripcion: desc })
            .then(function(resp) {
                if (resp.ok) {
                    // Agregar al select y seleccionarlo via Select2
                    const $sel = $('#selCentroCosto');
                    const newOpt = new Option(nombre, resp.id, true, true);
                    $sel.append(newOpt).trigger('change');
                    bootstrap.Modal.getInstance(document.getElementById('modalNuevoCC')).hide();
                    document.getElementById('ccNombre').value = '';
                    document.getElementById('ccDescripcion').value = '';
                    document.getElementById('ccSugerenciasIA').classList.add('d-none');
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

    // ---- Botón TERMINAR ----
    const btnTerminar = document.getElementById('btnTerminar');
    if (btnTerminar) {
        btnTerminar.addEventListener('click', function() {
            const id = btnTerminar.getAttribute('data-id');
            const descActual = <?= json_encode($actividadActiva['descripcion'] ?? '') ?>;

            Swal.fire({
                title: '¿Terminar esta actividad?',
                text: '¿Deseas editar la descripción antes de finalizar?',
                icon: 'question',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-pencil-square me-1"></i> Sí, editar descripción',
                denyButtonText: '<i class="bi bi-check-circle me-1"></i> No, terminar así',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd',
                denyButtonColor: '#198754',
            }).then(function(result) {
                if (result.isConfirmed) {
                    // Mostrar segundo modal con textarea, centro de costo y cliente
                    var ccActualId = <?= json_encode($actividadActiva['id_centro_costo'] ?? '') ?>;
                    var clienteActual = <?= json_encode($actividadActiva['cliente'] ?? 'FRAMEWORK') ?>;
                    var opcionesCC = '';
                    <?php if (!empty($centrosCosto)): ?>
                    <?php foreach ($centrosCosto as $cc): ?>
                    opcionesCC += '<option value="<?= $cc['id_centro_costo'] ?>"><?= esc($cc['nombre']) ?></option>';
                    <?php endforeach; ?>
                    <?php endif; ?>

                    // Determinar tipo de cliente actual: framework / activo / prospecto
                    var tipoCliInicial = 'framework';
                    if (clienteActual && clienteActual !== 'FRAMEWORK') {
                        // No podemos saber con certeza si era activo o prospecto;
                        // por defecto mostramos como prospecto (texto libre) para preservar el valor
                        tipoCliInicial = 'prospecto';
                    }

                    Swal.fire({
                        title: 'Editar actividad',
                        width: 520,
                        html:
                            '<label class="form-label small fw-bold text-start d-block mb-1">Descripción</label>' +
                            '<textarea id="swalDescripcion" class="swal2-textarea" rows="3" style="font-size:0.95rem;display:block;">' + descActual.replace(/</g,'&lt;') + '</textarea>' +
                            '<label class="form-label small fw-bold text-start d-block mb-1 mt-2">Centro de Costo</label>' +
                            '<select id="swalCentroCosto" class="swal2-select" style="display:block;">' +
                            '<option value="">Selecciona...</option>' +
                            opcionesCC +
                            '</select>' +
                            '<label class="form-label small fw-bold text-start d-block mb-1 mt-2">Cliente</label>' +
                            '<div class="text-muted small text-start mb-1" style="font-size:0.72rem;">Si es transversal: FRAMEWORK.</div>' +
                            '<div class="btn-group btn-group-sm w-100 mb-2" role="group">' +
                                '<input type="radio" class="btn-check" name="swalTipoCli" id="swalTipoFw" value="framework">' +
                                '<label class="btn btn-outline-secondary" for="swalTipoFw">FRAMEWORK</label>' +
                                '<input type="radio" class="btn-check" name="swalTipoCli" id="swalTipoAct" value="activo">' +
                                '<label class="btn btn-outline-success" for="swalTipoAct">Activo</label>' +
                                '<input type="radio" class="btn-check" name="swalTipoCli" id="swalTipoPro" value="prospecto">' +
                                '<label class="btn btn-outline-warning" for="swalTipoPro">Prospecto</label>' +
                            '</div>' +
                            '<div id="swalBloqueActivo" class="d-none">' +
                                '<div class="d-flex gap-2">' +
                                    '<select id="swalOrigenCli" class="form-select form-select-sm" style="max-width:165px;">' +
                                        '<option value="PH">Propiedad Horizontal</option>' +
                                        '<option value="SST">Empresas SST</option>' +
                                    '</select>' +
                                    '<select id="swalCliActivo" class="form-select form-select-sm flex-grow-1"></select>' +
                                '</div>' +
                            '</div>' +
                            '<div id="swalBloqueProspecto" class="d-none">' +
                                '<input type="text" id="swalCliProspecto" class="form-control form-control-sm" placeholder="Nombre del cliente prospecto" maxlength="150">' +
                            '</div>',
                        didOpen: function() {
                            var sel = document.getElementById('swalCentroCosto');
                            if (sel && ccActualId) sel.value = ccActualId;

                            // Marcar tipo de cliente inicial
                            document.getElementById('swalTipoFw').checked  = (tipoCliInicial === 'framework');
                            document.getElementById('swalTipoAct').checked = (tipoCliInicial === 'activo');
                            document.getElementById('swalTipoPro').checked = (tipoCliInicial === 'prospecto');
                            if (tipoCliInicial === 'prospecto') {
                                document.getElementById('swalBloqueProspecto').classList.remove('d-none');
                                document.getElementById('swalCliProspecto').value = clienteActual;
                            }

                            function swalToggleCli() {
                                var t = document.querySelector('input[name="swalTipoCli"]:checked').value;
                                document.getElementById('swalBloqueActivo').classList.toggle('d-none', t !== 'activo');
                                document.getElementById('swalBloqueProspecto').classList.toggle('d-none', t !== 'prospecto');
                                if (t === 'activo' && !$('#swalCliActivo').data('select2-initialized')) {
                                    $('#swalCliActivo').select2({
                                        theme: 'bootstrap-5',
                                        width: '100%',
                                        placeholder: 'Buscar cliente...',
                                        allowClear: true,
                                        minimumInputLength: 2,
                                        dropdownParent: $('.swal2-popup'),
                                        ajax: {
                                            url: BASE + 'bitacora/clientes/buscar',
                                            dataType: 'json',
                                            delay: 250,
                                            data: function(p) { return { q: p.term || '', origen: $('#swalOrigenCli').val() || 'PH' }; },
                                            processResults: function(d) {
                                                var its = (d && d.items) ? d.items : [];
                                                return { results: its.map(function(it) {
                                                    return { id: it.nombre, text: it.nombre + (it.ciudad ? ' (' + it.ciudad + ')' : '') };
                                                }) };
                                            }
                                        }
                                    });
                                    $('#swalCliActivo').data('select2-initialized', true);
                                    $('#swalOrigenCli').on('change', function() { $('#swalCliActivo').val(null).trigger('change'); });
                                }
                            }
                            document.querySelectorAll('input[name="swalTipoCli"]').forEach(function(r) {
                                r.addEventListener('change', swalToggleCli);
                            });
                        },
                        preConfirm: function() {
                            var desc = document.getElementById('swalDescripcion').value;
                            var cc = document.getElementById('swalCentroCosto').value;
                            if (!desc || !desc.trim()) {
                                Swal.showValidationMessage('La descripción no puede estar vacía');
                                return false;
                            }
                            if (!cc) {
                                Swal.showValidationMessage('Debes seleccionar un centro de costo');
                                return false;
                            }
                            var t = document.querySelector('input[name="swalTipoCli"]:checked').value;
                            var cli = 'FRAMEWORK';
                            if (t === 'activo') {
                                cli = ($('#swalCliActivo').val() || '').trim();
                                if (!cli) { Swal.showValidationMessage('Selecciona un cliente activo o cambia a Prospecto / FRAMEWORK'); return false; }
                            } else if (t === 'prospecto') {
                                cli = (document.getElementById('swalCliProspecto').value || '').trim();
                                if (!cli) { Swal.showValidationMessage('Escribe el nombre del cliente prospecto o cambia a Activo / FRAMEWORK'); return false; }
                            }
                            return { descripcion: desc, id_centro_costo: cc, cliente: cli };
                        },
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-stop-circle me-1"></i> Terminar Actividad',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc3545',
                    }).then(function(result2) {
                        if (result2.isConfirmed) {
                            finalizarActividad(id, result2.value.descripcion, result2.value.id_centro_costo, result2.value.cliente);
                        }
                    });
                } else if (result.isDenied) {
                    // Terminar sin editar
                    finalizarActividad(id, null);
                }
            });
        });
    }

    function finalizarActividad(id, nuevaDescripcion, nuevoCentroCosto, nuevoCliente) {
        btnTerminar.disabled = true;
        btnTerminar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Finalizando...';
        detenerCronometro();

        var datos = {};
        if (nuevaDescripcion !== null) {
            datos.descripcion = nuevaDescripcion;
        }
        if (nuevoCentroCosto) {
            datos.id_centro_costo = nuevoCentroCosto;
        }
        if (nuevoCliente) {
            datos.cliente = nuevoCliente;
        }

        ajax('POST', 'bitacora/terminar/' + id, datos)
            .then(function(resp) {
                if (resp.ok) {
                    location.reload();
                } else {
                    Swal.fire('Error', resp.error || 'Error al terminar', 'error');
                    btnTerminar.disabled = false;
                    btnTerminar.innerHTML = '<i class="bi bi-stop-circle me-1"></i> Terminar Actividad';
                }
            })
            .catch(function() {
                Swal.fire('Error', 'Error de conexión', 'error');
                btnTerminar.disabled = false;
                btnTerminar.innerHTML = '<i class="bi bi-stop-circle me-1"></i> Terminar Actividad';
            });
    }

    // ---- Botón DESCARTAR (olvidaste detener) ----
    const btnDescartar = document.getElementById('btnDescartar');
    if (btnDescartar) {
        btnDescartar.addEventListener('click', function() {
            const id = btnDescartar.getAttribute('data-id');
            const ok = confirm('Esta acción borrará tu último registro y no podremos recuperar la información, esa gestión desafortunadamente no quedará registrada.\n\nTe invitamos a estar más atento al control de tu bitácora de actividades.');
            if (!ok) return;

            btnDescartar.disabled = true;
            detenerCronometro();

            ajax('POST', 'bitacora/descartar/' + id, {})
                .then(function(resp) {
                    if (resp.ok) {
                        location.reload();
                    } else {
                        alert(resp.error || 'Error al descartar');
                        btnDescartar.disabled = false;
                    }
                })
                .catch(function() {
                    alert('Error de conexión');
                    btnDescartar.disabled = false;
                });
        });
    }
    // ---- TABLERO EQUIPO EN PROGRESO ----
    let equipoData = [];

    function getIniciales(nombre) {
        return nombre.split(' ').map(function(p) { return p[0]; }).join('').substring(0, 2).toUpperCase();
    }

    function formatHace(seg) {
        if (seg < 0) seg = 0;
        const h = Math.floor(seg / 3600);
        const m = Math.floor((seg % 3600) / 60);
        if (h > 0) return h + 'h ' + m + 'min';
        return m + ' min';
    }

    function formatHoraInicio(horaISO) {
        var d = new Date(horaISO);
        var h = d.getHours();
        var m = d.getMinutes();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + String(m).padStart(2,'0') + ' ' + ampm;
    }

    function renderEquipo() {
        const container = document.getElementById('listaEquipoProgreso');
        const conteo = document.getElementById('conteoEquipo');
        if (!container) return;

        if (equipoData.length === 0) {
            container.innerHTML = '<div class="text-center text-muted small py-2">' +
                '<i class="bi bi-emoji-sunglasses fs-5 d-block mb-1"></i>Nadie trabajando en este momento</div>';
            conteo.textContent = '0';
            return;
        }

        conteo.textContent = equipoData.length;
        container.innerHTML = equipoData.map(function(item) {
            var cliente = item.cliente || 'FRAMEWORK';
            return '<div class="equipo-item">' +
                '<div class="equipo-avatar">' + getIniciales(item.nombre_completo) + '</div>' +
                '<div class="flex-grow-1">' +
                    '<div class="fw-bold small" style="line-height:1.2">' + item.nombre_completo + '</div>' +
                    '<div class="text-muted" style="font-size:0.75rem"><i class="bi bi-lightning-charge-fill text-warning"></i> ' + item.descripcion + '</div>' +
                    '<div class="text-muted" style="font-size:0.7rem"><i class="bi bi-person-badge"></i> ' + cliente + '</div>' +
                    (item.centro_costo_nombre ? '<div class="text-muted" style="font-size:0.7rem"><i class="bi bi-building"></i> ' + item.centro_costo_nombre + '</div>' : '') +
                '</div>' +
                '<div class="text-end" style="min-width:70px">' +
                    '<div class="equipo-timer" data-seg="' + item.segundos_transcurridos + '">' +
                        formatHace(parseInt(item.segundos_transcurridos)) +
                    '</div>' +
                    '<div class="text-muted" style="font-size:0.65rem">' +
                        '<i class="bi bi-clock"></i> ' + formatHoraInicio(item.hora_inicio) +
                    '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    function cargarEquipoProgreso() {
        fetch(BASE + 'bitacora/equipo-en-progreso')
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.ok) {
                    equipoData = resp.actividades;
                    renderEquipo();
                }
            })
            .catch(function() {});
    }

    // Actualizar timers del equipo cada 30 segundos
    setInterval(function() {
        document.querySelectorAll('.equipo-timer[data-seg]').forEach(function(el) {
            var seg = parseInt(el.getAttribute('data-seg')) + 30;
            el.setAttribute('data-seg', seg);
            el.textContent = formatHace(seg);
        });
    }, 30000);

    // Cargar al inicio y refrescar cada 30 segundos
    cargarEquipoProgreso();
    setInterval(cargarEquipoProgreso, 30000);

})();
</script>
<?= $this->endSection() ?>
