<?php

namespace App\Models;

use CodeIgniter\Model;

class CentroCostoModel extends Model
{
    protected $table      = 'centros_costo';
    protected $primaryKey = 'id_centro_costo';
    protected $returnType = 'array';

    protected $allowedFields = [
        'nombre',
        'descripcion',
        'activo',
        'created_by',
    ];

    protected $useTimestamps = false;

    public function getActivos()
    {
        return $this->where('activo', 1)
                     ->orderBy('nombre', 'ASC')
                     ->findAll();
    }
}
