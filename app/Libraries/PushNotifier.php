<?php

namespace App\Libraries;

use App\Models\PushSubscriptionModel;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotifier
{
    protected $webPush;
    protected $subscriptionModel;

    public function __construct()
    {
        $this->subscriptionModel = new PushSubscriptionModel();

        $auth = [
            'VAPID' => [
                'subject'    => 'mailto:' . (env('BITACORA_REPORT_EMAILS') ? explode(',', env('BITACORA_REPORT_EMAILS'))[0] : 'admin@cycloidtalent.com'),
                'publicKey'  => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setAutomaticPadding(false);
    }

    /**
     * Envía notificación push a un usuario específico
     */
    public function notificarUsuario(int $idUsuario, string $titulo, string $cuerpo, string $url = '/bitacora'): array
    {
        $suscripciones = $this->subscriptionModel->getSuscripciones($idUsuario);
        $resultado = ['enviados' => 0, 'errores' => 0, 'eliminados' => 0];

        if (empty($suscripciones)) {
            return $resultado;
        }

        $payload = json_encode([
            'title' => $titulo,
            'body'  => $cuerpo,
            'url'   => $url,
        ]);

        foreach ($suscripciones as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'publicKey' => $sub['p256dh'],
                'authToken' => $sub['auth'],
            ]);

            $this->webPush->queueNotification($subscription, $payload);
        }

        // Enviar todas las notificaciones encoladas
        foreach ($this->webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $resultado['enviados']++;
            } else {
                // Si el endpoint ya no es válido (usuario desinstaló la PWA), eliminarlo
                if ($report->isSubscriptionExpired()) {
                    $this->subscriptionModel->eliminarPorEndpoint($report->getEndpoint());
                    $resultado['eliminados']++;
                } else {
                    $resultado['errores']++;
                    log_message('error', 'PushNotifier: Error - ' . $report->getReason());
                }
            }
        }

        return $resultado;
    }
}
