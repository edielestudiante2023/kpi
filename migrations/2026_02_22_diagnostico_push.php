<?php

/**
 * Diagnóstico: Verificar estado del sistema de Push Notifications
 *
 * Uso:  php migrations/2026_02_22_diagnostico_push.php
 *       php migrations/2026_02_22_diagnostico_push.php test   (envía push de prueba)
 */

$enviarTest = ($argv[1] ?? '') === 'test';

// ── Conexión ──────────────────────────────────────────────
$dotenv = @parse_ini_file(__DIR__ . '/../.env');
$config = [
    'host'     => $dotenv['database.default.hostname'] ?? '127.0.0.1',
    'port'     => $dotenv['database.default.port']     ?? 3306,
    'username' => $dotenv['database.default.username'] ?? 'root',
    'password' => $dotenv['database.default.password'] ?? '',
    'database' => $dotenv['database.default.database'] ?? 'kpicycloid',
];

$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if ($config['host'] !== '127.0.0.1' && $config['host'] !== 'localhost') {
    $opts[PDO::MYSQL_ATTR_SSL_CA] = true;
    $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}
$pdo = new PDO($dsn, $config['username'], $config['password'], $opts);

echo "=== DIAGNÓSTICO PUSH NOTIFICATIONS ===\n";
echo "Conectado a: {$config['host']}\n\n";

// ── 1. Verificar tabla push_subscriptions ─────────────────
echo "1. TABLA push_subscriptions:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM push_subscriptions");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total suscripciones: $total\n";

    if ($total > 0) {
        $stmt = $pdo->query("
            SELECT ps.id, ps.id_usuario, u.nombre_completo,
                   SUBSTRING(ps.endpoint, 1, 60) as endpoint_preview,
                   LENGTH(ps.p256dh) as p256dh_len,
                   LENGTH(ps.auth) as auth_len,
                   ps.created_at
            FROM push_subscriptions ps
            LEFT JOIN users u ON ps.id_usuario = u.id_users
            ORDER BY ps.created_at DESC
        ");
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Detalle:\n";
        foreach ($subs as $s) {
            echo "   - ID:{$s['id']} | Usuario: {$s['nombre_completo']} (ID:{$s['id_usuario']})\n";
            echo "     Endpoint: {$s['endpoint_preview']}...\n";
            echo "     p256dh: {$s['p256dh_len']} chars | auth: {$s['auth_len']} chars\n";
            echo "     Creado: {$s['created_at']}\n";
        }
    } else {
        echo "   [PROBLEMA] No hay suscripciones. Ningún navegador se ha suscrito.\n";
        echo "   Causas posibles:\n";
        echo "   - El usuario no aceptó el permiso de notificaciones\n";
        echo "   - El JS de suscripción falló (CSRF, error de red)\n";
        echo "   - El Service Worker no se activó correctamente\n";
    }
} catch (Exception $e) {
    echo "   [ERROR] Tabla no existe: {$e->getMessage()}\n";
}

echo "\n";

// ── 2. Verificar VAPID keys en .env ───────────────────────
echo "2. VAPID KEYS:\n";
$vapidPublic  = $dotenv['VAPID_PUBLIC_KEY']  ?? '';
$vapidPrivate = $dotenv['VAPID_PRIVATE_KEY'] ?? '';

if (empty($vapidPublic)) {
    echo "   [ERROR] VAPID_PUBLIC_KEY no está en .env\n";
} else {
    echo "   VAPID_PUBLIC_KEY: " . substr($vapidPublic, 0, 20) . "... (" . strlen($vapidPublic) . " chars) [OK]\n";
}

if (empty($vapidPrivate)) {
    echo "   [ERROR] VAPID_PRIVATE_KEY no está en .env\n";
} else {
    echo "   VAPID_PRIVATE_KEY: " . substr($vapidPrivate, 0, 10) . "... (" . strlen($vapidPrivate) . " chars) [OK]\n";
}

echo "\n";

// ── 3. Verificar actividades en progreso ──────────────────
echo "3. ACTIVIDADES EN PROGRESO:\n";
$stmt = $pdo->query("
    SELECT ba.id_bitacora, ba.id_usuario, u.nombre_completo,
           ba.descripcion, ba.hora_inicio,
           TIMESTAMPDIFF(MINUTE, ba.hora_inicio, NOW()) as minutos
    FROM bitacora_actividades ba
    JOIN users u ON ba.id_usuario = u.id_users
    WHERE ba.estado = 'en_progreso'
    ORDER BY ba.hora_inicio ASC
");
$activas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($activas)) {
    echo "   Ninguna actividad en progreso ahora mismo.\n";
} else {
    foreach ($activas as $a) {
        echo "   - {$a['nombre_completo']}: \"{$a['descripcion']}\" — {$a['minutos']} min (desde {$a['hora_inicio']})\n";
    }
}

echo "\n";

// ── 4. Verificar que la librería web-push existe ──────────
echo "4. LIBRERÍA web-push:\n";
$webPushPath = __DIR__ . '/../vendor/minishlink/web-push/src/WebPush.php';
if (file_exists($webPushPath)) {
    echo "   vendor/minishlink/web-push [OK]\n";
} else {
    echo "   [ERROR] vendor/minishlink/web-push NO ENCONTRADA\n";
    echo "   Ejecutar: composer require minishlink/web-push\n";
}

echo "\n";

// ── 5. Test de envío (solo si se pasa 'test') ─────────────
if ($enviarTest && $total > 0 && !empty($vapidPublic) && !empty($vapidPrivate)) {
    echo "5. ENVIANDO PUSH DE PRUEBA:\n";

    // Cargar autoload de composer
    require_once __DIR__ . '/../vendor/autoload.php';

    $auth = [
        'VAPID' => [
            'subject'    => 'mailto:admin@cycloidtalent.com',
            'publicKey'  => $vapidPublic,
            'privateKey' => $vapidPrivate,
        ],
    ];

    try {
        $webPush = new \Minishlink\WebPush\WebPush($auth);
        $webPush->setAutomaticPadding(false);

        $payload = json_encode([
            'title' => 'Test Push — Bitácora Cycloid',
            'body'  => 'Si ves esto, las push notifications funcionan correctamente.',
            'url'   => '/bitacora',
        ]);

        // Enviar a todas las suscripciones
        $stmt = $pdo->query("SELECT endpoint, p256dh, auth FROM push_subscriptions");
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($subs as $sub) {
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint'  => $sub['endpoint'],
                'publicKey'  => $sub['p256dh'],
                'authToken'  => $sub['auth'],
            ]);
            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = substr($report->getEndpoint(), 0, 60);
            if ($report->isSuccess()) {
                echo "   [OK] Push enviado a: {$endpoint}...\n";
            } else {
                echo "   [ERROR] Falló: {$endpoint}...\n";
                echo "   Razón: " . $report->getReason() . "\n";
                if ($report->isSubscriptionExpired()) {
                    echo "   (Suscripción expirada — se debe re-suscribir)\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "   [ERROR] Excepción: " . $e->getMessage() . "\n";
    }
} elseif ($enviarTest) {
    echo "5. No se puede enviar test: ";
    if ($total == 0) echo "no hay suscripciones.";
    if (empty($vapidPublic) || empty($vapidPrivate)) echo " VAPID keys faltantes.";
    echo "\n";
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
