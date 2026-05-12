<?php
/**
 * Migración: Módulo Asesoría Financiera IA
 * Tablas tbl_ia_conversacion y tbl_ia_mensaje
 *
 * Uso:  php migrations/2026_05_12_tbl_ia_asesoria.php [local|production]
 */

$env = $argv[1] ?? 'local';

if ($env === 'production') {
    $dotenv = @parse_ini_file(__DIR__ . '/../.env.production');
    $config = [
        'host'     => $dotenv['DB_HOST'] ?? getenv('DB_HOST'),
        'port'     => $dotenv['DB_PORT'] ?? getenv('DB_PORT') ?: 25060,
        'username' => $dotenv['DB_USER'] ?? getenv('DB_USER'),
        'password' => $dotenv['DB_PASS'] ?? getenv('DB_PASS'),
        'database' => $dotenv['DB_NAME'] ?? getenv('DB_NAME') ?: 'kpicycloid',
    ];
} else {
    $config = [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'username' => 'root',
        'password' => '',
        'database' => 'kpicycloid',
    ];
}

echo "=== Migración módulo Asesoría IA — entorno: {$env} ===\n\n";

$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    echo "[OK] Conexión exitosa a {$env}\n";
} catch (PDOException $e) {
    echo "[ERROR] No se pudo conectar: " . $e->getMessage() . "\n";
    exit(1);
}

$steps = [];

$steps[] = [
    'name' => 'Crear tabla tbl_ia_conversacion',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_ia_conversacion'",
    'sql' => "CREATE TABLE tbl_ia_conversacion (
        id_conversacion INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        tipo ENUM('diagnostico','analisis_cierre','comparativo','cartera','estrategia','libre') NOT NULL,
        id_snapshot_ref INT NULL,
        creado_por VARCHAR(100) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_tipo (tipo),
        KEY idx_created (created_at),
        KEY idx_snapshot (id_snapshot_ref)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

$steps[] = [
    'name' => 'Crear tabla tbl_ia_mensaje',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_ia_mensaje'",
    'sql' => "CREATE TABLE tbl_ia_mensaje (
        id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
        id_conversacion INT NOT NULL,
        rol ENUM('user','assistant','tool') NOT NULL,
        contenido MEDIUMTEXT NULL,
        tool_calls JSON NULL,
        tokens_input INT DEFAULT 0,
        tokens_output INT DEFAULT 0,
        tokens_cache_read INT DEFAULT 0,
        tokens_cache_write INT DEFAULT 0,
        modelo VARCHAR(50) NULL,
        costo_usd DECIMAL(10,6) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_conversacion (id_conversacion),
        KEY idx_created (created_at),
        CONSTRAINT fk_msg_conv FOREIGN KEY (id_conversacion) REFERENCES tbl_ia_conversacion(id_conversacion) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

$allOk = true;
foreach ($steps as $i => $step) {
    $num = $i + 1;
    echo "\n--- Paso {$num}: {$step['name']} ---\n";

    $stmt = $pdo->query($step['check']);
    $exists = (int) $stmt->fetchColumn();

    if ($exists > 0) {
        echo "[SKIP] Ya existe, no se ejecuta.\n";
        continue;
    }

    try {
        $pdo->exec($step['sql']);
        echo "[OK] Ejecutado correctamente.\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        $allOk = false;
        break;
    }
}

echo "\n" . ($allOk ? "=== Migración completada con éxito ===" : "=== Migración falló ===") . "\n";
exit($allOk ? 0 : 1);
