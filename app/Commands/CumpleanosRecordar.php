<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificadorCumpleanos;

class CumpleanosRecordar extends BaseCommand
{
    protected $group       = 'Cumpleanos';
    protected $name        = 'cumpleanos:recordar';
    protected $description = 'Envia recordatorio diario de cumpleanos a todos menos al cumpleanero (30 dias antes). Respeta silenciados.';
    protected $usage       = 'cumpleanos:recordar [fecha]';
    protected $arguments   = [
        'fecha' => 'Fecha de referencia (YYYY-MM-DD). Si no se indica, usa hoy.',
    ];

    public function run(array $params)
    {
        $fecha = $params[0] ?? null;

        CLI::write('=== Recordatorio de Cumpleanos ===', 'yellow');
        if ($fecha) CLI::write("Fecha manual: {$fecha}", 'white');

        $notificador = new NotificadorCumpleanos();
        $resultado = $notificador->ejecutar($fecha);

        CLI::write('');
        CLI::write('=== RESULTADOS ===', 'green');
        CLI::write("Cumpleaneros en ventana (30 dias): {$resultado['cumpleaneros']}", 'white');
        CLI::write("Silenciados: {$resultado['silenciados']}", 'white');
        CLI::write("Emails enviados: {$resultado['emails']}", 'green');
        CLI::write("Errores: {$resultado['errores']}", $resultado['errores'] > 0 ? 'red' : 'white');
        CLI::write('');
        foreach ($resultado['detalle'] as $linea) {
            CLI::write("  {$linea}", 'white');
        }
    }
}
