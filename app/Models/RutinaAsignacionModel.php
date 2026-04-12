<?php namespace App\Models;

use CodeIgniter\Model;

class RutinaAsignacionModel extends Model
{
    protected $table      = 'rutinas_asignaciones';
    protected $primaryKey = 'id_asignacion';

    protected $allowedFields = [
        'id_users',
        'id_actividad',
        'activa',
    ];

    protected $returnType     = 'array';
    protected $useTimestamps  = false;
}
