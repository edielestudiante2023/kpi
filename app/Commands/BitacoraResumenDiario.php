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
    protected $usage       = 'bitacora:resumen-diario [fecha]';
    protected $arguments   = [
        'fecha' => 'Fecha a reportar (YYYY-MM-DD). Si no se indica, reporta ayer.',
    ];

    public function run(array $params)
    {
        $fecha = $params[0] ?? null;

        if ($fecha) {
            CLI::write("Enviando reporte para fecha especifica: {$fecha}", 'yellow');
        } else {
            CLI::write('Iniciando envio de reportes de bitacora (ayer)...', 'yellow');
        }

        $notificador = new NotificadorBitacora();
        $resultados = $notificador->enviarTodosLosReportes($fecha);

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
