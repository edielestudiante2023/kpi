<?php

namespace App\Models;

use CodeIgniter\Model;

class ActividadArchivoModel extends Model
{
    protected $table            = 'actividad_archivos';
    protected $primaryKey       = 'id_archivo';
    protected $allowedFields    = [
        'id_actividad',
        'id_usuario',
        'nombre_original',
        'nombre_servidor',
        'tipo_mime',
        'tamanio',
        'created_at'
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    /**
     * Obtiene archivos de una actividad
     */
    public function getArchivosPorActividad($idActividad)
    {
        return $this->db->table('actividad_archivos aa')
            ->select('aa.*, u.nombre_completo')
            ->join('users u', 'u.id_users = aa.id_usuario')
            ->where('aa.id_actividad', $idActividad)
            ->orderBy('aa.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }
}
