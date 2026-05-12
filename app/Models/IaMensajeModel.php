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
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null; // no updated_at en esta tabla

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
