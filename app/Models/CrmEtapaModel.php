<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmEtapaModel extends Model
{
    protected $table         = 'tbl_crm_etapa';
    protected $primaryKey    = 'id_etapa';
    protected $allowedFields = ['nombre', 'orden', 'probabilidad_default', 'color', 'tipo', 'activa'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Etapas activas, ordenadas por `orden`, con las de cierre (ganada/perdida) al final.
     */
    public function getOrdenadas(bool $soloActivas = true): array
    {
        $b = $this->orderBy("FIELD(tipo, 'abierta', 'ganada', 'perdida')", '', false)
                  ->orderBy('orden', 'ASC');
        if ($soloActivas) $b->where('activa', 1);
        return $b->findAll();
    }

    /**
     * Sólo las abiertas (para Kanban del pipeline activo, sin Ganada/Perdida).
     */
    public function getAbiertas(): array
    {
        return $this->where('activa', 1)->where('tipo', 'abierta')
                    ->orderBy('orden', 'ASC')->findAll();
    }
}
