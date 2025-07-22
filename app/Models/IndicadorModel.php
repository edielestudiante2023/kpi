<?php
namespace App\Models;

use CodeIgniter\Model;

class IndicadorModel extends Model
{
    protected $table            = 'indicadores';
    protected $primaryKey       = 'id_indicador';
    protected $allowedFields    = [
        'nombre',
        'periodicidad',
        'ponderacion',
        'meta_valor',
        'meta_descripcion',
        'tipo_meta',
        'metodo_calculo',
        'activo',
        'unidad',
        'objetivo_proceso',
        'objetivo_calidad',
        'tipo_aplicacion',
        'created_at'
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = ''; // Si deseas manejar updated_at, cámbialo por el nombre del campo
}
