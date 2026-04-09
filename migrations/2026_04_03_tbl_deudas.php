<?php
/**
 * Migración: tbl_deudas + tbl_deuda_abonos
 * Módulo de Conciliaciones — Tracking de deudas/obligaciones
 *
 * Uso:  php migrations/2026_04_03_tbl_deudas.php [local|production]
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

echo "=== Migración tbl_deudas — entorno: {$env} ===\n\n";

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

// --- Paso 1: tbl_deudas ---
$steps[] = [
    'name' => 'Crear tabla tbl_deudas',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_deudas'",
    'sql' => "CREATE TABLE tbl_deudas (
        id_deuda INT AUTO_INCREMENT PRIMARY KEY,
        concepto VARCHAR(255) NOT NULL,
        acreedor VARCHAR(255) NOT NULL,
        monto_original DECIMAL(15,2) NOT NULL,
        fecha_registro DATE NOT NULL,
        fecha_vencimiento DATE NULL,
        estado ENUM('activa', 'saldada') NOT NULL DEFAULT 'activa',
        notas TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_estado (estado),
        KEY idx_acreedor (acreedor),
        KEY idx_fecha_vencimiento (fecha_vencimiento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// --- Paso 2: tbl_deuda_abonos ---
$steps[] = [
    'name' => 'Crear tabla tbl_deuda_abonos',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_deuda_abonos'",
    'sql' => "CREATE TABLE tbl_deuda_abonos (
        id_abono INT AUTO_INCREMENT PRIMARY KEY,
        id_deuda INT NOT NULL,
        fecha_abono DATE NOT NULL,
        valor_abono DECIMAL(15,2) NOT NULL,
        referencia VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_deuda (id_deuda),
        KEY idx_fecha (fecha_abono),
        CONSTRAINT fk_abono_deuda FOREIGN KEY (id_deuda) REFERENCES tbl_deudas(id_deuda) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// Ejecutar pasos
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
