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

    /**
     * Obtener fórmulas para múltiples indicadores de una sola vez (evita N+1).
     * Retorna array indexado por id_indicador.
     *
     * @param array $indicadorIds Array de IDs de indicadores
     * @return array [id_indicador => [partes...], ...]
     */
    public function getFormulasPorIndicadores(array $indicadorIds): array
    {
        if (empty($indicadorIds)) {
            return [];
        }

        $partes = $this->whereIn('id_indicador', $indicadorIds)
                       ->orderBy('id_indicador', 'ASC')
                       ->orderBy('orden', 'ASC')
                       ->findAll();

        $resultado = [];
        foreach ($partes as $p) {
            $idInd = $p['id_indicador'];
            if (!isset($resultado[$idInd])) {
                $resultado[$idInd] = [];
            }
            $resultado[$idInd][] = $p;
        }

        return $resultado;
    }

    /**
     * Obtener fórmulas como texto para múltiples indicadores (evita N+1).
     *
     * @param array $indicadorIds
     * @return array [id_indicador => 'formula texto', ...]
     */
    public function getFormulasComoTextoPorIndicadores(array $indicadorIds): array
    {
        $formulas = $this->getFormulasPorIndicadores($indicadorIds);

        $resultado = [];
        foreach ($formulas as $idInd => $partes) {
            $texto = '';
            foreach ($partes as $p) {
                $texto .= ' ' . $p['valor'];
            }
            $resultado[$idInd] = trim($texto);
        }

        return $resultado;
    }
}
