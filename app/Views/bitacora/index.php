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
                <div class="text-muted small mb-2">
                    <i class="bi bi-building"></i> <?= esc($actividadActiva['centro_costo_nombre'] ?? '') ?>
                </div>

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
                    <div class="d-flex gap-2">
                        <select class="form-select" id="selCentroCosto" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($centrosCosto as $cc): ?>
                                <option value="<?= $cc['id_centro_costo'] ?>"><?= esc($cc['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoCC" title="Nuevo centro de costo">
                            <i class="bi bi-plus-lg"></i>
                        </button>
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
            const cc   = document.getElementById('selCentroCosto').value;

            if (!desc) { alert('Escribe la descripción de la actividad'); return; }
            if (!cc)   { alert('Selecciona un centro de costo'); return; }

            btnIniciar.disabled = true;
            btnIniciar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Iniciando...';

            ajax('POST', 'bitacora/iniciar', { descripcion: desc, id_centro_costo: cc })
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
                                document.getElementById('selCentroCosto').value = this.dataset.id;
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
                    // Agregar al select y seleccionarlo
                    const sel = document.getElementById('selCentroCosto');
                    const opt = document.createElement('option');
                    opt.value = resp.id;
                    opt.textContent = nombre;
                    sel.appendChild(opt);
                    sel.value = resp.id;
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
            if (!confirm('¿Terminar esta actividad?')) return;

            btnTerminar.disabled = true;
            btnTerminar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Finalizando...';
            detenerCronometro();

            ajax('POST', 'bitacora/terminar/' + id, {})
                .then(function(resp) {
                    if (resp.ok) {
                        location.reload();
                    } else {
                        alert(resp.error || 'Error al terminar');
                        btnTerminar.disabled = false;
                        btnTerminar.innerHTML = '<i class="bi bi-stop-circle me-1"></i> Terminar Actividad';
                    }
                })
                .catch(function() {
                    alert('Error de conexión');
                    btnTerminar.disabled = false;
                    btnTerminar.innerHTML = '<i class="bi bi-stop-circle me-1"></i> Terminar Actividad';
                });
        });
    }
})();
</script>
<?= $this->endSection() ?>
