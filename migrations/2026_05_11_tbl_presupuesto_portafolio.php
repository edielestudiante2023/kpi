<?php
/**
 * Migración: Tabla tbl_presupuesto_portafolio
 * Módulo de Conciliaciones — Presupuesto mensual por portafolio
 *
 * Uso:  php migrations/2026_05_11_tbl_presupuesto_portafolio.php [local|production]
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

echo "=== Migración tbl_presupuesto_portafolio — entorno: {$env} ===\n\n";

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

// Resolver IDs reales de portafolios SST y RPS
$idSST = (int) $pdo->query("SELECT id_portafolio FROM tbl_portafolios WHERE portafolio = 'SST' LIMIT 1")->fetchColumn();
$idRPS = (int) $pdo->query("SELECT id_portafolio FROM tbl_portafolios WHERE portafolio = 'RPS' LIMIT 1")->fetchColumn();

if (! $idSST || ! $idRPS) {
    echo "[ERROR] No se encontraron portafolios SST y/o RPS en tbl_portafolios.\n";
    exit(1);
}
echo "[INFO] id SST = {$idSST}, id RPS = {$idRPS}\n";

$steps = [];

// --- Paso 1: Crear tabla ---
$steps[] = [
    'name' => 'Crear tabla tbl_presupuesto_portafolio',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_presupuesto_portafolio'",
    'sql' => "CREATE TABLE tbl_presupuesto_portafolio (
        id_presupuesto INT AUTO_INCREMENT PRIMARY KEY,
        id_portafolio INT NOT NULL,
        anio SMALLINT NOT NULL,
        mes TINYINT NOT NULL,
        presupuesto DECIMAL(15,2) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_portafolio_anio_mes (id_portafolio, anio, mes),
        KEY idx_anio_mes (anio, mes),
        CONSTRAINT fk_presupuesto_portafolio FOREIGN KEY (id_portafolio) REFERENCES tbl_portafolios(id_portafolio) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// --- Paso 2: Seed inicial de presupuestos ---
// Estructura: [anio => presupuesto_por_mes]
$seedSST = [
    2024 => 16000000,
    2025 => 16000000,
    2026 => 21000000,
];
$seedRPS = [
    2024 => 5100000,
    2025 => 5000000,
    2026 => 10000000,
];

$valores = [];
foreach ($seedSST as $anio => $valor) {
    for ($mes = 1; $mes <= 12; $mes++) {
        $valores[] = "({$idSST}, {$anio}, {$mes}, {$valor})";
    }
}
foreach ($seedRPS as $anio => $valor) {
    for ($mes = 1; $mes <= 12; $mes++) {
        $valores[] = "({$idRPS}, {$anio}, {$mes}, {$valor})";
    }
}

$steps[] = [
    'name' => 'Insertar presupuestos iniciales (SST y RPS, 2024-2026)',
    'check' => "SELECT COUNT(*) FROM tbl_presupuesto_portafolio",
    'sql' => "INSERT INTO tbl_presupuesto_portafolio (id_portafolio, anio, mes, presupuesto) VALUES "
        . implode(', ', $valores),
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
