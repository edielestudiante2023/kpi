<?php

/**
 * Migración: Maestro de Terceros + documentos adjuntos + FK en cuenta de cobro
 *
 * - tbl_terceros: maestro de proveedores (personas a quienes se les hacen cuentas de cobro).
 * - tbl_terceros_documentos: PDFs por tipo (RUT, cédula, certificación bancaria).
 * - tbl_cuenta_cobro: se agrega columna id_tercero (NULL → backward compat con datos inline).
 *
 * Uso:  php migrations/2026_05_14_terceros_y_documentos.php
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

// ── 1. Tabla tbl_terceros ─────────────────────────────────
echo "1. Tabla tbl_terceros... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'tbl_terceros'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        CREATE TABLE tbl_terceros (
            id_tercero INT AUTO_INCREMENT PRIMARY KEY,
            tipo_documento ENUM('CC','CE','TI','NIT','PASAPORTE') NOT NULL DEFAULT 'CC',
            documento VARCHAR(30) NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            email VARCHAR(150) NULL,
            telefono VARCHAR(50) NULL,
            banco VARCHAR(100) NULL,
            tipo_cuenta ENUM('ahorros','corriente') NULL,
            numero_cuenta VARCHAR(50) NULL,
            titular_cuenta VARCHAR(200) NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            notas TEXT NULL,
            creado_por VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_documento (documento),
            KEY idx_activo (activo),
            KEY idx_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 2. Tabla tbl_terceros_documentos ──────────────────────
echo "2. Tabla tbl_terceros_documentos... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'tbl_terceros_documentos'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        CREATE TABLE tbl_terceros_documentos (
            id_documento INT AUTO_INCREMENT PRIMARY KEY,
            id_tercero INT NOT NULL,
            tipo ENUM('rut','cedula','cert_bancaria') NOT NULL,
            nombre_original VARCHAR(255) NULL,
            ruta_pdf VARCHAR(500) NOT NULL,
            hash_pdf VARCHAR(64) NULL,
            tamano_pdf INT NULL,
            subido_por VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_tercero_tipo (id_tercero, tipo),
            CONSTRAINT fk_tdoc_tercero FOREIGN KEY (id_tercero) REFERENCES tbl_terceros(id_tercero) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 3. Columna id_tercero en tbl_cuenta_cobro ─────────────
echo "3. Columna tbl_cuenta_cobro.id_tercero... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'tbl_cuenta_cobro' AND COLUMN_NAME = 'id_tercero'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("ALTER TABLE tbl_cuenta_cobro ADD COLUMN id_tercero INT NULL AFTER id_cuenta_cobro");
    $pdo->exec("ALTER TABLE tbl_cuenta_cobro ADD KEY idx_tercero (id_tercero)");
    $pdo->exec("ALTER TABLE tbl_cuenta_cobro ADD CONSTRAINT fk_cc_tercero FOREIGN KEY (id_tercero) REFERENCES tbl_terceros(id_tercero) ON DELETE SET NULL");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

echo "\n=== MIGRACIÓN COMPLETADA ===\n";
