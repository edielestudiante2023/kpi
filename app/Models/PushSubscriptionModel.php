<?php

namespace App\Models;

use CodeIgniter\Model;

class PushSubscriptionModel extends Model
{
    protected $table         = 'push_subscriptions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['id_usuario', 'endpoint', 'p256dh', 'auth', 'created_at'];
    protected $useTimestamps = false;

    /**
     * Guardar o actualizar suscripción push de un usuario
     */
    public function guardarSuscripcion(int $idUsuario, string $endpoint, string $p256dh, string $auth): bool
    {
        // Eliminar suscripciones anteriores con el mismo endpoint
        $this->where('endpoint', $endpoint)->delete();

        return $this->insert([
            'id_usuario'  => $idUsuario,
            'endpoint'    => $endpoint,
            'p256dh'      => $p256dh,
            'auth'        => $auth,
            'created_at'  => date('Y-m-d H:i:s'),
        ]) !== false;
    }

    /**
     * Obtener suscripciones de un usuario
     */
    public function getSuscripciones(int $idUsuario): array
    {
        return $this->where('id_usuario', $idUsuario)->findAll();
    }

    /**
     * Eliminar suscripción por endpoint
     */
    public function eliminarPorEndpoint(string $endpoint): void
    {
        $this->where('endpoint', $endpoint)->delete();
    }
}
