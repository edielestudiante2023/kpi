<?php if (session()->get('id_users')): ?>
<script>
(function() {
    // Configuracion del heartbeat
    const HEARTBEAT_INTERVAL = 60000; // 1 minuto en milisegundos
    const HEARTBEAT_URL = '<?= base_url('sesion/heartbeat') ?>';

    let heartbeatTimer = null;
    let isPageVisible = true;
    let lastHeartbeat = Date.now();

    // Funcion para enviar heartbeat
    function sendHeartbeat() {
        // No enviar si la pagina no esta visible y han pasado menos de 5 minutos
        if (!isPageVisible && (Date.now() - lastHeartbeat) < 300000) {
            return;
        }

        fetch(HEARTBEAT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                lastHeartbeat = Date.now();
            }
        })
        .catch(error => {
            // Silenciar errores de red
            console.debug('Heartbeat error:', error);
        });
    }

    // Iniciar el intervalo de heartbeat
    function startHeartbeat() {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
        }
        heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);
        // Enviar uno inmediatamente
        sendHeartbeat();
    }

    // Detectar cambios de visibilidad de la pagina
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
        if (isPageVisible) {
            // Enviar heartbeat inmediatamente al volver a la pagina
            sendHeartbeat();
        }
    });

    // Detectar cuando el usuario esta por cerrar la pagina
    window.addEventListener('beforeunload', function() {
        // Intentar enviar un ultimo heartbeat de forma sincrona
        if (navigator.sendBeacon) {
            const formData = new FormData();
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            navigator.sendBeacon(HEARTBEAT_URL, formData);
        }
    });

    // Detectar actividad del usuario para reactivar heartbeat si estaba pausado
    ['mousedown', 'keydown', 'touchstart', 'scroll'].forEach(function(event) {
        document.addEventListener(event, function() {
            if (!isPageVisible) {
                isPageVisible = true;
            }
        }, { passive: true, once: false });
    });

    // Iniciar al cargar la pagina
    if (document.readyState === 'complete') {
        startHeartbeat();
    } else {
        window.addEventListener('load', startHeartbeat);
    }
})();
</script>
<?php endif; ?>
