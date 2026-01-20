<?php

namespace App\Models;

use CodeIgniter\Model;

class PreferenciaNotificacionModel extends Model
{
    protected $table = 'preferencias_notificacion';
    protected $primaryKey = 'id_preferencia';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_usuario',
        'notif_asignacion',
        'notif_cambio_estado',
        'notif_comentarios',
        'notif_vencimiento',
        'resumen_diario'
    ];

    protected $useTimestamps = false;

    /**
     * Obtiene las preferencias de un usuario, o crea valores por defecto si no existen
     */
    public function getPreferenciasUsuario(int $idUsuario): array
    {
        $preferencias = $this->where('id_usuario', $idUsuario)->first();

        if (!$preferencias) {
            // Crear preferencias por defecto
            $preferencias = [
                'id_usuario' => $idUsuario,
                'notif_asignacion' => 1,
                'notif_cambio_estado' => 1,
                'notif_comentarios' => 1,
                'notif_vencimiento' => 1,
                'resumen_diario' => 0
            ];
            $this->insert($preferencias);
            $preferencias['id_preferencia'] = $this->getInsertID();
        }

        return $preferencias;
    }

    /**
     * Actualiza las preferencias de un usuario
     */
    public function actualizarPreferencias(int $idUsuario, array $datos): bool
    {
        $preferencias = $this->where('id_usuario', $idUsuario)->first();

        if ($preferencias) {
            return $this->update($preferencias['id_preferencia'], $datos);
        } else {
            $datos['id_usuario'] = $idUsuario;
            return $this->insert($datos) !== false;
        }
    }

    /**
     * Verifica si un usuario quiere recibir un tipo específico de notificación
     */
    public function quiereNotificacion(int $idUsuario, string $tipoNotificacion): bool
    {
        $campo = 'notif_' . $tipoNotificacion;
        $preferencias = $this->getPreferenciasUsuario($idUsuario);

        return isset($preferencias[$campo]) && $preferencias[$campo] == 1;
    }
}
