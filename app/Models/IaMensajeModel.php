<?php namespace App\Models;

use CodeIgniter\Model;

class IaMensajeModel extends Model
{
    protected $table      = 'tbl_ia_mensaje';
    protected $primaryKey = 'id_mensaje';

    protected $allowedFields = [
        'id_conversacion', 'rol', 'contenido', 'tool_calls',
        'tokens_input', 'tokens_output', 'tokens_cache_read', 'tokens_cache_write',
        'modelo', 'costo_usd',
    ];

    protected $returnType    = 'array';
    // No usamos useTimestamps porque tbl_ia_mensaje solo tiene created_at
    // (con DEFAULT CURRENT_TIMESTAMP), no updated_at. CI4 con $updatedField=null
    // genera SQL malformado, asi que el timestamp lo pone MySQL.
    protected $useTimestamps = false;

    /**
     * Costo acumulado del mes corriente (USD)
     */
    public function costoMesActual(): float
    {
        $row = $this->selectSum('costo_usd')
            ->where('YEAR(created_at)', date('Y'))
            ->where('MONTH(created_at)', date('n'))
            ->get()->getRow();
        return (float) ($row->costo_usd ?? 0);
    }
}
