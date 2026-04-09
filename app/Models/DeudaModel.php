<?php namespace App\Models;

use CodeIgniter\Model;

class DeudaModel extends Model
{
    protected $table      = 'tbl_deudas';
    protected $primaryKey = 'id_deuda';

    protected $allowedFields = [
        'concepto', 'acreedor', 'monto_original',
        'fecha_registro', 'fecha_vencimiento', 'estado', 'notas',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
