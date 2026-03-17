<?php

/**
 * Truncate: Eliminar TODOS los registros de sesiones_usuario
 * Ejecutar: php migrations/2026_03_17_truncate_sesiones.php [local|pro]
 */

$env = $argv[1] ?? 'local';

if ($env === 'pro') {
    $config = [
        'host'     => 'db-mysql-cycloid-do-user-18794030-0.h.db.ondigitalocean.com',
        'port'     => 25060,
        'username' => 'cycloid_userdb',
        'password' => getenv('DB_PASSWORD') ?: '',
        'database' => 'kpicycloid',
    ];
    $dsn  = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $opts = [
        PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT              => 60,
        PDO::MYSQL_ATTR_SSL_CA         => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
} else {
    $config = [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'username' => 'root',
        'password' => '',
        'database' => 'kpicycloid',
    ];
    $dsn  = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
}

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $opts);
    echo "Conectado a [{$env}]: {$config['database']}\n\n";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

$total = $pdo->query("SELECT COUNT(*) FROM sesiones_usuario")->fetchColumn();
echo "Registros actuales: $total\n";

$pdo->exec("DELETE FROM sesiones_usuario");
$pdo->exec("ALTER TABLE sesiones_usuario AUTO_INCREMENT = 1");

$restantes = $pdo->query("SELECT COUNT(*) FROM sesiones_usuario")->fetchColumn();
echo "Registros tras borrado: $restantes\n";
echo ($restantes == 0) ? "OK — tabla vaciada.\n" : "ERROR — quedan registros.\n";
