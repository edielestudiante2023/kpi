<?php

/**
 * Corrección: El script anterior modificó la actividad equivocada.
 * - Debe revertir la actividad #4 del 22 feb (fue modificada por error)
 * - Debe corregir la actividad #3 del 21 feb a las 5:00 PM
 *
 * Uso:  php migrations/2026_02_23_corregir_actividades_edison.php
 */

function leerEnv(string $path): array {
    $vars = [];
    if (!file_exists($path)) return $vars;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = array_map('trim', explode('=', $line, 2));
        $val = trim($val, "\"' ");
        $vars[$key] = $val;
    }
    return $vars;
}

$dotenv = leerEnv(__DIR__ . '/../.env');
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
echo "Conectado a: {$config['host']}\n\n";

// Buscar Edison
$stmt = $pdo->prepare("SELECT id_users, nombre_completo FROM users WHERE nombre_completo LIKE '%Edison%' LIMIT 1");
$stmt->execute();
$edison = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Usuario: {$edison['nombre_completo']} (ID: {$edison['id_users']})\n\n";

// ── Mostrar actividades del 21 y 22 de febrero ───────────
foreach (['2026-02-21', '2026-02-22'] as $fecha) {
    $stmt = $pdo->prepare("
        SELECT ba.*, cc.nombre AS centro_costo
        FROM bitacora_actividades ba
        LEFT JOIN centros_costo cc ON ba.id_centro_costo = cc.id_centro_costo
        WHERE ba.id_usuario = :id AND ba.fecha = :fecha
        ORDER BY ba.numero_actividad ASC
    ");
    $stmt->execute(['id' => $edison['id_users'], 'fecha' => $fecha]);
    $acts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== $fecha ===\n";
    foreach ($acts as $a) {
        $desc = mb_substr($a['descripcion'], 0, 45);
        echo "  id_bitacora={$a['id_bitacora']} | #{$a['numero_actividad']} | {$desc}\n";
        echo "    Inicio: {$a['hora_inicio']} | Fin: " . ($a['hora_fin'] ?? 'NULL') . " | {$a['duracion_minutos']} min | {$a['estado']}\n";
    }
    if (empty($acts)) echo "  (sin actividades)\n";
    echo "\n";
}

// ── CORRECCIÓN 1: Actividad #3 del 21 feb → hora_fin 17:00 ──
echo "--- CORRECCIONES ---\n\n";

$stmt = $pdo->prepare("
    SELECT * FROM bitacora_actividades
    WHERE id_usuario = :id AND fecha = '2026-02-21' AND numero_actividad = 3
");
$stmt->execute(['id' => $edison['id_users']]);
$act21 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($act21) {
    $horaFin21 = '2026-02-21 17:00:00';
    $inicio21 = new DateTime($act21['hora_inicio']);
    $fin21 = new DateTime($horaFin21);
    $dur21 = round(($fin21->getTimestamp() - $inicio21->getTimestamp()) / 60, 2);

    echo "1. Act #3 del 21 feb (id={$act21['id_bitacora']}): {$act21['hora_fin']} → $horaFin21 ($dur21 min)\n";

    $stmt = $pdo->prepare("UPDATE bitacora_actividades SET hora_fin = :fin, duracion_minutos = :dur, estado = 'finalizada' WHERE id_bitacora = :id");
    $stmt->execute(['fin' => $horaFin21, 'dur' => $dur21, 'id' => $act21['id_bitacora']]);
    echo "   [OK]\n\n";
} else {
    echo "1. Act #3 del 21 feb: NO ENCONTRADA\n\n";
}

// ── CORRECCIÓN 2: Actividad #4 del 22 feb → revertir hora_fin incorrecta ──
$stmt = $pdo->prepare("
    SELECT * FROM bitacora_actividades
    WHERE id_usuario = :id AND fecha = '2026-02-22' AND numero_actividad = 4
");
$stmt->execute(['id' => $edison['id_users']]);
$act22 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($act22) {
    // La actividad #4 del 22 feb fue modificada por error.
    // Su hora_fin actual es 2026-02-22 17:00:00 (incorrecta).
    // Necesitamos saber cuál era la hora_fin real.
    // Como no la sabemos, mostramos el estado actual para que el usuario confirme.
    echo "2. Act #4 del 22 feb (id={$act22['id_bitacora']}):\n";
    echo "   Inicio: {$act22['hora_inicio']}\n";
    echo "   Fin actual (incorrecto): {$act22['hora_fin']}\n";
    echo "   Duración actual: {$act22['duracion_minutos']} min\n";

    // Si la hora_fin (17:00) es ANTERIOR a hora_inicio, es claramente un error
    $inicio22 = new DateTime($act22['hora_inicio']);
    $finActual = new DateTime($act22['hora_fin']);

    if ($finActual < $inicio22) {
        // El fin es antes del inicio → el script anterior la dañó
        // Recalcular: ¿la actividad estaba finalizada antes? No sabemos la hora real.
        // Opción: usar la hora de inicio de la siguiente acción o pedir al usuario.
        echo "   [DATO] hora_fin es ANTES de hora_inicio → claramente dañada por el script\n";
    }

    // Buscar si hay actividad siguiente para estimar la hora fin
    $stmt = $pdo->prepare("
        SELECT hora_inicio FROM bitacora_actividades
        WHERE id_usuario = :id AND fecha = '2026-02-22' AND numero_actividad > :num
        ORDER BY numero_actividad ASC LIMIT 1
    ");
    $stmt->execute(['id' => $edison['id_users'], 'num' => $act22['numero_actividad']]);
    $next = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($next) {
        echo "   Siguiente actividad inició a: {$next['hora_inicio']}\n";
    } else {
        echo "   No hay actividad siguiente.\n";
    }

    // Si no hay actividad siguiente y era la última, probablemente terminó normalmente
    // Pero no sabemos la hora real. Dejamos esto para que el usuario confirme.
    echo "   ¿Cuál debería ser la hora_fin correcta? (requiere input del usuario)\n";
} else {
    echo "2. Act #4 del 22 feb: NO ENCONTRADA\n";
}

echo "\n=== FIN ===\n";
