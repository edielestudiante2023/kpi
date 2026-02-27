<?php

namespace App\Models;

use CodeIgniter\Model;

class DiaFestivoModel extends Model
{
    protected $table         = 'dias_festivos';
    protected $primaryKey    = 'id_festivo';
    protected $allowedFields = ['fecha', 'descripcion', 'anio'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getFestivosAnio(int $anio): array
    {
        return $this->where('anio', $anio)
                    ->orderBy('fecha', 'ASC')
                    ->findAll();
    }

    /**
     * Cuenta días hábiles (lunes a viernes, excluyendo festivos) en un rango.
     * $desde y $hasta son strings 'Y-m-d' o 'Y-m-d H:i:s'. Se usan solo las fechas.
     */
    public function contarDiasHabiles(string $desde, string $hasta): int
    {
        $inicio = new \DateTime(substr($desde, 0, 10));
        $fin    = new \DateTime(substr($hasta, 0, 10));

        // Obtener festivos en el rango
        $festivos = $this->where('fecha >=', $inicio->format('Y-m-d'))
                         ->where('fecha <=', $fin->format('Y-m-d'))
                         ->findAll();
        $fechasFestivas = array_column($festivos, 'fecha');

        $dias = 0;
        $actual = clone $inicio;
        while ($actual <= $fin) {
            $diaSemana = (int) $actual->format('N'); // 1=lun, 7=dom
            if ($diaSemana <= 5 && !in_array($actual->format('Y-m-d'), $fechasFestivas)) {
                $dias++;
            }
            $actual->modify('+1 day');
        }

        return $dias;
    }
}
