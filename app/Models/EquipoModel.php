<?php namespace App\Models;

use CodeIgniter\Model;

class EquipoModel extends Model
{
    protected $table      = 'equipos';
    protected $primaryKey = 'id_equipos';

    protected $allowedFields = [
        'id_jefe',
        'id_subordinado',
        'fecha_asignacion',
        'estado_relacion',
    ];

    protected $returnType     = 'array';
    protected $useTimestamps  = false;
}