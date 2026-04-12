<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificadorRutinas;

class RutinasEnviarDiario extends BaseCommand
{
    protected $group       = 'Rutinas';
    protected $name        = 'rutinas:enviar-diario';
    protected $description = 'Envia el email diario de rutinas a cada usuario con actividades asignadas (L-V)';
    protected $usage       = 'rutinas:enviar-diario [fecha]';
    protected $arguments   = [
        'fecha' => 'Fecha de la rutina (YYYY-MM-DD). Si no se indica, usa hoy.',
    ];

    public function run(array $params)
    {
        $fecha = $params[0] ?? null;

        CLI::write('=== Rutinas: Envio diario ===', 'yellow');
        if ($fecha) {
            CLI::write("Fecha manual: {$fecha}", 'white');
        }

        $notificador = new NotificadorRutinas();
        $resultado = $notificador->enviarRecordatoriosDiarios($fecha);

        CLI::write('');
        CLI::write("Fecha: {$resultado['fecha']}", 'white');

        if ($resultado['omitidos'] === -1) {
            CLI::write('Fin de semana — no se envian emails.', 'yellow');
            return;
        }

        CLI::write("Emails enviados: {$resultado['enviados']}", 'green');
        CLI::write("Errores: {$resultado['errores']}", $resultado['errores'] > 0 ? 'red' : 'white');
        CLI::write("Omitidos (sin actividades): {$resultado['omitidos']}", 'white');
        CLI::write('');
        CLI::write('Proceso completado.', 'green');
    }
}
