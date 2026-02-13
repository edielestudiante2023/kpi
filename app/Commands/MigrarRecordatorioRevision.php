<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigrarRecordatorioRevision extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:recordatorio-revision';
    protected $description = 'Agrega columnas de tracking para recordatorios de revision en tabla actividades';
    protected $usage       = 'migrate:recordatorio-revision [host] [port] [user] [pass] [db]';
    protected $arguments   = [
        'host' => 'Host del servidor MySQL (omitir para usar local)',
        'port' => 'Puerto del servidor',
        'user' => 'Usuario de la BD',
        'pass' => 'Contraseña de la BD',
        'db'   => 'Nombre de la base de datos',
    ];

    public function run(array $params)
    {
        // Determinar si se usan credenciales externas o la conexión local
        $host = $params[0] ?? null;
        $port = $params[1] ?? '3306';
        $user = $params[2] ?? null;
        $pass = $params[3] ?? null;
        $db   = $params[4] ?? null;

        if ($host) {
            CLI::write("Conectando a: {$host}:{$port}/{$db}", 'yellow');
            $dsn = "mysql:host={$host};port={$port};dbname={$db}";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_SSL_CA => true,
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];
            try {
                $pdo = new \PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                CLI::error('Error de conexión: ' . $e->getMessage());
                return;
            }
        } else {
            CLI::write('Usando conexión LOCAL (config por defecto)', 'yellow');
            $dbConfig = new \Config\Database();
            $default = $dbConfig->default;
            $dsn = "mysql:host={$default['hostname']};port={$default['port']};dbname={$default['database']}";
            try {
                $pdo = new \PDO($dsn, $default['username'], $default['password'], [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]);
            } catch (\PDOException $e) {
                CLI::error('Error de conexión local: ' . $e->getMessage());
                return;
            }
        }

        CLI::write('Conexión establecida correctamente.', 'green');

        // Verificar si las columnas ya existen
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'actividades' AND COLUMN_NAME IN ('revision_recordatorios_hoy', 'revision_recordatorio_fecha')");
        $stmt->execute();
        $existentes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $cambios = 0;

        if (!in_array('revision_recordatorios_hoy', $existentes)) {
            CLI::write('Agregando columna: revision_recordatorios_hoy...', 'yellow');
            $pdo->exec("ALTER TABLE actividades ADD COLUMN revision_recordatorios_hoy TINYINT DEFAULT 0");
            CLI::write('  -> Columna revision_recordatorios_hoy agregada.', 'green');
            $cambios++;
        } else {
            CLI::write('Columna revision_recordatorios_hoy ya existe. Omitida.', 'white');
        }

        if (!in_array('revision_recordatorio_fecha', $existentes)) {
            CLI::write('Agregando columna: revision_recordatorio_fecha...', 'yellow');
            $pdo->exec("ALTER TABLE actividades ADD COLUMN revision_recordatorio_fecha DATE DEFAULT NULL");
            CLI::write('  -> Columna revision_recordatorio_fecha agregada.', 'green');
            $cambios++;
        } else {
            CLI::write('Columna revision_recordatorio_fecha ya existe. Omitida.', 'white');
        }

        CLI::write('');
        if ($cambios > 0) {
            CLI::write("Migración completada: {$cambios} columna(s) agregada(s).", 'green');
        } else {
            CLI::write('Sin cambios necesarios. La BD ya está actualizada.', 'green');
        }
    }
}
