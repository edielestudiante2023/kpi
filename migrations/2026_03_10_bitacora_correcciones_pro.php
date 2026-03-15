<?php

/**
 * Migración PRO: Tabla bitacora_correcciones
 * Ejecutar: php migrations/2026_03_10_bitacora_correcciones_pro.php
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
    echo "Conectado a PRO: {$config['database']}\n";
} catch (PDOException $e) {
    die("Error de conexión PRO: " . $e->getMessage() . "\n");
}

echo "Creando bitacora_correcciones... ";
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
echo "OK\n";

$tables = $pdo->query("SHOW TABLES LIKE 'bitacora_correcciones'")->fetchAll(PDO::FETCH_COLUMN);
echo "Verificación: " . implode(', ', $tables) . "\n";
echo "\nMigración PRO completada!\n";
