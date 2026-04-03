<?php
/**
 * Migración: Tabla tbl_facturacion
 * Módulo de Conciliaciones — Historial de facturación
 *
 * Uso:  php migrations/2026_04_03_tbl_facturacion.php [local|production]
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

echo "=== Migración tbl_facturacion — entorno: {$env} ===\n\n";

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

// --- Paso 1: Crear tabla tbl_facturacion ---
$steps[] = [
    'name' => 'Crear tabla tbl_facturacion',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_facturacion'",
    'sql' => "CREATE TABLE tbl_facturacion (
        id_facturacion BIGINT AUTO_INCREMENT PRIMARY KEY,
        id_portafolio INT NOT NULL,
        semana INT NULL,
        fecha_pago DATE NULL,
        mes_pago INT NULL,
        valor_pagado DECIMAL(15,2) NULL,
        dif_facturado_pagado DECIMAL(15,2) NULL DEFAULT 0,
        valor_esperado_recaudo_iva DECIMAL(15,2) NULL DEFAULT 0,
        retencion_renta_4 DECIMAL(15,2) NULL DEFAULT 0,
        base_gravable_neta DECIMAL(15,2) NULL DEFAULT 0,
        pagado TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=SI, 0=NO',
        anio INT NOT NULL,
        mes INT NOT NULL,
        extrae VARCHAR(50) NULL,
        fecha_anticipo DATE NULL,
        anticipo DECIMAL(15,2) NULL DEFAULT 0,
        comprobante VARCHAR(50) NOT NULL,
        fecha_elaboracion DATE NOT NULL,
        identificacion BIGINT NOT NULL COMMENT 'NIT del cliente',
        sucursal VARCHAR(50) NULL,
        nombre_tercero VARCHAR(255) NOT NULL,
        base_gravada DECIMAL(15,2) NULL DEFAULT 0,
        base_exenta DECIMAL(15,2) NULL DEFAULT 0,
        iva DECIMAL(15,2) NULL DEFAULT 0,
        retefuente_4 DECIMAL(15,2) NULL DEFAULT 0,
        recompra TINYINT(1) NULL DEFAULT 0,
        cargo_en_totales DECIMAL(15,2) NULL DEFAULT 0,
        descuento_en_totales DECIMAL(15,2) NULL DEFAULT 0,
        total DECIMAL(15,2) NULL DEFAULT 0,
        vendedor VARCHAR(100) NULL,
        base_comisiones DECIMAL(15,2) NULL DEFAULT 0,
        numero_factura INT NULL,
        portafolio_detallado VARCHAR(100) NULL,
        fecha_vence DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_portafolio (id_portafolio),
        KEY idx_anio_mes (anio, mes),
        KEY idx_comprobante (comprobante),
        KEY idx_identificacion (identificacion),
        KEY idx_pagado (pagado),
        KEY idx_fecha_elaboracion (fecha_elaboracion),
        KEY idx_numero_factura (numero_factura),
        CONSTRAINT fk_fact_portafolio FOREIGN KEY (id_portafolio) REFERENCES tbl_portafolios(id_portafolio)
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
