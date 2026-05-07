<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Migración: agregar columna `cliente` a `bitacora_actividades`.
 *
 * Uso:
 *   php spark bitacora:add-cliente
 *
 * - Aplica idempotente: si la columna ya existe, no la vuelve a crear.
 * - Default 'FRAMEWORK' garantiza que registros existentes queden con ese valor.
 * - Conecta a la BD configurada en .env (local en dev, prod en servidor prod).
 */
class BitacoraAddCliente extends BaseCommand
{
    protected $group       = 'Bitacora';
    protected $name        = 'bitacora:add-cliente';
    protected $description = 'Agrega columna cliente VARCHAR(150) NOT NULL DEFAULT FRAMEWORK a bitacora_actividades';
    protected $usage       = 'bitacora:add-cliente';

    public function run(array $params)
    {
        CLI::write('=== Migración: bitacora_actividades.cliente ===', 'yellow');

        $db = \Config\Database::connect();
        $hostname = $db->hostname ?? '?';
        $database = $db->database ?? '?';
        CLI::write("Host:     {$hostname}", 'white');
        CLI::write("Database: {$database}", 'white');
        CLI::write('');

        // 1. Verificar si la columna ya existe
        $existe = $db->query("
            SELECT COUNT(*) AS n
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = 'bitacora_actividades'
              AND COLUMN_NAME  = 'cliente'
        ", [$database])->getRow()->n ?? 0;

        if ((int) $existe > 0) {
            CLI::write('La columna `cliente` ya existe. Nada que hacer.', 'green');
            return;
        }

        // 2. Crear columna
        CLI::write('Agregando columna `cliente`...', 'yellow');
        $db->query("
            ALTER TABLE bitacora_actividades
            ADD COLUMN cliente VARCHAR(150) NOT NULL DEFAULT 'FRAMEWORK'
            AFTER descripcion
        ");
        CLI::write('Columna creada.', 'green');

        // 3. Asegurar valor en registros existentes (por si hubiese NULL)
        $db->query("
            UPDATE bitacora_actividades
            SET cliente = 'FRAMEWORK'
            WHERE cliente IS NULL OR cliente = ''
        ");
        $afectados = $db->affectedRows();
        CLI::write("Registros normalizados a 'FRAMEWORK': {$afectados}", 'green');

        CLI::write('');
        CLI::write('Migración completada.', 'green');
    }
}
