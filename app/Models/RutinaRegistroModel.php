<?php namespace App\Models;

use CodeIgniter\Model;

class RutinaRegistroModel extends Model
{
    protected $table      = 'rutinas_registros';
    protected $primaryKey = 'id_registro';

    protected $allowedFields = [
        'id_users',
        'id_actividad',
        'fecha',
        'completada',
        'hora_completado',
    ];

    protected $returnType     = 'array';
    protected $useTimestamps  = false;
}
