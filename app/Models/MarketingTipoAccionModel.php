<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketingTipoAccionModel extends Model
{
    protected $table         = 'tbl_marketing_tipo_accion';
    protected $primaryKey    = 'id_tipo_accion';
    protected $allowedFields = ['nombre', 'color', 'activa'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getActivos(): array
    {
        return $this->where('activa', 1)->orderBy('nombre', 'ASC')->findAll();
    }
}
