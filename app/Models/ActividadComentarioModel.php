<?php

namespace App\Models;

use CodeIgniter\Model;

class ActividadComentarioModel extends Model
{
    protected $table            = 'actividad_comentarios';
    protected $primaryKey       = 'id_comentario';
    protected $allowedFields    = [
        'id_actividad',
        'id_usuario',
        'comentario',
        'es_interno',
        'created_at'
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    /**
     * Obtiene comentarios de una actividad con datos del usuario
     */
    public function getComentariosPorActividad($idActividad, $incluirInternos = true)
    {
        $builder = $this->db->table('actividad_comentarios ac')
            ->select('ac.*, u.nombre_completo')
            ->join('users u', 'u.id_users = ac.id_usuario')
            ->where('ac.id_actividad', $idActividad)
            ->orderBy('ac.created_at', 'ASC');

        if (!$incluirInternos) {
            $builder->where('ac.es_interno', 0);
        }

        return $builder->get()->getResultArray();
    }
}
