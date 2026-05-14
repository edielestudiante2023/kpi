<?php

/**
 * Migración: Módulo Liquidador de Tiempo Adicional
 *
 * - Columna nueva en novedades_individuales: tipo ('reduccion' | 'uso_tiempo_adicional')
 * - Tabla tiempo_adicional_quincena (excedente acumulado por usuario por quincena liquidada)
 *
 * Reglas:
 * - Al liquidar una quincena, si horas_trabajadas > horas_meta, el excedente se
 *   registra en tiempo_adicional_quincena (una fila por usuario por liquidación).
 * - El saldo a favor de un usuario = SUM(horas_adicionales)
 *                                  - SUM(novedades_individuales.horas_reduccion
 *                                        WHERE tipo = 'uso_tiempo_adicional').
 *
 * Uso:  php migrations/2026_05_14_tiempo_adicional.php
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

// ── 1. Columna tipo en novedades_individuales ─────────────
echo "1. Columna novedades_individuales.tipo... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'novedades_individuales' AND COLUMN_NAME = 'tipo'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("ALTER TABLE novedades_individuales ADD COLUMN tipo ENUM('reduccion','uso_tiempo_adicional') NOT NULL DEFAULT 'reduccion' AFTER motivo");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

// ── 2. Tabla tiempo_adicional_quincena ────────────────────
echo "2. Tabla tiempo_adicional_quincena... ";
$stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'tiempo_adicional_quincena'");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        CREATE TABLE tiempo_adicional_quincena (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT UNSIGNED NOT NULL,
            id_liquidacion INT NOT NULL,
            horas_adicionales DECIMAL(8,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY fk_ta_user (id_usuario),
            KEY fk_ta_liq (id_liquidacion),
            CONSTRAINT fk_ta_user FOREIGN KEY (id_usuario) REFERENCES users(id_users),
            CONSTRAINT fk_ta_liq FOREIGN KEY (id_liquidacion) REFERENCES liquidaciones_bitacora(id_liquidacion) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "[CREADA]\n";
} else {
    echo "[YA EXISTE]\n";
}

echo "\n=== MIGRACIÓN COMPLETADA ===\n";
