<?php namespace App\Models;

use CodeIgniter\Model;

class AreaModel extends Model
{
    protected $table      = 'areas';
    protected $primaryKey = 'id_areas';

    protected $allowedFields = [
        'nombre_area',
        'descripcion_area',
        'estado_area',
    ];

    protected $returnType     = 'array';
    protected $useTimestamps  = false;
}