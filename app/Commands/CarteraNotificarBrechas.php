<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificadorCartera;

class CarteraNotificarBrechas extends BaseCommand
{
    protected $group       = 'Cartera';
    protected $name        = 'cartera:notificar-brechas';
    protected $description = 'Envia emails a clientes con facturas en estado brecha (diferencia >= $2.000). Solo dias 1 y 16 de cada mes, salvo que se fuerce.';
    protected $usage       = 'cartera:notificar-brechas [--forzar]';
    protected $options     = [
        '--forzar' => 'Enviar aunque no sea lunes',
    ];

    public function run(array $params)
    {
        $forzar = CLI::getOption('forzar') !== null;

        if ($forzar) {
            CLI::write('Modo forzado: enviando sin importar el dia.', 'yellow');
        } else {
            CLI::write('Verificando brechas de cartera (dias 1 y 16)...', 'yellow');
        }

        $notificador = new NotificadorCartera();
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
