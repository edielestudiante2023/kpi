<?php

/**
 * Migración: Módulo de Correcciones de Bitácora
 *
 * - Tabla bitacora_correcciones (solicitudes de corrección con token)
 *
 * Uso:  php migrations/2026_03_10_bitacora_correcciones.php
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

// ==========================================
// 1. Tabla bitacora_correcciones
// ==========================================
$pdo->exec("
    CREATE TABLE IF NOT EXISTS bitacora_correcciones (
        id_correccion      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_bitacora        INT UNSIGNED NOT NULL,
        id_usuario         INT UNSIGNED NOT NULL,
        campo              VARCHAR(50)  NOT NULL DEFAULT 'hora_fin',
        valor_anterior     VARCHAR(100) NOT NULL,
        valor_nuevo        VARCHAR(100) NOT NULL,
        motivo             TEXT NULL,
        token              VARCHAR(64)  NOT NULL,
        token_expira       DATETIME     NOT NULL,
        estado             ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
        aprobado_por       VARCHAR(255) NULL,
        fecha_resolucion   DATETIME NULL,
        created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_token (token),
        INDEX idx_estado (estado),
        INDEX idx_bitacora (id_bitacora)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Tabla bitacora_correcciones creada/verificada.\n";

echo "\n¡Migración completada!\n";
