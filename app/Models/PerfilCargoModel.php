<?php namespace App\Models;

use CodeIgniter\Model;

class PerfilCargoModel extends Model
{
    protected $table      = 'perfiles_cargo';
    protected $primaryKey = 'id_perfil_cargo';

    protected $allowedFields = [
        'nombre_cargo',
        'area',
        'jefe_inmediato',
        'colaboradores_a_cargo',
        'created_at'
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at'; // En caso de que lo agregues luego
}
