<?php

/**
 * Migración PRO: Novedades de Tiempo
 * Ejecutar: php migrations/2026_03_11_novedades_tiempo_pro.php
 */

$config = [
    'host'     => 'db-mysql-cycloid-do-user-18794030-0.h.db.ondigitalocean.com',
    'port'     => 25060,
    'username' => 'cycloid_userdb',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => 'kpicycloid',
];

$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 60,
    PDO::MYSQL_ATTR_SSL_CA => true,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
];

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $opts);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    echo "Conectado a PRO: {$config['database']}\n";
} catch (PDOException $e) {
    die("Error de conexión PRO: " . $e->getMessage() . "\n");
}

// 1. Novedades Colectivas
echo "Verificando novedades_colectivas... ";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS novedades_colectivas (
        id_novedad_colectiva INT AUTO_INCREMENT PRIMARY KEY,
        fecha                DATE NOT NULL,
        descripcion          VARCHAR(255) NOT NULL,
        horas_reduccion      DECIMAL(4,2) NOT NULL,
        anio                 INT NOT NULL,
        created_by           INT NULL,
        created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "OK\n";

// 2. Novedades Individuales
echo "Verificando novedades_individuales... ";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS novedades_individuales (
        id_novedad_individual INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario            INT NOT NULL,
        fecha                 DATE NOT NULL,
        horas_reduccion       DECIMAL(4,2) NOT NULL,
        motivo                VARCHAR(255) NOT NULL,
        created_by            INT NULL,
        created_at            DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_usuario_fecha (id_usuario, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "OK\n";

// Verificar
$tables = $pdo->query("SHOW TABLES LIKE 'novedades%'")->fetchAll(PDO::FETCH_COLUMN);
echo "\nTablas creadas: " . implode(', ', $tables) . "\n";
echo "\nMigración PRO completada!\n";
