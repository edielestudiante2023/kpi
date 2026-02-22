<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\PushNotifier;
use App\Models\BitacoraActividadModel;
use App\Models\UserModel;

class BitacoraNotificarActivas extends BaseCommand
{
    protected $group       = 'Bitacora';
    protected $name        = 'bitacora:notificar-activas';
    protected $description = 'Envía push notifications a usuarios con actividades activas > 30 min';

    public function run(array $params)
    {
        $bitacoraModel = new BitacoraActividadModel();
        $userModel     = new UserModel();

        // Buscar actividades en progreso
        $activas = $bitacoraModel
            ->where('estado', 'en_progreso')
            ->findAll();

        if (empty($activas)) {
            CLI::write('No hay actividades en progreso.', 'white');
            return;
        }

        $pushNotifier = new PushNotifier();
        $ahora = time();

        foreach ($activas as $act) {
            $inicio = strtotime($act['hora_inicio']);
            $minutos = ($ahora - $inicio) / 60;

            // Solo notificar si lleva más de 25 minutos (margen para el cron de 30 min)
            if ($minutos < 25) {
                CLI::write("Actividad #{$act['id_bitacora']} lleva " . round($minutos) . " min, aún no notificar.", 'white');
                continue;
            }

            $usuario = $userModel->find($act['id_usuario']);
            if (!$usuario) continue;

            $horas = floor($minutos / 60);
            $mins  = round($minutos - ($horas * 60));
            $tiempoTexto = $horas > 0 ? "{$horas}h {$mins}min" : "{$mins} min";

            $resultado = $pushNotifier->notificarUsuario(
                (int) $act['id_usuario'],
                'Actividad en progreso — ' . $tiempoTexto,
                $act['descripcion'] . "\n¿Sigues trabajando en esto?",
                '/bitacora'
            );

            CLI::write(
                "{$usuario['nombre_completo']}: \"{$act['descripcion']}\" ({$tiempoTexto}) → Push: {$resultado['enviados']} enviados, {$resultado['errores']} errores",
                $resultado['enviados'] > 0 ? 'green' : 'yellow'
            );
        }

        CLI::write('Proceso completado.', 'green');
    }
}
