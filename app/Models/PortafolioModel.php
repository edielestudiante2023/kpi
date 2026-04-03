<?php namespace App\Models;

use CodeIgniter\Model;

class PortafolioModel extends Model
{
    protected $table      = 'tbl_portafolios';
    protected $primaryKey = 'id_portafolio';

    protected $allowedFields = [
        'portafolio',
    ];

    protected $returnType     = 'array';
    protected $useTimestamps  = false;
}
