<?php

/**
 * Migración: Snapshots semanales del pipeline CRM.
 *
 * - tbl_crm_snapshot_semanal: foto del estado del pipeline en un momento (KPIs
 *   congelados + JSON con breakdown por etapa, responsable y motivos de pérdida).
 *   Generado manualmente por un admin desde la UI ("Generar snapshot ahora").
 *
 * Uso:  php migrations/2026_05_15_crm_snapshot_semanal.php
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

// ── tbl_crm_snapshot_semanal ──────────────────────────────
echo "1. Tabla tbl_crm_snapshot_semanal... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'tbl_crm_snapshot_semanal'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        CREATE TABLE tbl_crm_snapshot_semanal (
            id_snapshot INT AUTO_INCREMENT PRIMARY KEY,
            fecha_corte DATETIME NOT NULL,

            -- KPIs principales (estado actual al momento del corte)
            total_abiertas             INT NOT NULL DEFAULT 0,
            valor_pipeline             DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_ganadas_anio         INT NOT NULL DEFAULT 0,
            valor_ganadas_anio         DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_perdidas_anio        INT NOT NULL DEFAULT 0,
            valor_perdidas_anio        DECIMAL(14,2) NOT NULL DEFAULT 0,
            tasa_conversion_anio       DECIMAL(5,2) NOT NULL DEFAULT 0,
            ciclo_promedio_dias        INT NULL,
            oportunidades_estancadas_30d INT NOT NULL DEFAULT 0,

            -- Breakdown detallado (JSON)
            por_etapa                  JSON NULL,
            por_responsable            JSON NULL,
            motivos_perdida_top        JSON NULL,

            -- Auditoría
            creado_por                 INT UNSIGNED NOT NULL,
            notas                      TEXT NULL,
            created_at                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            KEY idx_fecha_corte (fecha_corte),
            CONSTRAINT fk_snap_user FOREIGN KEY (creado_por) REFERENCES users(id_users)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

echo "\n=== MIGRACION COMPLETADA ===\n";
