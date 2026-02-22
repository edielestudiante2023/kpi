<?php

/**
 * Migración: Crear tabla push_subscriptions para Web Push Notifications
 *
 * Ejecutar manualmente en producción:
 *   php spark db:query (o copiar el SQL abajo)
 */

$sql = "
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_push_usuario (id_usuario),
    CONSTRAINT fk_push_usuario FOREIGN KEY (id_usuario) REFERENCES users(id_users) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Auto-ejecutar si se llama directamente
if (php_sapi_name() === 'cli' && basename($argv[0] ?? '') === basename(__FILE__)) {
    $dotenv = @parse_ini_file(__DIR__ . '/../.env');
    $config = [
        'host'     => $dotenv['database.default.hostname'] ?? getenv('DB_HOST'),
        'port'     => $dotenv['database.default.port'] ?? getenv('DB_PORT') ?: 25060,
        'username' => $dotenv['database.default.username'] ?? getenv('DB_USER'),
        'password' => $dotenv['database.default.password'] ?? getenv('DB_PASS'),
        'database' => $dotenv['database.default.database'] ?? getenv('DB_NAME') ?: 'kpicycloid',
    ];

    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::MYSQL_ATTR_SSL_CA => true, PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
        );
        $pdo->exec($sql);
        echo "Tabla push_subscriptions creada exitosamente.\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
