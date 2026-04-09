<?php namespace App\Models;

use CodeIgniter\Model;

class DeudaAbonoModel extends Model
{
    protected $table      = 'tbl_deuda_abonos';
    protected $primaryKey = 'id_abono';

    protected $allowedFields = [
        'id_deuda', 'fecha_abono', 'valor_abono', 'referencia',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
