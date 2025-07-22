<?php

namespace App\Models;

use CodeIgniter\Model;

class PartesFormulaModel extends Model
{
    protected $table         = 'partes_formula_indicador';
    protected $primaryKey    = 'id_parte_formula';
    protected $allowedFields = [
        'id_indicador',
        'tipo_parte',
        'valor',
        'orden'
    ];
    protected $returnType    = 'array';
    public    $useTimestamps = false;

    /**
     * Obtener todas las partes de la fórmula para un indicador específico, ordenadas.
     */
    public function getPartesPorIndicador($idIndicador)
    {
        return $this->where('id_indicador', $idIndicador)
                    ->orderBy('orden', 'ASC')
                    ->findAll();
    }

    /**
     * Construye y devuelve la fórmula completa como texto,
     * concatenando en orden todas las partes de la fórmula.
     */
    public function getFormulaComoTexto($idIndicador)
    {
        $partes = $this->getPartesPorIndicador($idIndicador);

        $formula = '';
        foreach ($partes as $p) {
            $formula .= ' ' . $p['valor'];
        }

        return trim($formula);
    }
}
