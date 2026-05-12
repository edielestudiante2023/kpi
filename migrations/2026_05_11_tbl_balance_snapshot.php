<?php
/**
 * Migración: Tabla tbl_balance_snapshot
 * Estado de la empresa congelado por fecha de corte (cierre mensual).
 * Diseñado para alimentar análisis financieros vía agente IA.
 *
 * Uso:  php migrations/2026_05_11_tbl_balance_snapshot.php [local|production]
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

echo "=== Migración tbl_balance_snapshot — entorno: {$env} ===\n\n";

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
    'name' => 'Crear tabla tbl_balance_snapshot',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_balance_snapshot'",
    'sql' => "CREATE TABLE tbl_balance_snapshot (
        id_snapshot INT AUTO_INCREMENT PRIMARY KEY,
        fecha_corte DATE NOT NULL,
        cartera_sst DECIMAL(15,2) NOT NULL DEFAULT 0,
        cartera_rps DECIMAL(15,2) NOT NULL DEFAULT 0,
        saldo_banco_sst DECIMAL(15,2) NOT NULL DEFAULT 0,
        saldo_banco_rps DECIMAL(15,2) NOT NULL DEFAULT 0,
        total_activos DECIMAL(15,2) NOT NULL DEFAULT 0,
        total_pasivos DECIMAL(15,2) NOT NULL DEFAULT 0,
        estado_empresa DECIMAL(15,2) NOT NULL DEFAULT 0,
        detalle_pasivos JSON NULL,
        notas TEXT NULL,
        creado_por VARCHAR(100) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_fecha_corte (fecha_corte),
        KEY idx_fecha (fecha_corte)
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
