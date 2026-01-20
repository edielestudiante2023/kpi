<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriaActividadModel extends Model
{
    protected $table            = 'categorias_actividad';
    protected $primaryKey       = 'id_categoria';
    protected $allowedFields    = [
        'nombre_categoria',
        'descripcion',
        'color',
        'estado',
        'created_at'
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    /**
     * Obtiene solo categorías activas
     */
    public function getActivas()
    {
        return $this->where('estado', 'activa')
                    ->orderBy('nombre_categoria', 'ASC')
                    ->findAll();
    }
}
