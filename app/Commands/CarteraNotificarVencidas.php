<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificadorCarteraVencida;

class CarteraNotificarVencidas extends BaseCommand
{
    protected $group       = 'Cartera';
    protected $name        = 'cartera:notificar-vencidas';
    protected $description = 'Envia emails a clientes con facturas vencidas (>30 dias). Cada dos jueves (semanas pares), salvo que se fuerce.';
    protected $usage       = 'cartera:notificar-vencidas [--forzar]';
    protected $options     = [
        '--forzar' => 'Enviar aunque no sea jueves de semana par',
    ];

    public function run(array $params)
    {
        $forzar = CLI::getOption('forzar') !== null;

        if ($forzar) {
            CLI::write('Modo forzado: enviando sin importar el dia.', 'yellow');
        } else {
            CLI::write('Verificando cartera vencida (jueves cada 2 semanas)...', 'yellow');
        }

        $notificador = new NotificadorCarteraVencida();
        $resultado = $notificador->ejecutar($forzar);

        CLI::write('');
        CLI::write('=== RESULTADOS ===', 'green');
        CLI::write("Emails enviados: {$resultado['enviados']}", 'green');

        if ($resultado['sin_email'] > 0) {
            CLI::write("Clientes sin email: {$resultado['sin_email']}", 'yellow');
        }
        if ($resultado['errores'] > 0) {
            CLI::write("Errores de envio: {$resultado['errores']}", 'red');
        }

        CLI::write('');
        foreach ($resultado['detalle'] as $linea) {
            CLI::write("  {$linea}", 'white');
        }
    }
}
