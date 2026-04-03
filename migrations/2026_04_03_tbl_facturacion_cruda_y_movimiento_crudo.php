<?php
/**
 * Migración: tbl_facturacion_cruda + tbl_movimiento_bancario_crudo
 * Módulo de Conciliaciones — Tablas de carga operativa
 *
 * Uso:  php migrations/2026_04_03_tbl_facturacion_cruda_y_movimiento_crudo.php [local|production]
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

echo "=== Migración tablas crudas — entorno: {$env} ===\n\n";

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

// --- Paso 1: tbl_facturacion_cruda ---
$steps[] = [
    'name' => 'Crear tabla tbl_facturacion_cruda',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_facturacion_cruda'",
    'sql' => "CREATE TABLE tbl_facturacion_cruda (
        id_facturacion_cruda BIGINT AUTO_INCREMENT PRIMARY KEY,
        comprobante VARCHAR(50) NOT NULL,
        fecha_elaboracion DATE NOT NULL,
        identificacion BIGINT NOT NULL COMMENT 'NIT del cliente',
        sucursal VARCHAR(50) NULL,
        nombre_tercero VARCHAR(255) NOT NULL,
        base_gravada DECIMAL(15,2) NOT NULL DEFAULT 0,
        base_exenta DECIMAL(15,2) NOT NULL DEFAULT 0,
        iva DECIMAL(15,2) NOT NULL DEFAULT 0,
        impoconsumo DECIMAL(15,2) NOT NULL DEFAULT 0,
        ad_valorem DECIMAL(15,2) NOT NULL DEFAULT 0,
        cargo_en_totales DECIMAL(15,2) NOT NULL DEFAULT 0,
        descuento_en_totales DECIMAL(15,2) NOT NULL DEFAULT 0,
        total DECIMAL(15,2) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_comprobante (comprobante),
        KEY idx_fecha (fecha_elaboracion),
        KEY idx_identificacion (identificacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// --- Paso 2: tbl_movimiento_bancario_crudo ---
$steps[] = [
    'name' => 'Crear tabla tbl_movimiento_bancario_crudo',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_movimiento_bancario_crudo'",
    'sql' => "CREATE TABLE tbl_movimiento_bancario_crudo (
        id_movimiento_crudo BIGINT AUTO_INCREMENT PRIMARY KEY,
        id_cuenta_banco INT NOT NULL,
        fecha_sistema DATE NOT NULL,
        documento VARCHAR(50) NULL,
        descripcion_motivo VARCHAR(500) NULL,
        transaccion VARCHAR(50) NULL COMMENT 'Nota Debito, Nota Credito, Deposito Especial',
        oficina_recaudo VARCHAR(100) NULL,
        id_origen_destino VARCHAR(50) NULL,
        valor_cheque DECIMAL(15,2) NULL DEFAULT 0,
        valor_total DECIMAL(15,2) NOT NULL,
        referencia_1 VARCHAR(50) NULL,
        referencia_2 VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_cuenta (id_cuenta_banco),
        KEY idx_fecha (fecha_sistema),
        KEY idx_transaccion (transaccion),
        CONSTRAINT fk_mov_crudo_cuenta FOREIGN KEY (id_cuenta_banco) REFERENCES tbl_cuentas_banco(id_cuenta_banco)
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
