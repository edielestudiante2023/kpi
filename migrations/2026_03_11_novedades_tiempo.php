<?php

/**
 * Migración: Módulo de Novedades de Tiempo
 *
 * - Tabla novedades_colectivas (reducciones de jornada para todos)
 * - Tabla novedades_individuales (reducciones por persona)
 *
 * Uso:  php migrations/2026_03_11_novedades_tiempo.php
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
// 1. Novedades Colectivas (Fechas Especiales)
// ==========================================
$pdo->exec("
    CREATE TABLE IF NOT EXISTS novedades_colectivas (
        id_novedad_colectiva INT AUTO_INCREMENT PRIMARY KEY,
        fecha                DATE NOT NULL,
        descripcion          VARCHAR(255) NOT NULL,
        horas_reduccion      DECIMAL(4,2) NOT NULL COMMENT 'Horas que se restan (base jornada completa 8h)',
        anio                 INT NOT NULL,
        created_by           INT NULL,
        created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Tabla novedades_colectivas creada/verificada.\n";

// ==========================================
// 2. Novedades Individuales
// ==========================================
$pdo->exec("
    CREATE TABLE IF NOT EXISTS novedades_individuales (
        id_novedad_individual INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario            INT NOT NULL,
        fecha                 DATE NOT NULL,
        horas_reduccion       DECIMAL(4,2) NOT NULL COMMENT 'Horas exactas que se restan a esa persona',
        motivo                VARCHAR(255) NOT NULL,
        created_by            INT NULL,
        created_at            DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_usuario_fecha (id_usuario, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Tabla novedades_individuales creada/verificada.\n";

echo "\n¡Migración completada!\n";
