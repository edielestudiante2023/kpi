<?php

/**
 * Diagnóstico: Auditoría de Sesiones
 * Ejecutar: php migrations/2026_03_17_diagnostico_sesiones.php
 *
 * Lee en modo SOLO LECTURA — no modifica nada.
 */

$env = $argv[1] ?? 'local';

if ($env === 'pro') {
    $config = [
        'host'     => 'db-mysql-cycloid-do-user-18794030-0.h.db.ondigitalocean.com',
        'port'     => 25060,
        'username' => 'cycloid_userdb',
        'password' => getenv('DB_PASSWORD') ?: '',
        'database' => 'kpicycloid',
    ];
    $dsn  = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $opts = [
        PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT              => 60,
        PDO::MYSQL_ATTR_SSL_CA         => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
} else {
    $config = [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'username' => 'root',
        'password' => '',
        'database' => 'kpicycloid',
    ];
    $dsn  = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
}

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $opts);
    echo "Conectado a [{$env}]: {$config['database']}\n\n";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// ─── 1. Verificar que la tabla existe ──────────────────────────────────────
echo "=== 1. EXISTENCIA DE TABLA ===\n";
$existe = $pdo->query("SHOW TABLES LIKE 'sesiones_usuario'")->fetchColumn();
if (!$existe) {
    die("ERROR: La tabla 'sesiones_usuario' NO EXISTE en [{$env}].\n");
}
echo "Tabla sesiones_usuario: EXISTE\n\n";

// ─── 2. Estructura de la tabla ─────────────────────────────────────────────
echo "=== 2. ESTRUCTURA ===\n";
$cols = $pdo->query("DESCRIBE sesiones_usuario")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    printf("  %-25s %-20s %s\n", $c['Field'], $c['Type'], $c['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
}
echo "\n";

// ─── 3. Conteos generales ──────────────────────────────────────────────────
echo "=== 3. CONTEOS GENERALES ===\n";
$row = $pdo->query("
    SELECT
        COUNT(*)                                             AS total,
        SUM(activa = 1)                                      AS activas,
        SUM(activa = 0)                                      AS cerradas,
        SUM(activa = 1 AND fecha_fin IS NOT NULL)            AS activas_con_fecha_fin,
        SUM(activa = 0 AND fecha_fin IS NULL)                AS cerradas_sin_fecha_fin,
        SUM(fecha_fin IS NULL AND activa = 0)                AS anomalia_cerrada_sin_fin,
        SUM(fecha_inicio > fecha_fin)                        AS inicio_mayor_fin,
        SUM(fecha_ultimo_latido < fecha_inicio)              AS latido_antes_inicio,
        SUM(TIMESTAMPDIFF(HOUR, fecha_inicio, IFNULL(fecha_fin, NOW())) > 24)
                                                             AS duracion_mayor_24h,
        MIN(fecha_inicio)                                    AS primera_sesion,
        MAX(fecha_inicio)                                    AS ultima_sesion,
        COUNT(DISTINCT id_usuario)                           AS usuarios_distintos
    FROM sesiones_usuario
")->fetch(PDO::FETCH_ASSOC);

foreach ($row as $k => $v) {
    printf("  %-40s %s\n", $k . ':', $v ?? 'NULL');
}
echo "\n";

// ─── 4. Sesiones activas sospechosas (más de 10h sin latido) ───────────────
echo "=== 4. SESIONES ACTIVAS SOSPECHOSAS (activa=1, sin latido > 10 min) ===\n";
$stmt = $pdo->query("
    SELECT
        id_sesion,
        id_usuario,
        fecha_inicio,
        fecha_ultimo_latido,
        fecha_fin,
        TIMESTAMPDIFF(MINUTE, fecha_ultimo_latido, NOW()) AS minutos_sin_latido,
        TIMESTAMPDIFF(HOUR,   fecha_inicio, NOW())        AS horas_desde_inicio
    FROM sesiones_usuario
    WHERE activa = 1
      AND fecha_ultimo_latido < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ORDER BY fecha_ultimo_latido ASC
    LIMIT 30
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "  Ninguna.\n";
} else {
    printf("  %-8s %-10s %-20s %-20s %-10s %-10s\n",
        'id_ses', 'id_usu', 'fecha_inicio', 'ult_latido', 'min_sin_lat', 'h_desde_ini');
    foreach ($rows as $r) {
        printf("  %-8s %-10s %-20s %-20s %-11s %-10s\n",
            $r['id_sesion'], $r['id_usuario'],
            $r['fecha_inicio'], $r['fecha_ultimo_latido'],
            $r['minutos_sin_latido'], $r['horas_desde_inicio']);
    }
    echo "  Total sospechosas: " . count($rows) . "\n";
}
echo "\n";

// ─── 5. Sesiones con duración negativa o absurda ──────────────────────────
echo "=== 5. SESIONES CON DURACIÓN INVÁLIDA ===\n";
$stmt = $pdo->query("
    SELECT
        id_sesion, id_usuario, fecha_inicio, fecha_fin,
        TIMESTAMPDIFF(SECOND, fecha_inicio, fecha_fin) AS dur_seg
    FROM sesiones_usuario
    WHERE fecha_fin IS NOT NULL
      AND (fecha_fin < fecha_inicio OR TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) > 24)
    ORDER BY dur_seg ASC
    LIMIT 20
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "  Ninguna.\n";
} else {
    foreach ($rows as $r) {
        printf("  id=%s  usuario=%s  inicio=%s  fin=%s  dur=%ss\n",
            $r['id_sesion'], $r['id_usuario'],
            $r['fecha_inicio'], $r['fecha_fin'], $r['dur_seg']);
    }
}
echo "\n";

// ─── 6. Usuarios con múltiples sesiones activas simultáneas ───────────────
echo "=== 6. USUARIOS CON > 1 SESIÓN ACTIVA (no debería ocurrir) ===\n";
$stmt = $pdo->query("
    SELECT id_usuario, COUNT(*) AS sesiones_activas, GROUP_CONCAT(id_sesion) AS ids
    FROM sesiones_usuario
    WHERE activa = 1
    GROUP BY id_usuario
    HAVING sesiones_activas > 1
    ORDER BY sesiones_activas DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "  Ninguno.\n";
} else {
    foreach ($rows as $r) {
        echo "  usuario={$r['id_usuario']}  sesiones_activas={$r['sesiones_activas']}  ids=[{$r['ids']}]\n";
    }
}
echo "\n";

// ─── 7. Últimas 20 sesiones (cualquier estado) ─────────────────────────────
echo "=== 7. ÚLTIMAS 20 SESIONES ===\n";
$stmt = $pdo->query("
    SELECT
        s.id_sesion,
        s.id_usuario,
        u.nombre_completo,
        s.fecha_inicio,
        s.fecha_fin,
        s.fecha_ultimo_latido,
        s.activa,
        CASE
            WHEN s.fecha_fin IS NOT NULL THEN TIMESTAMPDIFF(SECOND, s.fecha_inicio, s.fecha_fin)
            ELSE TIMESTAMPDIFF(SECOND, s.fecha_inicio, s.fecha_ultimo_latido)
        END AS dur_seg
    FROM sesiones_usuario s
    LEFT JOIN users u ON u.id_users = s.id_usuario
    ORDER BY s.fecha_inicio DESC
    LIMIT 20
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
printf("  %-6s %-6s %-25s %-20s %-20s %-6s %-8s\n",
    'id', 'usu', 'nombre', 'inicio', 'fin/latido', 'activa', 'dur_min');
foreach ($rows as $r) {
    $finOLatido = $r['fecha_fin'] ?? $r['fecha_ultimo_latido'] ?? '-';
    printf("  %-6s %-6s %-25s %-20s %-20s %-6s %-8s\n",
        $r['id_sesion'], $r['id_usuario'],
        substr($r['nombre_completo'] ?? '?', 0, 24),
        $r['fecha_inicio'],
        $finOLatido,
        $r['activa'],
        round(($r['dur_seg'] ?? 0) / 60, 1));
}
echo "\n";

// ─── 8. Verificar vistas ──────────────────────────────────────────────────
echo "=== 8. VISTAS REQUERIDAS ===\n";
$vistas = ['vw_sesiones_usuario', 'vw_resumen_uso_usuario'];
foreach ($vistas as $v) {
    $existe = $pdo->query("SHOW FULL TABLES WHERE Tables_in_{$config['database']} = '$v' AND Table_type = 'VIEW'")->fetchColumn();
    echo "  $v: " . ($existe ? "OK" : "NO EXISTE") . "\n";

    if ($existe) {
        // Mostrar definición breve
        $def = $pdo->query("SHOW CREATE VIEW `$v`")->fetch(PDO::FETCH_ASSOC);
        $sql = $def["Create View"] ?? '';
        // Solo primeras 300 chars para no saturar
        echo "    " . substr($sql, 0, 400) . "...\n";
    }
}
echo "\n";

echo "=== DIAGNÓSTICO COMPLETADO ===\n";
