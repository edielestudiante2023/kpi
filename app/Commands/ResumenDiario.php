<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificadorActividades;

class ResumenDiario extends BaseCommand
{
    protected $group       = 'Actividades';
    protected $name        = 'actividades:resumen-diario';
    protected $description = 'Envia el resumen diario de actividades a todos los usuarios';
    protected $usage       = 'actividades:resumen-diario';

    public function run(array $params)
    {
        CLI::write('Iniciando envio de resumen diario...', 'yellow');

        $notificador = new NotificadorActividades();
        $resultados = $notificador->enviarResumenDiario();

        CLI::write('');
        CLI::write('=== RESULTADOS ===', 'green');
        CLI::write("Emails enviados: {$resultados['enviados']}", 'green');
        CLI::write("Errores: {$resultados['errores']}", $resultados['errores'] > 0 ? 'red' : 'white');
        CLI::write("Usuarios sin actividades: {$resultados['sin_actividades']}", 'white');
        CLI::write('');

        if ($resultados['errores'] > 0) {
            CLI::write('Revisa los logs para mas detalles sobre los errores.', 'yellow');
        }

        CLI::write('Proceso completado.', 'green');
    }
}
