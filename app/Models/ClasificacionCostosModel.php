<?php namespace App\Models;

use CodeIgniter\Model;

class ClasificacionCostosModel extends Model
{
    protected $table      = 'tbl_clasificacion_costos';
    protected $primaryKey = 'id_clasificacion';

    protected $allowedFields = [
        'llave_item', 'categoria', 'tipo',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
