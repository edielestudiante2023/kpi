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
        $db = \Config\Database::connect();
        $row = $db->query(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE anio = ? AND mes = ?",
            [$anio, $mes]
        )->getRowArray();
        return ((int) ($row['c'] ?? 0)) > 0;
    }

    /**
     * Reemplaza toda la configuración de un mes con los días indicados.
     * @param int[] $dias Lista de números de día hábil
     */
    public function guardarMes(int $anio, int $mes, array $dias, ?int $createdBy = null): void
    {
        $db = \Config\Database::connect();
        $db->query("DELETE FROM {$this->table} WHERE anio = ? AND mes = ?", [$anio, $mes]);

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
        $db     = \Config\Database::connect();

        // Recopilar todos los meses involucrados
        $meses = [];
        $tmp = clone $inicio;
        while ($tmp <= $fin) {
            $key = $tmp->format('Y-n');
            $meses[$key] = ['anio' => (int) $tmp->format('Y'), 'mes' => (int) $tmp->format('n')];
            $tmp->modify('first day of next month');
        }

        // Verificar que todos los meses tengan configuración
        foreach ($meses as $m) {
            if (!$this->tieneConfiguracion($m['anio'], $m['mes'])) {
                return null;
            }
        }

        // Precargar todos los días configurados del rango en un set PHP
        $diasHabiles = [];
        foreach ($meses as $m) {
            $rows = $db->query(
                "SELECT dia FROM {$this->table} WHERE anio = ? AND mes = ?",
                [$m['anio'], $m['mes']]
            )->getResultArray();
            foreach ($rows as $r) {
                $fecha = sprintf('%04d-%02d-%02d', $m['anio'], $m['mes'], $r['dia']);
                $diasHabiles[$fecha] = true;
            }
        }

        // Contar días del rango que estén en el set
        $count = 0;
        $actual = clone $inicio;
        while ($actual <= $fin) {
            if (isset($diasHabiles[$actual->format('Y-m-d')])) {
                $count++;
            }
            $actual->modify('+1 day');
        }

        return $count;
    }
}
