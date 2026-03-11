<?php

namespace App\Models;

use CodeIgniter\Model;

class NovedadColectivaModel extends Model
{
    protected $table         = 'novedades_colectivas';
    protected $primaryKey    = 'id_novedad_colectiva';
    protected $allowedFields = ['fecha', 'descripcion', 'horas_reduccion', 'anio', 'created_by'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getNovedadesAnio(int $anio): array
    {
        return $this->where('anio', $anio)
                    ->orderBy('fecha', 'ASC')
                    ->findAll();
    }

    /**
     * Suma de horas de reducción colectivas en un rango (base jornada completa 8h).
     */
    public function getHorasColectivasRango(string $desde, string $hasta): float
    {
        $result = $this->selectSum('horas_reduccion', 'total')
                       ->where('fecha >=', substr($desde, 0, 10))
                       ->where('fecha <=', substr($hasta, 0, 10))
                       ->first();
        return (float) ($result['total'] ?? 0);
    }
}
