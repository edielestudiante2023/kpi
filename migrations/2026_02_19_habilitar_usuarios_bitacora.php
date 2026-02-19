<?php
/**
 * Migración: Habilitar usuarios iniciales para bitácora
 *
 * Uso:  php migrations/2026_02_19_habilitar_usuarios_bitacora.php [local|production]
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

echo "=== Habilitar usuarios bitácora — entorno: {$env} ===\n\n";

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

// IDs de usuarios a habilitar (encontrados en BD):
// 1  - Edison Cuervo
// 20 - Diana Patricia Cuestas Navia
// 21 - Edison Ernesto Cuervo Salazar
// 23 - Eleyson Augusto Segura Anacaona
// 22 - Lizeth Natalia Jiménez Retavisca
// Nota: No se encontró usuario "Solangel" en la BD
$userIds = [1, 20, 21, 22, 23];

$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$sql = "UPDATE users SET bitacora_habilitada = 1 WHERE id_users IN ({$placeholders})";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($userIds);
    $affected = $stmt->rowCount();
    echo "[OK] {$affected} usuario(s) habilitados para bitácora.\n";

    // Verificar
    $idList = implode(',', $userIds);
    $stmt2 = $pdo->query("SELECT id_users, nombre_completo, bitacora_habilitada FROM users WHERE id_users IN ({$idList})");
    echo "\nUsuarios actualizados:\n";
    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $estado = $row['bitacora_habilitada'] ? 'HABILITADO' : 'NO';
        echo "  [{$estado}] ID {$row['id_users']}: {$row['nombre_completo']}\n";
    }
} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Proceso completado ===\n";
exit(0);
