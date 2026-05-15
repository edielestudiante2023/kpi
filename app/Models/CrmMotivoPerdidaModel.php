<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmMotivoPerdidaModel extends Model
{
    protected $table         = 'tbl_crm_motivo_perdida';
    protected $primaryKey    = 'id_motivo_perdida';
    protected $allowedFields = ['nombre', 'activa'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getActivos(): array
    {
        return $this->where('activa', 1)->orderBy('nombre', 'ASC')->findAll();
    }
}
