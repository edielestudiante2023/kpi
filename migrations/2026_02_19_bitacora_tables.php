<?php
/**
 * Migración: Módulo Bitácora
 * - Agrega columna bitacora_habilitada a users
 * - Crea tabla centros_costo
 * - Crea tabla bitacora_actividades
 *
 * Uso:  php migrations/2026_02_19_bitacora_tables.php [local|production]
 */

$env = $argv[1] ?? 'local';

if ($env === 'production') {
    // Leer credenciales desde variables de entorno o archivo .env del proyecto
    $dotenv = @parse_ini_file(__DIR__ . '/../.env');
    $config = [
        'host'     => $dotenv['database.default.hostname'] ?? getenv('DB_HOST'),
        'port'     => $dotenv['database.default.port'] ?? getenv('DB_PORT') ?: 25060,
        'username' => $dotenv['database.default.username'] ?? getenv('DB_USER'),
        'password' => $dotenv['database.default.password'] ?? getenv('DB_PASS'),
        'database' => $dotenv['database.default.database'] ?? getenv('DB_NAME') ?: 'kpicycloid',
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

echo "=== Migración Bitácora — entorno: {$env} ===\n\n";

// Conexión
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

// --- Paso 1: columna bitacora_habilitada en users ---
$steps[] = [
    'name' => 'Agregar columna bitacora_habilitada a users',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bitacora_habilitada'",
    'sql' => "ALTER TABLE users ADD COLUMN bitacora_habilitada TINYINT(1) DEFAULT 0 AFTER reset_token_expira",
];

// --- Paso 2: tabla centros_costo ---
$steps[] = [
    'name' => 'Crear tabla centros_costo',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'centros_costo'",
    'sql' => "CREATE TABLE centros_costo (
        id_centro_costo INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT NULL,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT UNSIGNED NULL,
        UNIQUE KEY uk_nombre (nombre),
        KEY fk_cc_user (created_by),
        CONSTRAINT fk_cc_user FOREIGN KEY (created_by) REFERENCES users(id_users) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// --- Paso 3: tabla bitacora_actividades ---
$steps[] = [
    'name' => 'Crear tabla bitacora_actividades',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'bitacora_actividades'",
    'sql' => "CREATE TABLE bitacora_actividades (
        id_bitacora INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT UNSIGNED NOT NULL,
        numero_actividad INT NOT NULL,
        descripcion TEXT NOT NULL,
        id_centro_costo INT NOT NULL,
        fecha DATE NOT NULL,
        hora_inicio DATETIME NOT NULL,
        hora_fin DATETIME NULL,
        duracion_minutos DECIMAL(8,2) NULL,
        estado ENUM('en_progreso','finalizada') DEFAULT 'en_progreso',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY fk_bit_usuario (id_usuario),
        KEY fk_bit_cc (id_centro_costo),
        KEY idx_fecha_usuario (fecha, id_usuario),
        CONSTRAINT fk_bit_usuario FOREIGN KEY (id_usuario) REFERENCES users(id_users) ON DELETE CASCADE,
        CONSTRAINT fk_bit_cc FOREIGN KEY (id_centro_costo) REFERENCES centros_costo(id_centro_costo) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// Ejecutar pasos
$allOk = true;
foreach ($steps as $i => $step) {
    $num = $i + 1;
    echo "\n--- Paso {$num}: {$step['name']} ---\n";

    // Verificar si ya existe
    $stmt = $pdo->query($step['check']);
    $exists = (int) $stmt->fetchColumn();

    if ($exists > 0) {
        echo "[SKIP] Ya existe, no se ejecuta.\n";
        continue;
    }

    // Ejecutar
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
