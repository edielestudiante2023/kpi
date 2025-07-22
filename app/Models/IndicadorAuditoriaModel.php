<?php namespace App\Models;

use CodeIgniter\Model;

class IndicadorAuditoriaModel extends Model
{
    protected $table      = 'indicador_auditoria';
    protected $primaryKey = 'id_auditoria';

    protected $allowedFields = [
        'id_historial',
        'editor_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
