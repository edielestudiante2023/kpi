<?php namespace App\Models;

use CodeIgniter\Model;

class CentroCostoConciliacionModel extends Model
{
    protected $table      = 'tbl_centros_costo';
    protected $primaryKey = 'id_centro_costo';

    protected $allowedFields = [
        'centro_costo',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
