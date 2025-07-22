<?php

namespace App\Models;

use CodeIgniter\Model;

class IndicadorPerfilModel extends Model
{
    protected $table         = 'indicadores_perfil';
    protected $primaryKey    = 'id_indicador_perfil';
    protected $allowedFields = [
        'id_indicador',
        'id_perfil_cargo',
    ];
    protected $returnType    = 'array';

    /**
     * Obtiene indicadores asignados a un perfil con datos completos del indicador.
     */
    public function getIndicadoresPorPerfil(int $idPerfil): array
    {
        return $this->select([
            'indicadores_perfil.id_indicador_perfil',
            'indicadores_perfil.id_indicador',
            'indicadores_perfil.id_perfil_cargo',

            // Desde la tabla indicadores
            'indicadores.nombre AS nombre_indicador',
            'indicadores.meta_valor',
            'indicadores.meta_descripcion',
            'indicadores.tipo_meta',
            'indicadores.metodo_calculo',
            'indicadores.unidad',
            'indicadores.periodicidad',
            'indicadores.ponderacion',
            'indicadores.objetivo_proceso',
            'indicadores.objetivo_calidad',
            'indicadores.tipo_aplicacion',
            'indicadores.created_at',
        ])
            ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
            ->where('indicadores_perfil.id_perfil_cargo', $idPerfil)
            ->orderBy('indicadores_perfil.id_indicador_perfil', 'ASC')
            ->findAll();
    }

    /**
     * Obtiene todos los indicadores con el nombre del cargo asociado.
     */
    public function getIndicadoresConNombreCargo(): array
    {
        return $this->select([
            'indicadores_perfil.*',
            'indicadores.nombre AS nombre_indicador',
            'perfiles_cargo.nombre_cargo',
        ])
            ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
            ->join('perfiles_cargo', 'perfiles_cargo.id_perfil_cargo = indicadores_perfil.id_perfil_cargo')
            ->orderBy('perfiles_cargo.nombre_cargo', 'ASC')
            ->findAll();
    }

    /**
     * Obtiene indicadores con información completa de cargo y área.
     */
    public function getIndicadoresConCargoYArea(): array
    {
        return $this->db->table('indicadores_perfil ip')
            ->select([
                'ip.id_indicador_perfil',
                'ip.id_indicador',
                'ip.id_perfil_cargo',
                'c.nombre_cargo AS nombre_cargo',
                'a.nombre_area AS nombre_area',
                'i.nombre AS nombre_indicador',
                'i.meta_valor',
                'i.meta_descripcion',
                'i.tipo_meta',
                'i.metodo_calculo',
                'i.unidad',
                'i.periodicidad',
                'i.ponderacion',
                'i.objetivo_proceso',
                'i.objetivo_calidad',
                'i.tipo_aplicacion',
                'i.created_at',
            ])
            ->join('perfiles_cargo c', 'ip.id_perfil_cargo = c.id_perfil_cargo')
            ->join('areas a', 'c.area = a.nombre_area')
            ->join('indicadores i', 'ip.id_indicador = i.id_indicador')
            ->orderBy('a.nombre_area, c.nombre_cargo, i.nombre')
            ->get()
            ->getResultArray();
    }
}
