<?php

/**
 * Migración: Módulo de Marketing (lightweight para empresa pequeña).
 *
 *  - tbl_marketing_lead: persona/empresa interesada antes de ser oportunidad CRM
 *  - tbl_marketing_tipo_accion: catálogo de tipos de acción (LinkedIn, evento, etc)
 *  - tbl_marketing_accion: diario semanal de acciones de marketing con costo opcional
 *
 * Reutiliza: tbl_crm_fuente, tbl_crm_empresa, tbl_crm_oportunidad (cuando se convierte).
 * Admin de marketing = mismo flag crm_admin (no se duplica).
 *
 * Uso:  php migrations/2026_05_15_marketing_modulo.php
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

function tablaExiste(PDO $pdo, string $db, string $tabla): bool {
    $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$tabla'");
    return ((int) $stmt->fetchColumn()) > 0;
}

// ── 1. tbl_marketing_tipo_accion (catálogo) ───────────────
echo "1. Tabla tbl_marketing_tipo_accion... ";
if (!tablaExiste($pdo, $db, 'tbl_marketing_tipo_accion')) {
    $pdo->exec("
        CREATE TABLE tbl_marketing_tipo_accion (
            id_tipo_accion INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(80) NOT NULL,
            color VARCHAR(7) NOT NULL DEFAULT '#6c757d',
            activa TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_tipo_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 2. tbl_marketing_lead ─────────────────────────────────
echo "2. Tabla tbl_marketing_lead... ";
if (!tablaExiste($pdo, $db, 'tbl_marketing_lead')) {
    $pdo->exec("
        CREATE TABLE tbl_marketing_lead (
            id_lead INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            empresa_text VARCHAR(200) NULL,
            cargo VARCHAR(100) NULL,
            email VARCHAR(150) NULL,
            telefono VARCHAR(50) NULL,
            id_fuente INT NULL,
            estado ENUM('nuevo','contactado','calificado','descartado') NOT NULL DEFAULT 'nuevo',
            id_responsable INT UNSIGNED NULL,
            id_empresa_convertida INT NULL,
            id_oportunidad_convertida INT NULL,
            notas TEXT NULL,
            fecha_calificacion DATETIME NULL,
            fecha_descartado DATETIME NULL,
            motivo_descarte VARCHAR(200) NULL,
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_estado (estado),
            KEY idx_fuente (id_fuente),
            KEY idx_responsable (id_responsable),
            KEY idx_email (email),
            KEY idx_creado (created_at),
            CONSTRAINT fk_mkt_lead_fuente FOREIGN KEY (id_fuente) REFERENCES tbl_crm_fuente(id_fuente) ON DELETE SET NULL,
            CONSTRAINT fk_mkt_lead_resp FOREIGN KEY (id_responsable) REFERENCES users(id_users) ON DELETE SET NULL,
            CONSTRAINT fk_mkt_lead_empresa FOREIGN KEY (id_empresa_convertida) REFERENCES tbl_crm_empresa(id_empresa) ON DELETE SET NULL,
            CONSTRAINT fk_mkt_lead_opo FOREIGN KEY (id_oportunidad_convertida) REFERENCES tbl_crm_oportunidad(id_oportunidad) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 3. tbl_marketing_accion ───────────────────────────────
echo "3. Tabla tbl_marketing_accion... ";
if (!tablaExiste($pdo, $db, 'tbl_marketing_accion')) {
    $pdo->exec("
        CREATE TABLE tbl_marketing_accion (
            id_accion INT AUTO_INCREMENT PRIMARY KEY,
            fecha DATE NOT NULL,
            id_tipo_accion INT NOT NULL,
            descripcion VARCHAR(500) NOT NULL,
            costo DECIMAL(12,2) NULL,
            leads_generados INT NULL,
            notas TEXT NULL,
            id_responsable INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_fecha (fecha),
            KEY idx_tipo (id_tipo_accion),
            KEY idx_responsable (id_responsable),
            CONSTRAINT fk_mkt_acc_tipo FOREIGN KEY (id_tipo_accion) REFERENCES tbl_marketing_tipo_accion(id_tipo_accion),
            CONSTRAINT fk_mkt_acc_resp FOREIGN KEY (id_responsable) REFERENCES users(id_users) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 4. Seed tipos de acción ───────────────────────────────
echo "4. Seed tipos de acción... ";
$tipos = [
    ['Post LinkedIn',   '#0a66c2'],
    ['Correo en frío',  '#fd7e14'],
    ['Llamada en frío', '#dc3545'],
    ['Evento',          '#198754'],
    ['Webinar',         '#6f42c1'],
    ['Anuncio pagado',  '#ffc107'],
    ['Referido',        '#20c997'],
    ['Reunión cliente', '#0d6efd'],
    ['Contenido web',   '#6c757d'],
    ['Otro',            '#adb5bd'],
];
$ins = $pdo->prepare("INSERT INTO tbl_marketing_tipo_accion (nombre, color) VALUES (?, ?) ON DUPLICATE KEY UPDATE color = VALUES(color)");
foreach ($tipos as $t) $ins->execute($t);
echo "[OK: " . count($tipos) . " tipos]\n";

echo "\n=== MIGRACION COMPLETADA ===\n";
