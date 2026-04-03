<?php
/**
 * Migración: tbl_cuentas_banco + tbl_conciliacion_bancaria
 * Módulo de Conciliaciones
 *
 * Uso:  php migrations/2026_04_03_tbl_cuentas_banco_y_conciliacion.php [local|production]
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

echo "=== Migración cuentas banco + conciliación — entorno: {$env} ===\n\n";

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

// --- Paso 1: Tabla maestra cuentas de banco ---
$steps[] = [
    'name' => 'Crear tabla tbl_cuentas_banco',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_cuentas_banco'",
    'sql' => "CREATE TABLE tbl_cuentas_banco (
        id_cuenta_banco INT AUTO_INCREMENT PRIMARY KEY,
        nombre_cuenta VARCHAR(100) NOT NULL,
        UNIQUE KEY uk_nombre_cuenta (nombre_cuenta)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// --- Paso 2: Insertar cuentas ---
$steps[] = [
    'name' => 'Insertar cuentas de banco (SST, RPS)',
    'check' => "SELECT COUNT(*) FROM tbl_cuentas_banco",
    'sql' => "INSERT INTO tbl_cuentas_banco (id_cuenta_banco, nombre_cuenta) VALUES (1, 'SST'), (2, 'RPS')",
];

// --- Paso 3: Tabla conciliación bancaria ---
$steps[] = [
    'name' => 'Crear tabla tbl_conciliacion_bancaria',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_conciliacion_bancaria'",
    'sql' => "CREATE TABLE tbl_conciliacion_bancaria (
        id_conciliacion BIGINT AUTO_INCREMENT PRIMARY KEY,
        id_cuenta_banco INT NOT NULL,
        id_centro_costo INT NOT NULL,
        llave_item VARCHAR(255) NOT NULL,
        deb_cred VARCHAR(20) NOT NULL COMMENT 'EGRESO o INGRESO',
        fv VARCHAR(100) NULL COMMENT 'Numero factura asociada',
        item_cliente VARCHAR(255) NULL,
        anio INT NOT NULL,
        mes INT NOT NULL,
        semana INT NULL,
        valor DECIMAL(15,2) NOT NULL COMMENT 'Con signo: negativo=egreso, positivo=ingreso',
        fecha_sistema DATE NOT NULL,
        documento BIGINT NULL,
        descripcion_motivo VARCHAR(500) NULL,
        transaccion VARCHAR(50) NULL COMMENT 'Nota Debito, Nota Credito, Deposito Especial',
        oficina_recaudo VARCHAR(100) NULL,
        nit_originador BIGINT NULL,
        valor_cheque DECIMAL(15,2) NULL DEFAULT 0,
        valor_total DECIMAL(15,2) NULL COMMENT 'Valor absoluto',
        referencia_1 VARCHAR(50) NULL,
        referencia_2 VARCHAR(50) NULL,
        mes_real INT NOT NULL COMMENT 'Mes real del gasto/ingreso (puede diferir del mes del movimiento)',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_cuenta (id_cuenta_banco),
        KEY idx_centro_costo (id_centro_costo),
        KEY idx_anio_mes (anio, mes),
        KEY idx_mes_real (mes_real),
        KEY idx_deb_cred (deb_cred),
        KEY idx_fecha_sistema (fecha_sistema),
        KEY idx_llave_item (llave_item),
        KEY idx_nit_originador (nit_originador),
        CONSTRAINT fk_conc_cuenta FOREIGN KEY (id_cuenta_banco) REFERENCES tbl_cuentas_banco(id_cuenta_banco),
        CONSTRAINT fk_conc_centro FOREIGN KEY (id_centro_costo) REFERENCES tbl_centros_costo(id_centro_costo)
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
