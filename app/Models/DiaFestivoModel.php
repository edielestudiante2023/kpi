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
     * Cuenta días hábiles en un rango.
     * Prioridad: configuración manual (dias_habiles_config) > cálculo automático (L-V menos festivos).
     * $desde y $hasta son strings 'Y-m-d' o 'Y-m-d H:i:s'. Se usan solo las fechas.
     */
    public function contarDiasHabiles(string $desde, string $hasta): int
    {
        $inicio = new \DateTime(substr($desde, 0, 10));
        $fin    = new \DateTime(substr($hasta, 0, 10));
        $db     = \Config\Database::connect();

        // Intentar con configuración manual (query directa, sin depender de otro modelo)
        $resultado = $this->contarDesdeConfigManual($db, $inicio, $fin);
        if ($resultado !== null) {
            return $resultado;
        }

        // Fallback: cálculo automático (L-V excluyendo festivos)
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

    /**
     * Cuenta días hábiles desde la tabla dias_habiles_config (queries directas).
     * Retorna null si algún mes del rango no tiene configuración.
     */
    private function contarDesdeConfigManual($db, \DateTime $inicio, \DateTime $fin): ?int
    {
        // Recopilar meses involucrados
        $meses = [];
        $tmp = clone $inicio;
        while ($tmp <= $fin) {
            $key = $tmp->format('Y-n');
            $meses[$key] = ['anio' => (int) $tmp->format('Y'), 'mes' => (int) $tmp->format('n')];
            $tmp->modify('first day of next month');
        }

        // Verificar que todos los meses tengan configuración
        foreach ($meses as $m) {
            $row = $db->query(
                "SELECT COUNT(*) AS c FROM dias_habiles_config WHERE anio = ? AND mes = ?",
                [$m['anio'], $m['mes']]
            )->getRowArray();
            if (((int) ($row['c'] ?? 0)) === 0) {
                return null;
            }
        }

        // Precargar días configurados en un set
        $diasHabiles = [];
        foreach ($meses as $m) {
            $rows = $db->query(
                "SELECT dia FROM dias_habiles_config WHERE anio = ? AND mes = ?",
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
