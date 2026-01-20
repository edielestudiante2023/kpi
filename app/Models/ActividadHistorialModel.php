<?php

namespace App\Models;

use CodeIgniter\Model;

class ActividadHistorialModel extends Model
{
    protected $table            = 'actividad_historial';
    protected $primaryKey       = 'id_historial';
    protected $allowedFields    = [
        'id_actividad',
        'id_usuario',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'created_at'
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    /**
     * Obtiene historial de una actividad con datos del usuario
     */
    public function getHistorialPorActividad($idActividad)
    {
        return $this->db->table('actividad_historial ah')
            ->select('ah.*, u.nombre_completo')
            ->join('users u', 'u.id_users = ah.id_usuario')
            ->where('ah.id_actividad', $idActividad)
            ->orderBy('ah.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }
}
