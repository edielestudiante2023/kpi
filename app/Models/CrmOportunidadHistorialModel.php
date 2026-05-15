<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Historial de cambios de etapa por oportunidad (auditoría del pipeline).
 * Las inserciones se hacen desde CrmOportunidadModel::cambiarEtapa().
 */
class CrmOportunidadHistorialModel extends Model
{
    protected $table         = 'tbl_crm_oportunidad_historial';
    protected $primaryKey    = 'id_historial';
    protected $allowedFields = [
        'id_oportunidad', 'id_etapa_anterior', 'id_etapa_nueva',
        'id_usuario', 'comentario',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Historial de una oportunidad con nombres de etapas y usuario.
     */
    public function getDeOportunidad(int $idOportunidad): array
    {
        $db = \Config\Database::connect();
        return $db->table($this->table . ' h')
            ->select('
                h.*,
                ea.nombre AS etapa_anterior_nombre, ea.color AS etapa_anterior_color,
                en.nombre AS etapa_nueva_nombre,    en.color AS etapa_nueva_color,
                u.nombre_completo AS usuario_nombre
            ')
            ->join('tbl_crm_etapa ea', 'ea.id_etapa = h.id_etapa_anterior', 'left')
            ->join('tbl_crm_etapa en', 'en.id_etapa = h.id_etapa_nueva', 'left')
            ->join('users u', 'u.id_users = h.id_usuario', 'left')
            ->where('h.id_oportunidad', $idOportunidad)
            ->orderBy('h.created_at', 'DESC')
            ->get()->getResultArray();
    }
}
