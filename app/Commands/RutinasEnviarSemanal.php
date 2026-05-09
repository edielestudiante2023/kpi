<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificadorRutinas;

class RutinasEnviarSemanal extends BaseCommand
{
    protected $group       = 'Rutinas';
    protected $name        = 'rutinas:enviar-semanal';
    protected $description = 'Envia el resumen semanal de rutinas (cumplimiento semana anterior + meta nueva). Solo lunes salvo modo manual.';
    protected $usage       = 'rutinas:enviar-semanal [fecha]';
    protected $arguments   = [
        'fecha' => 'Fecha de referencia (YYYY-MM-DD, debe ser un lunes para envio automatico). Si no se indica, usa hoy.',
    ];

    public function run(array $params)
    {
        $fecha = $params[0] ?? null;

        CLI::write('=== Rutinas: Resumen semanal ===', 'yellow');
        if ($fecha) {
            CLI::write("Fecha manual: {$fecha}", 'white');
        }

        $notificador = new NotificadorRutinas();
        $resultado = $notificador->enviarResumenSemanal($fecha);

        CLI::write('');
        CLI::write("Fecha: {$resultado['fecha']}", 'white');

        if (($resultado['omitidos'] ?? 0) === -1) {
            CLI::write('Hoy no es lunes — no se envian emails.', 'yellow');
            return;
        }

        CLI::write("Emails enviados: {$resultado['enviados']}", 'green');
        CLI::write("Errores: {$resultado['errores']}", $resultado['errores'] > 0 ? 'red' : 'white');
        CLI::write("Omitidos (sin actividades): {$resultado['omitidos']}", 'white');
        CLI::write('');
        CLI::write('Proceso completado.', 'green');
    }
}
