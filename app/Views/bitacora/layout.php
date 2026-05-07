<?php $session = session(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Bitácora – Cycloid</title>

    <!-- PWA -->
    <meta name="theme-color" content="#2c3e50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Bitácora">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="<?= base_url('bitacora-manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('img/icons/icon-192x192.png') ?>">
    <link rel="icon" type="image/png" href="<?= base_url('img/icons/icon-96x96.png') ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        :root {
            --bs-body-bg: #f5f6fa;
            --header-bg: #2c3e50;
        }
        * { box-sizing: border-box; }

        /* Select2 ajustes */
        .select2-container .select2-selection--single {
            height: 38px;
            padding: 5px 0;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        body {
            background: var(--bs-body-bg);
            padding-top: 56px;
            padding-bottom: 70px;
            -webkit-tap-highlight-color: transparent;
        }

        /* Header fijo */
        .bitacora-header {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1030;
            background: var(--header-bg);
            color: #fff;
            height: 56px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px;
        }
        .bitacora-header .user-name { font-size: 0.9rem; opacity: 0.85; }
        .bitacora-header .logo { height: 32px; }

        /* Bottom tabs */
        .bitacora-tabs {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030;
            background: #fff;
            border-top: 1px solid #dee2e6;
            display: flex;
            height: 64px;
        }
        .bitacora-tabs a {
            flex: 1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-decoration: none;
            color: #6c757d;
            font-size: 0.7rem;
            transition: color 0.2s;
        }
        .bitacora-tabs a.active { color: #0d6efd; font-weight: 600; }
        .bitacora-tabs a i { font-size: 1.3rem; margin-bottom: 2px; }

        /* Cronómetro */
        .cronometro-display {
            font-size: 3rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            text-align: center;
            color: #2c3e50;
            line-height: 1;
        }
        .cronometro-display.running { color: #198754; }

        /* Cards compactas */
        .actividad-card {
            background: #fff;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .actividad-card .num {
            background: #0d6efd;
            color: #fff;
            border-radius: 50%;
            width: 28px; height: 28px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700;
        }
        .actividad-card.en-progreso { border-left: 4px solid #198754; }
        .actividad-card.finalizada { border-left: 4px solid #6c757d; }

        .total-horas {
            background: #2c3e50;
            color: #fff;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
            font-size: 1.1rem;
        }
        .total-horas strong { font-size: 1.4rem; }

        /* Alerta sonora */
        .alerta-sonora {
            display: none;
            position: fixed; top: 60px; left: 10px; right: 10px; z-index: 9999;
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }

        /* Banner offline */
        .offline-banner {
            display: none;
            position: fixed; top: 56px; left: 0; right: 0; z-index: 1029;
            background: #dc3545;
            color: #fff;
            text-align: center;
            padding: 4px 8px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .offline-banner.visible { display: block; }
        body.is-offline { padding-top: 80px; }

        /* Tablero equipo en progreso */
        .equipo-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .equipo-item:last-child { border-bottom: none; }
        .equipo-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #198754;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .equipo-timer {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            font-weight: 600;
            color: #198754;
        }

        /* Toast offline guardado */
        .offline-toast {
            position: fixed; bottom: 80px; left: 16px; right: 16px; z-index: 9999;
            background: #198754;
            color: #fff;
            border-radius: 10px;
            padding: 12px 16px;
            text-align: center;
            font-size: 0.85rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
            display: none;
        }
        .offline-toast.visible { display: block; }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <?php
        // Dashboard principal según rol (mismo switch que AuthController::index)
        $rolHeader   = (int) $session->get('id_roles');
        $dashboardUrl = match ($rolHeader) {
            1       => 'superadmin/superadmindashboard',
            2       => 'admin/admindashboard',
            3       => 'jefatura/jefaturadashboard',
            default => 'trabajador/trabajadordashboard',
        };
    ?>
    <div class="bitacora-header">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= base_url('img/cycloid_sqe.jpg') ?>" alt="Logo" class="logo rounded">
            <span class="fw-bold">Bitácora</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="user-name d-none d-sm-inline"><?= esc($session->get('nombre_completo')) ?></span>
            <a href="<?= base_url($dashboardUrl) ?>" class="btn btn-sm btn-outline-light" title="Dashboard Principal">
                <i class="bi bi-grid-1x2"></i>
                <span class="d-none d-sm-inline ms-1">Dashboard</span>
            </a>
            <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-outline-light" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Alerta sonora 30 min -->
    <div class="alerta-sonora" id="alertaSonora">
        <i class="bi bi-bell-fill text-warning fs-3"></i>
        <div class="fw-bold mt-1">Actividad en progreso</div>
        <div class="text-muted" id="alertaTexto"></div>
        <button class="btn btn-sm btn-warning mt-2" onclick="cerrarAlerta()">Entendido</button>
    </div>

    <!-- Contenido principal -->
    <div class="container-fluid px-3 py-3">
        <?= $this->renderSection('content') ?>
    </div>

    <!-- Bottom Tabs -->
    <?php $rolId = (int) $session->get('id_roles'); ?>
    <div class="bitacora-tabs">
        <a href="<?= base_url('bitacora') ?>" class="<?= ($tab ?? '') === 'bitacora' ? 'active' : '' ?>">
            <i class="bi bi-stopwatch"></i>
            Bitacora
        </a>
        <a href="<?= base_url('bitacora/resumen') ?>" class="<?= ($tab ?? '') === 'resumen' ? 'active' : '' ?>">
            <i class="bi bi-graph-up"></i>
            Resumen
        </a>
        <a href="<?= base_url('bitacora/analisis') ?>" class="<?= ($tab ?? '') === 'analisis' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i>
            Análisis
        </a>
        <?php if (in_array($rolId, [1, 2, 3])): ?>
        <a href="<?= base_url('bitacora/equipo') ?>" class="<?= ($tab ?? '') === 'equipo' ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            Equipo
        </a>
        <?php endif; ?>
        <?php if ($session->get('admin_bitacora')): ?>
        <a href="<?= base_url('bitacora/liquidacion') ?>" class="<?= ($tab ?? '') === 'liquidacion' ? 'active' : '' ?>">
            <i class="bi bi-calculator"></i>
            Liquidación
        </a>
        <?php endif; ?>
        <a href="<?= base_url('bitacora/centros-costo') ?>" class="<?= ($tab ?? '') === 'centros' ? 'active' : '' ?>">
            <i class="bi bi-building"></i>
            Centros
        </a>
    </div>

    <!-- Audio alerta -->
    <audio id="audioAlerta" preload="auto">
        <source src="<?= base_url('sounds/alert.mp3') ?>" type="audio/mpeg">
    </audio>

    <!-- jQuery + Bootstrap JS + Select2 + SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- PWA Service Worker + Push Subscription -->
    <script>
    (function() {
        var BASE = '<?= base_url() ?>';
        var CSRF_NAME = '<?= csrf_token() ?>';
        var CSRF_HASH = '<?= csrf_hash() ?>';

        if (!('serviceWorker' in navigator)) return;

        navigator.serviceWorker.register('<?= base_url('bitacora-sw.js') ?>')
            .then(function(registration) {
                // Esperar a que el SW esté activo
                var sw = registration.installing || registration.waiting || registration.active;
                if (!sw) return;

                function suscribirPush() {
                    // Pedir permiso de notificaciones
                    if (!('Notification' in window)) return;
                    if (Notification.permission === 'denied') return;

                    Notification.requestPermission().then(function(permission) {
                        if (permission !== 'granted') return;

                        // Obtener VAPID public key del servidor
                        fetch(BASE + 'bitacora/push/vapid-key')
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (!data.publicKey) return;

                                // Convertir base64url a Uint8Array
                                var rawKey = data.publicKey.replace(/-/g, '+').replace(/_/g, '/');
                                var padding = '='.repeat((4 - rawKey.length % 4) % 4);
                                var bytes = atob(rawKey + padding);
                                var array = new Uint8Array(bytes.length);
                                for (var i = 0; i < bytes.length; i++) array[i] = bytes.charCodeAt(i);

                                return registration.pushManager.subscribe({
                                    userVisibleOnly: true,
                                    applicationServerKey: array
                                });
                            })
                            .then(function(subscription) {
                                if (!subscription) return;

                                // Enviar suscripción al servidor
                                var key = subscription.getKey('p256dh');
                                var auth = subscription.getKey('auth');
                                var fd = new FormData();
                                fd.append('endpoint', subscription.endpoint);
                                fd.append('p256dh', btoa(String.fromCharCode.apply(null, new Uint8Array(key))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,''));
                                fd.append('auth', btoa(String.fromCharCode.apply(null, new Uint8Array(auth))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,''));
                                fd.append(CSRF_NAME, CSRF_HASH);

                                fetch(BASE + 'bitacora/push/subscribe', { method: 'POST', body: fd });
                            })
                            .catch(function(e) { console.log('Push subscription error:', e); });
                    });
                }

                if (sw.state === 'activated') {
                    suscribirPush();
                } else {
                    sw.addEventListener('statechange', function() {
                        if (sw.state === 'activated') suscribirPush();
                    });
                }
            });
    })();
    </script>

    <?= $this->renderSection('modals') ?>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
