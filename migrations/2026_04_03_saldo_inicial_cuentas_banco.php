<?php
/**
 * Migración: Agregar saldo_inicial y fecha_saldo_inicial a tbl_cuentas_banco
 *
 * Uso:  php migrations/2026_04_03_saldo_inicial_cuentas_banco.php [local|production]
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

echo "=== Migración saldo_inicial cuentas banco — entorno: {$env} ===\n\n";

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
    'name' => 'Agregar columna saldo_inicial a tbl_cuentas_banco',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_cuentas_banco' AND COLUMN_NAME = 'saldo_inicial'",
    'sql' => "ALTER TABLE tbl_cuentas_banco ADD COLUMN saldo_inicial DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER nombre_cuenta",
];

$steps[] = [
    'name' => 'Agregar columna fecha_saldo_inicial a tbl_cuentas_banco',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_cuentas_banco' AND COLUMN_NAME = 'fecha_saldo_inicial'",
    'sql' => "ALTER TABLE tbl_cuentas_banco ADD COLUMN fecha_saldo_inicial DATE NULL AFTER saldo_inicial",
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
