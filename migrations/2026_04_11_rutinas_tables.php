<?php
/**
 * Migración: Módulo Rutinas de Trabajo
 * - Crea tabla rutinas_actividades (maestra de actividades)
 * - Crea tabla rutinas_asignaciones (quién hace qué)
 * - Crea tabla rutinas_registros (check diario)
 *
 * Uso:  php migrations/2026_04_11_rutinas_tables.php [local|production]
 */

$env = $argv[1] ?? 'local';

if ($env === 'production') {
    $dotenv = @parse_ini_file(__DIR__ . '/../.env.production');
    $config = [
        'host'     => trim($dotenv['DB_HOST'] ?? getenv('DB_HOST')),
        'port'     => trim($dotenv['DB_PORT'] ?? getenv('DB_PORT') ?: 25060),
        'username' => trim($dotenv['DB_USER'] ?? getenv('DB_USER')),
        'password' => trim($dotenv['DB_PASS'] ?? getenv('DB_PASS')),
        'database' => trim($dotenv['DB_NAME'] ?? getenv('DB_NAME') ?: 'kpicycloid'),
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

echo "=== Migración Rutinas de Trabajo — entorno: {$env} ===\n\n";

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

// --- Paso 1: tabla rutinas_actividades ---
$steps[] = [
    'name' => 'Crear tabla rutinas_actividades',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'rutinas_actividades'",
    'sql' => "CREATE TABLE rutinas_actividades (
        id_actividad INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL COMMENT 'Nombre corto de la actividad',
        descripcion TEXT NULL COMMENT 'Descripción detallada de qué hacer',
        frecuencia ENUM('L-V','diaria') DEFAULT 'L-V' COMMENT 'L-V = lunes a viernes, diaria = incluye fines de semana',
        peso DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Peso para cálculo de puntaje de cumplimiento',
        activa TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// --- Paso 2: tabla rutinas_asignaciones ---
$steps[] = [
    'name' => 'Crear tabla rutinas_asignaciones',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'rutinas_asignaciones'",
    'sql' => "CREATE TABLE rutinas_asignaciones (
        id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
        id_users INT(10) UNSIGNED NOT NULL COMMENT 'FK a users.id_users',
        id_actividad INT NOT NULL COMMENT 'FK a rutinas_actividades.id_actividad',
        activa TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_actividad (id_users, id_actividad),
        KEY fk_ra_actividad (id_actividad),
        CONSTRAINT fk_ra_users FOREIGN KEY (id_users) REFERENCES users(id_users) ON DELETE CASCADE,
        CONSTRAINT fk_ra_actividad FOREIGN KEY (id_actividad) REFERENCES rutinas_actividades(id_actividad) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// --- Paso 3: tabla rutinas_registros ---
$steps[] = [
    'name' => 'Crear tabla rutinas_registros',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'rutinas_registros'",
    'sql' => "CREATE TABLE rutinas_registros (
        id_registro INT AUTO_INCREMENT PRIMARY KEY,
        id_users INT(10) UNSIGNED NOT NULL COMMENT 'FK a users.id_users',
        id_actividad INT NOT NULL COMMENT 'FK a rutinas_actividades.id_actividad',
        fecha DATE NOT NULL COMMENT 'Fecha del día de la rutina',
        completada TINYINT(1) DEFAULT 0 COMMENT '0=pendiente, 1=completada',
        hora_completado DATETIME NULL COMMENT 'Timestamp exacto cuando marcó el check',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_actividad_fecha (id_users, id_actividad, fecha),
        KEY idx_fecha (fecha),
        KEY fk_rr_actividad (id_actividad),
        CONSTRAINT fk_rr_users FOREIGN KEY (id_users) REFERENCES users(id_users) ON DELETE CASCADE,
        CONSTRAINT fk_rr_actividad FOREIGN KEY (id_actividad) REFERENCES rutinas_actividades(id_actividad) ON DELETE CASCADE
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
