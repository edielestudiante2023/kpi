<?php namespace App\Models;

use CodeIgniter\Model;

class RutinaActividadModel extends Model
{
    protected $table      = 'rutinas_actividades';
    protected $primaryKey = 'id_actividad';

    protected $allowedFields = [
        'nombre',
        'descripcion',
        'frecuencia',
        'peso',
        'activa',
    ];

    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
}
