<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmFuenteModel extends Model
{
    protected $table         = 'tbl_crm_fuente';
    protected $primaryKey    = 'id_fuente';
    protected $allowedFields = ['nombre', 'activa'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getActivas(): array
    {
        return $this->where('activa', 1)->orderBy('nombre', 'ASC')->findAll();
    }
}
