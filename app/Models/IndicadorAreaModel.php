<?php

namespace App\Models;

use CodeIgniter\Model;

class IndicadorAreaModel extends Model
{
    protected $table         = 'indicadores_area';
    protected $primaryKey    = 'id_indicador_area';
    protected $allowedFields = [
        'id_indicador',
        'id_areas',
        'meta',
        'ponderacion',
        'periodicidad'
    ];
    protected $returnType    = 'array';
    public    $useTimestamps = false;

    /**
     * Obtener todos los indicadores asociados a un área específica
     */
    public function getIndicadoresPorArea($idArea)
    {
        return $this->select('
                indicadores_area.*,
                indicadores.nombre         AS nombre_indicador,
                indicadores.metodo_calculo,
                indicadores.unidad,
                indicadores.objetivo_proceso,
                indicadores.objetivo_calidad
            ')
            ->join('indicadores', 'indicadores.id_indicador = indicadores_area.id_indicador')
            ->where('indicadores_area.id_areas', $idArea)
            ->findAll();
    }

    /**
     * Obtener indicadores de todas las áreas activas con nombre de área
     */
    public function getIndicadoresConNombreArea()
    {
        return $this->select('
                indicadores_area.*,
                indicadores.nombre     AS nombre_indicador,
                areas.nombre_area
            ')
            ->join('indicadores', 'indicadores.id_indicador = indicadores_area.id_indicador')
            ->join('areas', 'areas.id_areas = indicadores_area.id_areas')
            ->where('areas.estado_area', 'activa')
            ->orderBy('areas.nombre_area', 'ASC')
            ->findAll();
    }
}
