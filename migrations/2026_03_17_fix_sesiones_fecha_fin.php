<?php

/**
 * Fix: Corregir fecha_fin en sesiones donde se usó NOW() al hacer login
 * en lugar de fecha_ultimo_latido.
 *
 * CAUSA RAÍZ:
 *   cerrarSesionesUsuario() hacía: fecha_fin = NOW()
 *   pero el último latido pudo haber sido días antes → sesiones con 100h+ de duración falsa.
 *
 * CORRECCIÓN:
 *   Para sesiones cerradas (activa=0) donde fecha_fin > fecha_ultimo_latido + 10 min,
 *   reemplazar fecha_fin = fecha_ultimo_latido.
 *
 * NO toca sesiones activas ni sesiones cerradas por logout real (diferencia <= 10 min).
 *
 * Ejecutar: php migrations/2026_03_17_fix_sesiones_fecha_fin.php [local|pro]
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

// ─── Fase 1: Identificar sesiones afectadas ────────────────────────────────
echo "=== FASE 1: Identificar sesiones a corregir ===\n";

$stmt = $pdo->query("
    SELECT
        id_sesion,
        id_usuario,
        fecha_inicio,
        fecha_ultimo_latido,
        fecha_fin,
        TIMESTAMPDIFF(MINUTE, fecha_ultimo_latido, fecha_fin) AS minutos_diferencia,
        TIMESTAMPDIFF(HOUR,   fecha_inicio,         fecha_fin) AS horas_dur_actual,
        TIMESTAMPDIFF(HOUR,   fecha_inicio, fecha_ultimo_latido) AS horas_dur_real
    FROM sesiones_usuario
    WHERE activa = 0
      AND fecha_fin IS NOT NULL
      AND fecha_ultimo_latido IS NOT NULL
      AND TIMESTAMPDIFF(MINUTE, fecha_ultimo_latido, fecha_fin) > 10
    ORDER BY minutos_diferencia DESC
");

$sesiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($sesiones)) {
    echo "  No hay sesiones que corregir.\n\n";
} else {
    echo "  Se encontraron " . count($sesiones) . " sesiones con fecha_fin inflada:\n\n";
    printf("  %-8s %-6s %-20s %-20s %-20s %-12s %-10s %-10s\n",
        'id_ses', 'usu', 'inicio', 'ult_latido', 'fin_actual', 'min_diferencia', 'h_actual', 'h_real');
    foreach ($sesiones as $r) {
        printf("  %-8s %-6s %-20s %-20s %-20s %-14s %-10s %-10s\n",
            $r['id_sesion'], $r['id_usuario'],
            $r['fecha_inicio'], $r['fecha_ultimo_latido'],
            $r['fecha_fin'], $r['minutos_diferencia'],
            $r['horas_dur_actual'], $r['horas_dur_real']);
    }
}
echo "\n";

// ─── Fase 2: Confirmar antes de proceder ──────────────────────────────────
if (empty($sesiones)) {
    echo "Nada que corregir. Script finalizado.\n";
    exit(0);
}

echo "¿Aplicar corrección? Se cambiará fecha_fin = fecha_ultimo_latido para las " . count($sesiones) . " sesiones. [s/N]: ";
$respuesta = trim(fgets(STDIN));

if (strtolower($respuesta) !== 's') {
    echo "Cancelado por el usuario.\n";
    exit(0);
}

// ─── Fase 3: Backup antes de corregir ────────────────────────────────────
echo "\n=== FASE 3: Backup parcial (sesiones afectadas) ===\n";
$ids = implode(',', array_column($sesiones, 'id_sesion'));
$backup = $pdo->query("
    SELECT id_sesion, fecha_fin AS fecha_fin_original
    FROM sesiones_usuario
    WHERE id_sesion IN ($ids)
")->fetchAll(PDO::FETCH_ASSOC);

echo "  Backup de " . count($backup) . " registros capturado en memoria.\n\n";

// ─── Fase 4: Aplicar corrección ───────────────────────────────────────────
echo "=== FASE 4: Aplicando corrección ===\n";

$pdo->beginTransaction();

try {
    $updateStmt = $pdo->prepare("
        UPDATE sesiones_usuario
        SET fecha_fin = fecha_ultimo_latido
        WHERE id_sesion = :id_sesion
          AND activa = 0
          AND TIMESTAMPDIFF(MINUTE, fecha_ultimo_latido, fecha_fin) > 10
    ");

    $actualizados = 0;
    foreach ($sesiones as $s) {
        $updateStmt->execute([':id_sesion' => $s['id_sesion']]);
        $actualizados += $updateStmt->rowCount();
        echo "  [OK] id_sesion={$s['id_sesion']}  fecha_fin: {$s['fecha_fin']} → {$s['fecha_ultimo_latido']}\n";
    }

    $pdo->commit();
    echo "\n  Transacción confirmada. Filas actualizadas: $actualizados\n\n";

} catch (Exception $e) {
    $pdo->rollBack();
    die("ERROR — Rollback aplicado: " . $e->getMessage() . "\n");
}

// ─── Fase 5: Verificación post-corrección ────────────────────────────────
echo "=== FASE 5: Verificación ===\n";

$stmt = $pdo->query("
    SELECT COUNT(*) AS restantes
    FROM sesiones_usuario
    WHERE activa = 0
      AND fecha_fin IS NOT NULL
      AND fecha_ultimo_latido IS NOT NULL
      AND TIMESTAMPDIFF(MINUTE, fecha_ultimo_latido, fecha_fin) > 10
");
$restantes = $stmt->fetchColumn();
echo "  Sesiones aún con fecha_fin inflada (debe ser 0): $restantes\n";

$stmt = $pdo->query("
    SELECT COUNT(*) AS mayores24h
    FROM sesiones_usuario
    WHERE TIMESTAMPDIFF(HOUR, fecha_inicio, IFNULL(fecha_fin, fecha_ultimo_latido)) > 24
");
echo "  Sesiones con duración > 24h tras corrección: " . $stmt->fetchColumn() . "\n\n";

echo "=== CORRECCIÓN COMPLETADA ===\n";
