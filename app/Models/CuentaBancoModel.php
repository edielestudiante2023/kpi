<?php namespace App\Models;

use CodeIgniter\Model;

class CuentaBancoModel extends Model
{
    protected $table      = 'tbl_cuentas_banco';
    protected $primaryKey = 'id_cuenta_banco';

    protected $allowedFields = [
        'nombre_cuenta', 'saldo_inicial', 'fecha_saldo_inicial',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
