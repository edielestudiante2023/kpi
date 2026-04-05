<?php

/**
 * Migración: Tabla dias_habiles_config
 * Almacena configuración manual de días hábiles por mes.
 *
 * Uso:  php migrations/2026_04_05_dias_habiles_config.php
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
    $opts[PDO::MYSQL_ATTR_SSL_CA] = '';
    $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $opts);
    echo "Conectado a {$config['database']}\n";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// Crear tabla
$pdo->exec("
    CREATE TABLE IF NOT EXISTS dias_habiles_config (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        anio            SMALLINT NOT NULL,
        mes             TINYINT NOT NULL,
        dia             TINYINT NOT NULL,
        created_by      INT UNSIGNED NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_fecha (anio, mes, dia),
        KEY idx_anio_mes (anio, mes)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Tabla dias_habiles_config creada/verificada.\n";

echo "\n¡Migración completada!\n";
