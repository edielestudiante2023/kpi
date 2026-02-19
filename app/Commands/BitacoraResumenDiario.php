<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificadorBitacora;

class BitacoraResumenDiario extends BaseCommand
{
    protected $group       = 'Bitacora';
    protected $name        = 'bitacora:resumen-diario';
    protected $description = 'Envia el reporte diario de bitacora de cada usuario a Edison y Diana';
    protected $usage       = 'bitacora:resumen-diario';

    public function run(array $params)
    {
        CLI::write('Iniciando envio de reportes de bitacora...', 'yellow');

        $notificador = new NotificadorBitacora();
        $resultados = $notificador->enviarTodosLosReportes();

        CLI::write('');
        CLI::write('=== RESULTADOS ===', 'green');

        if (!empty($resultados['mensaje'])) {
            CLI::write($resultados['mensaje'], 'yellow');
        }

        if (!empty($resultados['fecha_reportada'])) {
            CLI::write("Fecha reportada: {$resultados['fecha_reportada']}", 'white');
        }

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
