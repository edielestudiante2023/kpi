<?php namespace App\Models;

use CodeIgniter\Model;

class MovimientoBancarioCrudoModel extends Model
{
    protected $table      = 'tbl_movimiento_bancario_crudo';
    protected $primaryKey = 'id_movimiento_crudo';

    protected $allowedFields = [
        'id_cuenta_banco', 'fecha_sistema', 'documento', 'descripcion_motivo',
        'transaccion', 'oficina_recaudo', 'id_origen_destino',
        'valor_cheque', 'valor_total', 'referencia_1', 'referencia_2',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
