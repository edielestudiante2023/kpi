<?php

/**
 * Migración: Extractos bancarios (PDFs subidos para revisión de la contadora)
 *
 * - tbl_extractos_bancarios: un PDF por (cuenta_banco, año, mes) (puede haber más
 *   de uno si se sube versión corregida — el más reciente es el vigente).
 *
 * Uso:  php migrations/2026_05_14_extractos_bancarios.php
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

// ── 1. Tabla tbl_extractos_bancarios ──────────────────────
echo "1. Tabla tbl_extractos_bancarios... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'tbl_extractos_bancarios'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        CREATE TABLE tbl_extractos_bancarios (
            id_extracto INT AUTO_INCREMENT PRIMARY KEY,
            id_cuenta_banco INT NOT NULL,
            anio SMALLINT NOT NULL,
            mes TINYINT NOT NULL,
            descripcion VARCHAR(200) NULL,
            nombre_original VARCHAR(255) NULL,
            ruta_pdf VARCHAR(500) NOT NULL,
            hash_pdf VARCHAR(64) NULL,
            tamano_pdf INT NULL,
            subido_por VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_cuenta_periodo (id_cuenta_banco, anio, mes),
            KEY idx_anio_mes (anio, mes),
            CONSTRAINT fk_extracto_cuenta FOREIGN KEY (id_cuenta_banco) REFERENCES tbl_cuentas_banco(id_cuenta_banco)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

echo "\n=== MIGRACIÓN COMPLETADA ===\n";
