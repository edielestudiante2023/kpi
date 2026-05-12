<?php namespace App\Models;

use CodeIgniter\Model;

class IaConversacionModel extends Model
{
    protected $table      = 'tbl_ia_conversacion';
    protected $primaryKey = 'id_conversacion';

    protected $allowedFields = [
        'titulo', 'tipo', 'id_snapshot_ref', 'creado_por',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
