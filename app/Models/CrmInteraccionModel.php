<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmInteraccionModel extends Model
{
    protected $table         = 'tbl_crm_interaccion';
    protected $primaryKey    = 'id_interaccion';
    protected $allowedFields = [
        'id_oportunidad', 'id_empresa', 'id_contacto',
        'tipo', 'asunto', 'detalle',
        'fecha_programada', 'fecha_completada', 'estado',
        'recordatorio_at', 'recordatorio_enviado', 'id_usuario',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Timeline de interacciones de una oportunidad, con nombre del usuario.
     */
    public function getTimeline(int $idOportunidad): array
    {
        $db = \Config\Database::connect();
        return $db->table($this->table . ' i')
            ->select('i.*, u.nombre_completo AS usuario_nombre, c.nombre AS contacto_nombre')
            ->join('users u', 'u.id_users = i.id_usuario', 'left')
            ->join('tbl_crm_contacto c', 'c.id_contacto = i.id_contacto', 'left')
            ->where('i.id_oportunidad', $idOportunidad)
            ->orderBy('COALESCE(i.fecha_completada, i.fecha_programada, i.created_at)', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Interacciones de una empresa (puede o no estar asociada a oportunidad).
     */
    public function getDeEmpresa(int $idEmpresa, int $limit = 50): array
    {
        $db = \Config\Database::connect();
        return $db->table($this->table . ' i')
            ->select('i.*, u.nombre_completo AS usuario_nombre, o.titulo AS oportunidad_titulo, o.codigo AS oportunidad_codigo')
            ->join('users u', 'u.id_users = i.id_usuario', 'left')
            ->join('tbl_crm_oportunidad o', 'o.id_oportunidad = i.id_oportunidad', 'left')
            ->where('i.id_empresa', $idEmpresa)
            ->orderBy('i.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Pendientes para recordatorio (Command CLI las consume).
     */
    public function getPendientesParaRecordatorio(): array
    {
        return $this->where('estado', 'pendiente')
                    ->where('recordatorio_enviado', 0)
                    ->where('recordatorio_at <=', date('Y-m-d H:i:s'))
                    ->where('recordatorio_at IS NOT NULL', null, false)
                    ->findAll();
    }

    /**
     * Tareas pendientes asignadas al usuario actual (para dashboard / nav badge).
     */
    public function getPendientesUsuario(int $idUsuario): array
    {
        return $this->where('id_usuario', $idUsuario)
                    ->where('estado', 'pendiente')
                    ->orderBy('fecha_programada', 'ASC')
                    ->findAll();
    }
}
