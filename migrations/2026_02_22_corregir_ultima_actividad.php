<?php

/**
 * Corrección: Última actividad de Edison terminó a las 5:00 PM, no 7:27 PM
 *
 * Uso:  php migrations/2026_02_22_corregir_ultima_actividad.php
 */

// ── Leer .env manualmente (compatible con formato CI4) ────
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

// Buscar última actividad registrada
$stmt = $pdo->prepare("
    SELECT ba.*, cc.nombre AS centro_costo
    FROM bitacora_actividades ba
    LEFT JOIN centros_costo cc ON ba.id_centro_costo = cc.id_centro_costo
    WHERE ba.id_usuario = :id
    ORDER BY ba.id_bitacora DESC
    LIMIT 1
");
$stmt->execute(['id' => $edison['id_users']]);
$act = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$act) {
    echo "[ERROR] No se encontraron actividades para Edison.\n";
    exit(1);
}

echo "Última actividad:\n";
echo "  #{$act['numero_actividad']} - {$act['descripcion']}\n";
echo "  Fecha: {$act['fecha']}\n";
echo "  Inicio: {$act['hora_inicio']}\n";
echo "  Fin actual: {$act['hora_fin']}\n";
echo "  Duración actual: {$act['duracion_minutos']} min\n\n";

// Corregir a las 5:00 PM del mismo día
$horaFinCorrecta = $act['fecha'] . ' 17:00:00';
$inicio = new DateTime($act['hora_inicio']);
$fin = new DateTime($horaFinCorrecta);
$diffMinutos = round(($fin->getTimestamp() - $inicio->getTimestamp()) / 60, 2);

echo ">>> Corrección:\n";
echo "    hora_fin:         $horaFinCorrecta\n";
echo "    duracion_minutos: $diffMinutos\n";

$stmt = $pdo->prepare("
    UPDATE bitacora_actividades
    SET hora_fin = :hora_fin, duracion_minutos = :duracion, estado = 'finalizada'
    WHERE id_bitacora = :id
");
$stmt->execute([
    'hora_fin' => $horaFinCorrecta,
    'duracion' => $diffMinutos,
    'id'       => $act['id_bitacora'],
]);

$horas = floor($diffMinutos / 60);
$mins = round(fmod($diffMinutos, 60));
echo "\n[OK] Corregida. Duración real: {$horas}h {$mins}min\n";
