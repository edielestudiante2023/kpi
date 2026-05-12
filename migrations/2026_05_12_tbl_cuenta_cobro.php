<?php
/**
 * Migración: Tabla tbl_cuenta_cobro
 * Módulo de Conciliaciones — Cuentas de cobro de contratistas/personas naturales
 *
 * Uso:  php migrations/2026_05_12_tbl_cuenta_cobro.php [local|production]
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

echo "=== Migración tbl_cuenta_cobro — entorno: {$env} ===\n\n";

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
    'name' => 'Crear tabla tbl_cuenta_cobro',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_cuenta_cobro'",
    'sql' => "CREATE TABLE tbl_cuenta_cobro (
        id_cuenta_cobro INT AUTO_INCREMENT PRIMARY KEY,

        -- Cobrador
        tipo_documento ENUM('CC','CE','TI','NIT','PASAPORTE') NOT NULL DEFAULT 'CC',
        documento VARCHAR(30) NOT NULL,
        nombre_cobrador VARCHAR(200) NOT NULL,
        email_cobrador VARCHAR(150) NULL,
        telefono_cobrador VARCHAR(50) NULL,

        -- Servicio
        id_centro_costo INT NOT NULL,
        id_clasificacion INT NULL,
        descripcion_servicio TEXT NOT NULL,
        fecha_gasto DATE NOT NULL,
        periodo_desde DATE NULL,
        periodo_hasta DATE NULL,

        -- Valores
        valor_bruto DECIMAL(15,2) NOT NULL,
        retencion_fuente DECIMAL(15,2) NOT NULL DEFAULT 0,
        retencion_iva DECIMAL(15,2) NOT NULL DEFAULT 0,
        retencion_ica DECIMAL(15,2) NOT NULL DEFAULT 0,
        otras_deducciones DECIMAL(15,2) NOT NULL DEFAULT 0,
        total_retenciones DECIMAL(15,2) GENERATED ALWAYS AS
            (retencion_fuente + retencion_iva + retencion_ica + otras_deducciones) STORED,
        valor_neto_a_pagar DECIMAL(15,2) NOT NULL,

        -- Pago
        estado ENUM('pendiente','pagada','castigada') NOT NULL DEFAULT 'pendiente',
        forma_pago ENUM('transferencia','cheque','efectivo','otro') NULL,
        banco_destino VARCHAR(100) NULL,
        tipo_cuenta_destino ENUM('ahorros','corriente') NULL,
        numero_cuenta_destino VARCHAR(50) NULL,
        titular_cuenta VARCHAR(200) NULL,
        fecha_pago DATE NULL,
        referencia_pago VARCHAR(150) NULL,
        id_cuenta_banco_pago INT NULL,
        id_conciliacion_ref INT NULL,

        -- PDF (obligatorio)
        ruta_pdf VARCHAR(500) NOT NULL,
        nombre_pdf_original VARCHAR(255) NOT NULL,
        hash_pdf VARCHAR(64) NOT NULL,
        tamano_pdf INT NOT NULL,

        -- Reportería automática
        anio SMALLINT GENERATED ALWAYS AS (YEAR(fecha_gasto)) STORED,
        mes TINYINT GENERATED ALWAYS AS (MONTH(fecha_gasto)) STORED,

        -- Auditoría
        creado_por VARCHAR(100) NULL,
        notas TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        KEY idx_documento (documento),
        KEY idx_estado (estado),
        KEY idx_anio_mes (anio, mes),
        KEY idx_centro (id_centro_costo),
        KEY idx_hash (hash_pdf),
        CONSTRAINT fk_cc_centro FOREIGN KEY (id_centro_costo)
            REFERENCES tbl_centros_costo(id_centro_costo)
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
