<?php namespace App\Models;

use CodeIgniter\Model;

class ConciliacionBancariaModel extends Model
{
    protected $table      = 'tbl_conciliacion_bancaria';
    protected $primaryKey = 'id_conciliacion';

    protected $allowedFields = [
        'id_cuenta_banco', 'id_centro_costo', 'llave_item', 'deb_cred',
        'fv', 'item_cliente', 'anio', 'mes', 'semana', 'valor',
        'fecha_sistema', 'documento', 'descripcion_motivo', 'transaccion',
        'oficina_recaudo', 'nit_originador', 'valor_cheque', 'valor_total',
        'referencia_1', 'referencia_2', 'mes_real',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
