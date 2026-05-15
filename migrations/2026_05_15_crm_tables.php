<?php

/**
 * Migración: Módulo CRM standalone (pipeline comercial)
 *
 * Crea:
 *  - tbl_crm_empresa, tbl_crm_contacto
 *  - tbl_crm_etapa (configurable), tbl_crm_oportunidad, tbl_crm_oportunidad_historial
 *  - tbl_crm_interaccion (timeline)
 *  - tbl_crm_fuente, tbl_crm_motivo_perdida (catálogos)
 *  - Columnas users.crm_habilitado y users.crm_admin
 *  - Seed: 6 etapas estándar, 5 fuentes, 4 motivos de pérdida
 *  - Habilita crm_habilitado=1 y crm_admin=1 para Edison
 *
 * Uso:  php migrations/2026_05_15_crm_tables.php
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
function columnaExiste(PDO $pdo, string $db, string $tabla, string $col): bool {
    $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$tabla' AND COLUMN_NAME = '$col'");
    return ((int) $stmt->fetchColumn()) > 0;
}

// ── 1. tbl_crm_fuente ─────────────────────────────────────
echo "1. Tabla tbl_crm_fuente... ";
if (!tablaExiste($pdo, $db, 'tbl_crm_fuente')) {
    $pdo->exec("
        CREATE TABLE tbl_crm_fuente (
            id_fuente INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(80) NOT NULL,
            activa TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_fuente_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 2. tbl_crm_motivo_perdida ─────────────────────────────
echo "2. Tabla tbl_crm_motivo_perdida... ";
if (!tablaExiste($pdo, $db, 'tbl_crm_motivo_perdida')) {
    $pdo->exec("
        CREATE TABLE tbl_crm_motivo_perdida (
            id_motivo_perdida INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL,
            activa TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_motivo_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 3. tbl_crm_etapa ──────────────────────────────────────
echo "3. Tabla tbl_crm_etapa... ";
if (!tablaExiste($pdo, $db, 'tbl_crm_etapa')) {
    $pdo->exec("
        CREATE TABLE tbl_crm_etapa (
            id_etapa INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(60) NOT NULL,
            orden INT NOT NULL,
            probabilidad_default TINYINT NOT NULL DEFAULT 0,
            color VARCHAR(7) NOT NULL DEFAULT '#6c757d',
            tipo ENUM('abierta','ganada','perdida') NOT NULL DEFAULT 'abierta',
            activa TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_etapa_nombre (nombre),
            KEY idx_orden (orden)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 4. tbl_crm_empresa ────────────────────────────────────
echo "4. Tabla tbl_crm_empresa... ";
if (!tablaExiste($pdo, $db, 'tbl_crm_empresa')) {
    $pdo->exec("
        CREATE TABLE tbl_crm_empresa (
            id_empresa INT AUTO_INCREMENT PRIMARY KEY,
            razon_social VARCHAR(200) NOT NULL,
            nit VARCHAR(30) NULL,
            sector VARCHAR(100) NULL,
            tamano ENUM('micro','pequena','mediana','grande') NULL,
            ciudad VARCHAR(100) NULL,
            telefono VARCHAR(50) NULL,
            email_principal VARCHAR(150) NULL,
            sitio_web VARCHAR(200) NULL,
            id_fuente INT NULL,
            id_responsable INT UNSIGNED NULL,
            notas TEXT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_responsable (id_responsable),
            KEY idx_razon (razon_social),
            KEY idx_fuente (id_fuente),
            CONSTRAINT fk_crm_emp_resp FOREIGN KEY (id_responsable) REFERENCES users(id_users) ON DELETE SET NULL,
            CONSTRAINT fk_crm_emp_fuente FOREIGN KEY (id_fuente) REFERENCES tbl_crm_fuente(id_fuente) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 5. tbl_crm_contacto ───────────────────────────────────
echo "5. Tabla tbl_crm_contacto... ";
if (!tablaExiste($pdo, $db, 'tbl_crm_contacto')) {
    $pdo->exec("
        CREATE TABLE tbl_crm_contacto (
            id_contacto INT AUTO_INCREMENT PRIMARY KEY,
            id_empresa INT NOT NULL,
            nombre VARCHAR(150) NOT NULL,
            cargo VARCHAR(100) NULL,
            email VARCHAR(150) NULL,
            telefono VARCHAR(50) NULL,
            es_decisor TINYINT(1) NOT NULL DEFAULT 0,
            notas TEXT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_empresa (id_empresa),
            KEY idx_email (email),
            CONSTRAINT fk_crm_cont_empresa FOREIGN KEY (id_empresa) REFERENCES tbl_crm_empresa(id_empresa) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 6. tbl_crm_oportunidad ────────────────────────────────
echo "6. Tabla tbl_crm_oportunidad... ";
if (!tablaExiste($pdo, $db, 'tbl_crm_oportunidad')) {
    $pdo->exec("
        CREATE TABLE tbl_crm_oportunidad (
            id_oportunidad INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(20) NOT NULL,
            id_empresa INT NOT NULL,
            id_contacto_principal INT NULL,
            titulo VARCHAR(200) NOT NULL,
            descripcion TEXT NULL,
            valor DECIMAL(14,2) NOT NULL DEFAULT 0,
            moneda CHAR(3) NOT NULL DEFAULT 'COP',
            id_etapa INT NOT NULL,
            probabilidad TINYINT NOT NULL DEFAULT 0,
            fecha_cierre_estimada DATE NULL,
            fecha_cierre_real DATE NULL,
            id_motivo_perdida INT NULL,
            id_responsable INT UNSIGNED NOT NULL,
            id_creador INT UNSIGNED NOT NULL,
            notas TEXT NULL,
            notificado_estancada TINYINT(1) NOT NULL DEFAULT 0,
            ultima_actividad_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_codigo (codigo),
            KEY idx_etapa (id_etapa),
            KEY idx_responsable (id_responsable),
            KEY idx_empresa (id_empresa),
            KEY idx_contacto (id_contacto_principal),
            KEY idx_motivo (id_motivo_perdida),
            KEY idx_fecha_cierre (fecha_cierre_estimada),
            CONSTRAINT fk_crm_op_empresa  FOREIGN KEY (id_empresa) REFERENCES tbl_crm_empresa(id_empresa),
            CONSTRAINT fk_crm_op_contacto FOREIGN KEY (id_contacto_principal) REFERENCES tbl_crm_contacto(id_contacto) ON DELETE SET NULL,
            CONSTRAINT fk_crm_op_etapa    FOREIGN KEY (id_etapa) REFERENCES tbl_crm_etapa(id_etapa),
            CONSTRAINT fk_crm_op_motivo   FOREIGN KEY (id_motivo_perdida) REFERENCES tbl_crm_motivo_perdida(id_motivo_perdida) ON DELETE SET NULL,
            CONSTRAINT fk_crm_op_resp     FOREIGN KEY (id_responsable) REFERENCES users(id_users),
            CONSTRAINT fk_crm_op_creador  FOREIGN KEY (id_creador) REFERENCES users(id_users)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 7. tbl_crm_oportunidad_historial ──────────────────────
echo "7. Tabla tbl_crm_oportunidad_historial... ";
if (!tablaExiste($pdo, $db, 'tbl_crm_oportunidad_historial')) {
    $pdo->exec("
        CREATE TABLE tbl_crm_oportunidad_historial (
            id_historial INT AUTO_INCREMENT PRIMARY KEY,
            id_oportunidad INT NOT NULL,
            id_etapa_anterior INT NULL,
            id_etapa_nueva INT NOT NULL,
            id_usuario INT UNSIGNED NOT NULL,
            comentario TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_oportunidad_fecha (id_oportunidad, created_at),
            CONSTRAINT fk_crm_hist_op   FOREIGN KEY (id_oportunidad) REFERENCES tbl_crm_oportunidad(id_oportunidad) ON DELETE CASCADE,
            CONSTRAINT fk_crm_hist_eant FOREIGN KEY (id_etapa_anterior) REFERENCES tbl_crm_etapa(id_etapa) ON DELETE SET NULL,
            CONSTRAINT fk_crm_hist_enue FOREIGN KEY (id_etapa_nueva)    REFERENCES tbl_crm_etapa(id_etapa),
            CONSTRAINT fk_crm_hist_user FOREIGN KEY (id_usuario) REFERENCES users(id_users)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 8. tbl_crm_interaccion ────────────────────────────────
echo "8. Tabla tbl_crm_interaccion... ";
if (!tablaExiste($pdo, $db, 'tbl_crm_interaccion')) {
    $pdo->exec("
        CREATE TABLE tbl_crm_interaccion (
            id_interaccion INT AUTO_INCREMENT PRIMARY KEY,
            id_oportunidad INT NULL,
            id_empresa INT NULL,
            id_contacto INT NULL,
            tipo ENUM('llamada','reunion','correo','nota','tarea','propuesta_enviada','whatsapp') NOT NULL,
            asunto VARCHAR(200) NOT NULL,
            detalle TEXT NULL,
            fecha_programada DATETIME NULL,
            fecha_completada DATETIME NULL,
            estado ENUM('pendiente','completada','cancelada') NOT NULL DEFAULT 'completada',
            recordatorio_at DATETIME NULL,
            recordatorio_enviado TINYINT(1) NOT NULL DEFAULT 0,
            id_usuario INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_oportunidad (id_oportunidad),
            KEY idx_empresa (id_empresa),
            KEY idx_contacto (id_contacto),
            KEY idx_recordatorio (recordatorio_at, recordatorio_enviado, estado),
            KEY idx_usuario_estado (id_usuario, estado),
            CONSTRAINT fk_crm_int_op       FOREIGN KEY (id_oportunidad) REFERENCES tbl_crm_oportunidad(id_oportunidad) ON DELETE CASCADE,
            CONSTRAINT fk_crm_int_empresa  FOREIGN KEY (id_empresa) REFERENCES tbl_crm_empresa(id_empresa) ON DELETE CASCADE,
            CONSTRAINT fk_crm_int_contacto FOREIGN KEY (id_contacto) REFERENCES tbl_crm_contacto(id_contacto) ON DELETE SET NULL,
            CONSTRAINT fk_crm_int_user     FOREIGN KEY (id_usuario) REFERENCES users(id_users)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 9. Columnas en users ──────────────────────────────────
echo "9. Columna users.crm_habilitado... ";
if (!columnaExiste($pdo, $db, 'users', 'crm_habilitado')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN crm_habilitado TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_bitacora");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

echo "10. Columna users.crm_admin... ";
if (!columnaExiste($pdo, $db, 'users', 'crm_admin')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN crm_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER crm_habilitado");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 11. Seed: etapas ──────────────────────────────────────
echo "11. Seed de etapas... ";
$etapas = [
    ['Prospecto',    10, 10, '#6c757d', 'abierta'],
    ['Calificado',   20, 30, '#0d6efd', 'abierta'],
    ['Propuesta',    30, 60, '#fd7e14', 'abierta'],
    ['Negociacion',  40, 80, '#6f42c1', 'abierta'],
    ['Ganada',       90, 100,'#198754', 'ganada'],
    ['Perdida',     100,  0, '#dc3545', 'perdida'],
];
$ins = $pdo->prepare("
    INSERT INTO tbl_crm_etapa (nombre, orden, probabilidad_default, color, tipo)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE orden=VALUES(orden), probabilidad_default=VALUES(probabilidad_default), color=VALUES(color), tipo=VALUES(tipo)
");
foreach ($etapas as $e) $ins->execute($e);
echo "[OK: " . count($etapas) . " etapas]\n";

// ── 12. Seed: fuentes ─────────────────────────────────────
echo "12. Seed de fuentes... ";
$fuentes = ['Referido', 'LinkedIn', 'Web', 'Llamada en frio', 'Evento'];
$ins = $pdo->prepare("INSERT INTO tbl_crm_fuente (nombre) VALUES (?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)");
foreach ($fuentes as $f) $ins->execute([$f]);
echo "[OK: " . count($fuentes) . " fuentes]\n";

// ── 13. Seed: motivos de pérdida ──────────────────────────
echo "13. Seed de motivos de perdida... ";
$motivos = ['Precio', 'Timing', 'Competencia', 'No respondio'];
$ins = $pdo->prepare("INSERT INTO tbl_crm_motivo_perdida (nombre) VALUES (?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)");
foreach ($motivos as $m) $ins->execute([$m]);
echo "[OK: " . count($motivos) . " motivos]\n";

// ── 14. Habilitar CRM para Edison ─────────────────────────
echo "14. Habilitar crm_habilitado + crm_admin para Edison... ";
$pdo->exec("UPDATE users SET crm_habilitado = 1, crm_admin = 1 WHERE nombre_completo LIKE '%Edison%' AND activo = 1");
$stmt = $pdo->query("SELECT id_users, nombre_completo FROM users WHERE crm_admin = 1");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "[" . count($admins) . " admin(s): " . implode(', ', array_column($admins, 'nombre_completo')) . "]\n";

echo "\n=== MIGRACION COMPLETADA ===\n";
