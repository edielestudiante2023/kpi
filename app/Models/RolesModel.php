<?php namespace App\Models;

use CodeIgniter\Model;

class RolesModel extends Model
{
    protected $table      = 'roles';
    protected $primaryKey = 'id_roles';

    protected $allowedFields = [
        'nombre_rol',
    ];

    protected $returnType     = 'array';
    protected $useTimestamps  = false;
}