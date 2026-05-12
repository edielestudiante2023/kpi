<?php namespace App\Models;

use CodeIgniter\Model;

class PresupuestoPortafolioModel extends Model
{
    protected $table      = 'tbl_presupuesto_portafolio';
    protected $primaryKey = 'id_presupuesto';

    protected $allowedFields = [
        'id_portafolio', 'anio', 'mes', 'presupuesto',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
