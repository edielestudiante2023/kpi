<?php
/**
 * Migración: Tabla maestra tbl_centros_costo
 * Módulo de Conciliaciones — Centros de costo para conciliación bancaria
 *
 * Uso:  php migrations/2026_04_03_tbl_centros_costo_conciliacion.php [local|production]
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

echo "=== Migración tbl_centros_costo — entorno: {$env} ===\n\n";

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

// --- Paso 1: Crear tabla ---
$steps[] = [
    'name' => 'Crear tabla tbl_centros_costo',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_centros_costo'",
    'sql' => "CREATE TABLE tbl_centros_costo (
        id_centro_costo INT AUTO_INCREMENT PRIMARY KEY,
        centro_costo VARCHAR(100) NOT NULL,
        UNIQUE KEY uk_centro_costo (centro_costo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// --- Paso 2: Insertar datos ---
$steps[] = [
    'name' => 'Insertar centros de costo iniciales',
    'check' => "SELECT COUNT(*) FROM tbl_centros_costo",
    'sql' => "INSERT INTO tbl_centros_costo (centro_costo) VALUES
        ('SST'),
        ('RPS'),
        ('HUNTING'),
        ('CREDITO'),
        ('FRAMEWORK'),
        ('GLADIATOR'),
        ('OTROS'),
        ('PLANILLA'),
        ('CHAMILO'),
        ('DEVOLUCION'),
        ('DEBITO'),
        ('BIENESTAR'),
        ('RECONSIGNACION')",
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
