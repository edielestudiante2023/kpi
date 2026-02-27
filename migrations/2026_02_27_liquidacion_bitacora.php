<?php

/**
 * Migración: Módulo de Liquidación Quincenal de Bitácora
 *
 * - Columnas nuevas en users: jornada, admin_bitacora
 * - Tabla dias_festivos (festivos colombianos)
 * - Tabla liquidaciones_bitacora (cortes de quincena)
 * - Tabla detalle_liquidacion (resultado por usuario)
 * - Seed festivos colombianos 2026
 * - Habilitar admin_bitacora para Diana y Edison
 *
 * Uso:  php migrations/2026_02_27_liquidacion_bitacora.php
 */

function leerEnv(string $path): array {
    $vars = [];
    if (!file_exists($path)) return $vars;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = array_map('trim', explode('=', $line, 2));
        $val = trim($val, "\"' ");
        $vars[$key] = $val;
    }
    return $vars;
}

$dotenv = leerEnv(__DIR__ . '/../.env');
$config = [
    'host'     => $dotenv['database.default.hostname'] ?? '127.0.0.1',
    'port'     => $dotenv['database.default.port']     ?? 3306,
    'username' => $dotenv['database.default.username'] ?? 'root',
    'password' => $dotenv['database.default.password'] ?? '',
    'database' => $dotenv['database.default.database'] ?? 'kpicycloid',
];
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if ($config['host'] !== '127.0.0.1' && $config['host'] !== 'localhost') {
    $opts[PDO::MYSQL_ATTR_SSL_CA] = true;
    $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}
$pdo = new PDO($dsn, $config['username'], $config['password'], $opts);
echo "Conectado a: {$config['host']}\n\n";

$db = $config['database'];

// ── 1. Columna jornada en users ───────────────────────────
echo "1. Columna users.jornada... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'jornada'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("ALTER TABLE users ADD COLUMN jornada ENUM('completa','media') NOT NULL DEFAULT 'completa' AFTER bitacora_habilitada");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 2. Columna admin_bitacora en users ────────────────────
echo "2. Columna users.admin_bitacora... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'admin_bitacora'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("ALTER TABLE users ADD COLUMN admin_bitacora TINYINT(1) NOT NULL DEFAULT 0 AFTER jornada");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 3. Tabla dias_festivos ────────────────────────────────
echo "3. Tabla dias_festivos... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'dias_festivos'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        CREATE TABLE dias_festivos (
            id_festivo INT AUTO_INCREMENT PRIMARY KEY,
            fecha DATE NOT NULL,
            descripcion VARCHAR(150) NOT NULL,
            anio SMALLINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_fecha (fecha),
            KEY idx_anio (anio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 4. Tabla liquidaciones_bitacora ───────────────────────
echo "4. Tabla liquidaciones_bitacora... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'liquidaciones_bitacora'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        CREATE TABLE liquidaciones_bitacora (
            id_liquidacion INT AUTO_INCREMENT PRIMARY KEY,
            fecha_inicio DATETIME NOT NULL,
            fecha_corte DATETIME NOT NULL,
            dias_habiles INT NOT NULL,
            ejecutado_por INT UNSIGNED NOT NULL,
            notas TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY fk_liq_ejecutor (ejecutado_por),
            CONSTRAINT fk_liq_ejecutor FOREIGN KEY (ejecutado_por) REFERENCES users(id_users)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 5. Tabla detalle_liquidacion ──────────────────────────
echo "5. Tabla detalle_liquidacion... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'detalle_liquidacion'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        CREATE TABLE detalle_liquidacion (
            id_detalle INT AUTO_INCREMENT PRIMARY KEY,
            id_liquidacion INT NOT NULL,
            id_usuario INT UNSIGNED NOT NULL,
            jornada ENUM('completa','media') NOT NULL DEFAULT 'completa',
            dias_habiles INT NOT NULL,
            horas_meta DECIMAL(8,2) NOT NULL,
            horas_trabajadas DECIMAL(8,2) NOT NULL DEFAULT 0,
            porcentaje_cumplimiento DECIMAL(6,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY fk_det_liq (id_liquidacion),
            KEY fk_det_user (id_usuario),
            CONSTRAINT fk_det_liq FOREIGN KEY (id_liquidacion) REFERENCES liquidaciones_bitacora(id_liquidacion) ON DELETE CASCADE,
            CONSTRAINT fk_det_user FOREIGN KEY (id_usuario) REFERENCES users(id_users)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 6. Seed festivos colombianos 2026 ─────────────────────
echo "6. Festivos colombianos 2026... ";
$festivos2026 = [
    ['2026-01-01', 'Año Nuevo'],
    ['2026-01-12', 'Día de los Reyes Magos'],
    ['2026-03-23', 'Día de San José'],
    ['2026-04-02', 'Jueves Santo'],
    ['2026-04-03', 'Viernes Santo'],
    ['2026-05-01', 'Día del Trabajo'],
    ['2026-05-18', 'Ascensión del Señor'],
    ['2026-06-08', 'Corpus Christi'],
    ['2026-06-15', 'Sagrado Corazón'],
    ['2026-06-29', 'San Pedro y San Pablo'],
    ['2026-07-20', 'Día de la Independencia'],
    ['2026-08-07', 'Batalla de Boyacá'],
    ['2026-08-17', 'Asunción de la Virgen'],
    ['2026-10-12', 'Día de la Raza'],
    ['2026-11-02', 'Todos los Santos'],
    ['2026-11-16', 'Independencia de Cartagena'],
    ['2026-12-08', 'Inmaculada Concepción'],
    ['2026-12-25', 'Navidad'],
];

$insertados = 0;
$stmt = $pdo->prepare("INSERT INTO dias_festivos (fecha, descripcion, anio) VALUES (:fecha, :desc, :anio) ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)");
foreach ($festivos2026 as [$fecha, $desc]) {
    $stmt->execute(['fecha' => $fecha, 'desc' => $desc, 'anio' => 2026]);
    $insertados++;
}
echo "[{$insertados} festivos]\n";

// ── 7. Habilitar admin_bitacora para Diana y Edison ───────
echo "7. Admin bitacora para Diana y Edison... ";
// Edison (superadmin, ID puede variar)
$pdo->exec("UPDATE users SET admin_bitacora = 1 WHERE nombre_completo LIKE '%Edison%' AND activo = 1");
// Diana
$pdo->exec("UPDATE users SET admin_bitacora = 1 WHERE nombre_completo LIKE '%Diana%' AND activo = 1");
$stmt = $pdo->query("SELECT id_users, nombre_completo FROM users WHERE admin_bitacora = 1");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "[" . count($admins) . " admins: ";
echo implode(', ', array_column($admins, 'nombre_completo'));
echo "]\n";

// ── 8. Jornada media para Solangel (si existe) ───────────
echo "8. Jornada media para Solangel... ";
$stmt = $pdo->prepare("UPDATE users SET jornada = 'media' WHERE nombre_completo LIKE '%Solangel%' OR nombre_completo LIKE '%Solángel%'");
$stmt->execute();
if ($stmt->rowCount() > 0) {
    echo "[OK — {$stmt->rowCount()} actualizada(s)]\n";
} else {
    echo "[NO ENCONTRADA — se configurará cuando se cree el usuario]\n";
}

echo "\n=== MIGRACIÓN COMPLETADA ===\n";
