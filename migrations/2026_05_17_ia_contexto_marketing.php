<?php

/**
 * Migración: extiende el ENUM `contexto` de tbl_ia_conversacion para incluir
 * el modo 'marketing' (OTTO Coach de Marketing para Solangel).
 *
 *   ENUM('financiero','comercial') -> ENUM('financiero','comercial','marketing')
 *
 * Idempotente: solo aplica el ALTER si 'marketing' no está aún en el ENUM.
 *
 * Uso:  php migrations/2026_05_17_ia_contexto_marketing.php
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

$db = $config['database'];

echo "1. ENUM tbl_ia_conversacion.contexto agregar 'marketing'... ";
$stmt = $pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'tbl_ia_conversacion' AND COLUMN_NAME = 'contexto'");
$tipoActual = (string) $stmt->fetchColumn();

if (strpos($tipoActual, 'marketing') !== false) {
    echo "[YA ESTA]\n";
} else {
    $pdo->exec("ALTER TABLE tbl_ia_conversacion MODIFY COLUMN contexto ENUM('financiero','comercial','marketing') NOT NULL DEFAULT 'financiero'");
    echo "[AMPLIADO]\n";
}

echo "\n=== MIGRACION COMPLETADA ===\n";
