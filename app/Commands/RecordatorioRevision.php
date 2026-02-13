<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificadorActividades;

class RecordatorioRevision extends BaseCommand
{
    protected $group       = 'Actividades';
    protected $name        = 'actividades:recordatorio-revision';
    protected $description = 'Envia recordatorios a creadores de actividades en revision (max 2/dia)';
    protected $usage       = 'actividades:recordatorio-revision';

    public function run(array $params)
    {
        CLI::write('Enviando recordatorios de revision...', 'yellow');

        $notificador = new NotificadorActividades();
        $resultados = $notificador->enviarRecordatoriosRevision();

        CLI::write('');
        CLI::write('=== RESULTADOS ===', 'green');
        CLI::write("Recordatorios enviados: {$resultados['enviados']}", 'green');
        CLI::write("Errores: {$resultados['errores']}", $resultados['errores'] > 0 ? 'red' : 'white');
        CLI::write("Omitidos (limite alcanzado): {$resultados['omitidos']}", 'white');
        CLI::write('');

        if ($resultados['errores'] > 0) {
            CLI::write('Revisa los logs para mas detalles sobre los errores.', 'yellow');
        }

        CLI::write('Proceso completado.', 'green');
    }
}
