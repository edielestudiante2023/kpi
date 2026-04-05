<?php

namespace App\Models;

use CodeIgniter\Model;

class DiaHabilConfigModel extends Model
{
    protected $table         = 'dias_habiles_config';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['anio', 'mes', 'dia', 'created_by'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Obtiene los días hábiles configurados para un mes/año.
     * @return int[] Lista de números de día (1-31)
     */
    public function getDiasHabiles(int $anio, int $mes): array
    {
        $rows = $this->where('anio', $anio)
                     ->where('mes', $mes)
                     ->orderBy('dia', 'ASC')
                     ->findAll();
        return array_map(fn($r) => (int) $r['dia'], $rows);
    }

    /**
     * ¿Existe configuración manual para este mes?
     */
    public function tieneConfiguracion(int $anio, int $mes): bool
    {
        return $this->where('anio', $anio)->where('mes', $mes)->countAllResults() > 0;
    }

    /**
     * Reemplaza toda la configuración de un mes con los días indicados.
     * @param int[] $dias Lista de números de día hábil
     */
    public function guardarMes(int $anio, int $mes, array $dias, ?int $createdBy = null): void
    {
        // Eliminar configuración anterior del mes
        $this->where('anio', $anio)->where('mes', $mes)->delete();

        // Insertar nuevos días
        foreach ($dias as $d) {
            $this->insert([
                'anio'       => $anio,
                'mes'        => $mes,
                'dia'        => (int) $d,
                'created_by' => $createdBy,
            ]);
        }
    }

    /**
     * Cuenta días hábiles configurados en un rango de fechas.
     * Retorna null si algún mes del rango no tiene configuración (fallback a lógica automática).
     */
    public function contarDiasHabilesRango(string $desde, string $hasta): ?int
    {
        $inicio = new \DateTime(substr($desde, 0, 10));
        $fin    = new \DateTime(substr($hasta, 0, 10));

        // Recopilar todos los meses involucrados
        $meses = [];
        $tmp = clone $inicio;
        while ($tmp <= $fin) {
            $key = $tmp->format('Y-n'); // "2026-4"
            $meses[$key] = ['anio' => (int) $tmp->format('Y'), 'mes' => (int) $tmp->format('n')];
            $tmp->modify('first day of next month');
        }

        // Verificar que todos los meses tengan configuración
        foreach ($meses as $m) {
            if (!$this->tieneConfiguracion($m['anio'], $m['mes'])) {
                return null; // Fallback a lógica automática
            }
        }

        // Contar días configurados que caigan en el rango
        $count = 0;
        $actual = clone $inicio;
        while ($actual <= $fin) {
            $anio = (int) $actual->format('Y');
            $mes  = (int) $actual->format('n');
            $dia  = (int) $actual->format('j');

            $existe = $this->where('anio', $anio)
                           ->where('mes', $mes)
                           ->where('dia', $dia)
                           ->countAllResults() > 0;
            if ($existe) {
                $count++;
            }
            $actual->modify('+1 day');
        }

        return $count;
    }
}
