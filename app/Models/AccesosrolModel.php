<?php

namespace App\Models;

use CodeIgniter\Model;

class AccesosrolModel extends Model
{
    protected $table            = 'accesos_rol';
    protected $primaryKey       = 'id_acceso';
    protected $allowedFields    = ['id_roles', 'detalle', 'enlace', 'estado'];
    protected $returnType       = 'array';

    public function getAccesosConRol()
    {
        return $this->select('accesos_rol.*, roles.nombre_rol')
                    ->join('roles', 'roles.id_roles = accesos_rol.id_roles')
                    ->orderBy('roles.nombre_rol ASC, accesos_rol.detalle ASC')
                    ->findAll();
    }
}
