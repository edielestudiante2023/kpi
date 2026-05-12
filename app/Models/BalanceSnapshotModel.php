<?php namespace App\Models;

use CodeIgniter\Model;

class BalanceSnapshotModel extends Model
{
    protected $table      = 'tbl_balance_snapshot';
    protected $primaryKey = 'id_snapshot';

    protected $allowedFields = [
        'fecha_corte',
        'cartera_sst', 'cartera_rps',
        'saldo_banco_sst', 'saldo_banco_rps',
        'total_activos', 'total_pasivos', 'estado_empresa',
        'detalle_pasivos', 'notas', 'creado_por',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
