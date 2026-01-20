<?php

namespace App\Libraries;

use App\Models\HistorialIndicadorModel;

/**
 * Servicio centralizado para evaluar cumplimiento de indicadores.
 * Evita duplicación de lógica entre TrabajadorController y JefaturaController.
 */
class EvaluadorIndicadores
{
    protected HistorialIndicadorModel $histModel;

    public function __construct()
    {
        $this->histModel = new HistorialIndicadorModel();
    }

    /**
     * Evalúa si un valor cumple la meta según el tipo de meta del indicador.
     *
     * @param float|string $valor Valor reportado
     * @param string $tipoMeta Tipo de meta (mayor_igual, menor_igual, igual, comparativa)
     * @param float $metaEsperada Meta del indicador
     * @param int $userId ID del usuario
     * @param int $ipId ID del indicador_perfil
     * @return array ['cumple' => int|null, 'valor_anterior' => float|null]
     */
    public function evaluar($valor, string $tipoMeta, float $metaEsperada, int $userId, int $ipId): array
    {
        $cumple = null;
        $valorAnterior = null;

        if (!is_numeric($valor)) {
            log_message('debug', "EvaluadorIndicadores: Valor no numérico para IP {$ipId}: " . print_r($valor, true));
            return ['cumple' => null, 'valor_anterior' => null];
        }

        $valorNum = (float) $valor;

        switch ($tipoMeta) {
            case 'mayor_igual':
                $cumple = ($valorNum >= $metaEsperada) ? 1 : 0;
                break;

            case 'menor_igual':
                $cumple = ($valorNum <= $metaEsperada) ? 1 : 0;
                break;

            case 'igual':
                $cumple = ($valorNum == $metaEsperada) ? 1 : 0;
                break;

            case 'comparativa':
                $resultado = $this->evaluarComparativa($valorNum, $userId, $ipId);
                $cumple = $resultado['cumple'];
                $valorAnterior = $resultado['valor_anterior'];
                break;

            default:
                log_message('warning', "EvaluadorIndicadores: Tipo de meta desconocido: {$tipoMeta}");
                break;
        }

        log_message('debug', "EvaluadorIndicadores: IP {$ipId} | Usuario {$userId} | Tipo: {$tipoMeta} | Valor: {$valorNum} | Meta: {$metaEsperada} | Cumple: " . ($cumple ?? 'null'));

        return [
            'cumple' => $cumple,
            'valor_anterior' => $valorAnterior
        ];
    }

    /**
     * Evalúa tipo comparativa: compara con el último valor registrado del mismo usuario.
     * IMPORTANTE: NO modifica la meta global del indicador (bug anterior corregido).
     *
     * @param float $valorActual
     * @param int $userId
     * @param int $ipId
     * @return array
     */
    protected function evaluarComparativa(float $valorActual, int $userId, int $ipId): array
    {
        // Buscar último resultado anterior del mismo usuario e indicador/perfil
        $anterior = $this->histModel
            ->where('id_usuario', $userId)
            ->where('id_indicador_perfil', $ipId)
            ->orderBy('fecha_registro', 'DESC')
            ->first();

        if ($anterior && is_numeric($anterior['resultado_real'])) {
            $valorAnterior = (float) $anterior['resultado_real'];
            $cumple = ($valorActual > $valorAnterior) ? 1 : 0;

            log_message('debug', "EvaluadorIndicadores: Comparativa IP {$ipId} | Usuario {$userId} | Anterior: {$valorAnterior} | Actual: {$valorActual} | Cumple: {$cumple}");

            return [
                'cumple' => $cumple,
                'valor_anterior' => $valorAnterior
            ];
        }

        // Primer registro - no hay base de comparación
        log_message('debug', "EvaluadorIndicadores: Comparativa IP {$ipId} | Usuario {$userId} | Primer registro, sin evaluación");

        return [
            'cumple' => null,
            'valor_anterior' => $valorActual
        ];
    }
}
