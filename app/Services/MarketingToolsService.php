<?php

namespace App\Services;

/**
 * Herramientas (function calling) que OTTO modo marketing puede invocar
 * para analizar el embudo de marketing y proponer acciones de crecimiento.
 *
 * Paralelo a FinancialToolsService y CrmToolsService pero enfocado en
 * generación y calificación de leads + diario de acciones.
 */
class MarketingToolsService
{
    /**
     * Definiciones JSON schema de las 8 tools de marketing.
     */
    public function definiciones(): array
    {
        return [
            [
                'name' => 'obtener_resumen_semanal',
                'description' => 'Resumen de una semana específica: cuántos leads nuevos llegaron, por qué fuente, cuántos se calificaron, cuántas acciones de marketing se registraron y cuánto costaron. Si no se da fecha, retorna la semana actual.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha' => [
                            'type' => 'string', 'format' => 'date',
                            'description' => 'Cualquier fecha dentro de la semana a consultar (lunes-domingo). YYYY-MM-DD.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'comparar_semanas',
                'description' => 'Compara dos semanas (semana A vs semana B) y devuelve los deltas: leads nuevos, tasa de calificación, acciones, costo, CAC. Útil para responder "¿avanzamos esta semana?". Si no se dan fechas, compara semana actual vs semana pasada.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha_a' => ['type' => 'string', 'format' => 'date', 'description' => 'Cualquier día de la semana de referencia (default: semana pasada).'],
                        'fecha_b' => ['type' => 'string', 'format' => 'date', 'description' => 'Cualquier día de la semana actual a comparar (default: semana actual).'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_embudo_actual',
                'description' => 'Estado vivo del embudo de leads: cantidad y porcentaje por estado (nuevo / contactado / calificado / descartado). Útil para entender dónde se atascan los leads.',
                'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name' => 'obtener_fuentes_por_calificacion',
                'description' => 'Ranking de fuentes por TASA de calificación (no por volumen). La fuente que convierte mejor vale más que la que trae más volumen — esto es clave para enfocar el esfuerzo de marketing.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'limite' => ['type' => 'integer', 'description' => 'Máximo de fuentes a retornar (default 10).'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_leads_estancados',
                'description' => 'Lista leads en estado nuevo/contactado que no han sido actualizados en X días. Estos son leads que se están enfriando por falta de seguimiento.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'dias_min' => ['type' => 'integer', 'description' => 'Días sin actualizar para considerar estancado (default 7).'],
                        'limite' => ['type' => 'integer', 'description' => 'Máximo a retornar (default 20).'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_acciones_periodo',
                'description' => 'Lista detallada del diario de acciones en un rango. Útil para responder "¿qué hizo Solangel en marketing este mes?". Devuelve por acción: fecha, tipo, descripción, costo y leads_generados.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'desde' => ['type' => 'string', 'format' => 'date', 'description' => 'Fecha inicio YYYY-MM-DD'],
                        'hasta' => ['type' => 'string', 'format' => 'date', 'description' => 'Fecha fin YYYY-MM-DD'],
                    ],
                    'required' => ['desde', 'hasta'],
                ],
            ],
            [
                'name' => 'obtener_resumen_acciones_por_tipo',
                'description' => 'Agrega las acciones del periodo por tipo (post LinkedIn, evento, etc.). Devuelve cantidad, costo total y leads atribuidos por cada tipo. Útil para ver dónde se está invirtiendo el esfuerzo y qué tipo de acción está atribuyendo más leads.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'desde' => ['type' => 'string', 'format' => 'date'],
                        'hasta' => ['type' => 'string', 'format' => 'date'],
                    ],
                    'required' => ['desde', 'hasta'],
                ],
            ],
            [
                'name' => 'calcular_cac',
                'description' => 'Calcula CAC (Costo de Adquisición de Cliente) informal de un mes: costo total de acciones / leads nuevos del mes. Si no se especifica mes, usa el actual. Solo cuenta acciones con costo registrado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'anio' => ['type' => 'integer'],
                        'mes' => ['type' => 'integer'],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Despachador.
     */
    public function ejecutar(string $nombre, array $input): string
    {
        $metodo = 'tool_' . $nombre;
        if (!method_exists($this, $metodo)) {
            return json_encode(['error' => "Tool '{$nombre}' no existe."], JSON_UNESCAPED_UNICODE);
        }
        try {
            $resultado = $this->$metodo($input);
            return json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return json_encode(['error' => 'Error ejecutando tool: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // ─────────────────────────── HELPERS ───────────────────────────

    private function rangoSemanaDe(?string $fecha): array
    {
        $ts = $fecha ? strtotime($fecha) : time();
        // Lunes 00:00 a Domingo 23:59 (semana ISO)
        $lunes = strtotime('monday this week', $ts);
        $domingo = strtotime('sunday this week', $ts);
        if ($domingo < $lunes) $domingo = strtotime('+6 days', $lunes);
        return [
            date('Y-m-d 00:00:00', $lunes),
            date('Y-m-d 23:59:59', $domingo),
            date('Y-m-d', $lunes),
            date('Y-m-d', $domingo),
        ];
    }

    private function delta($a, $b): array
    {
        $diff = round($b - $a, 2);
        return [
            'anterior' => $a,
            'actual'   => $b,
            'delta'    => $diff,
            'tendencia'=> $diff > 0 ? 'crece' : ($diff < 0 ? 'baja' : 'sin_cambio'),
        ];
    }

    // ─────────────────────────────── TOOLS ───────────────────────────────

    private function tool_obtener_resumen_semanal(array $in): array
    {
        $db = \Config\Database::connect();
        [$desdeDt, $hastaDt, $desde, $hasta] = $this->rangoSemanaDe($in['fecha'] ?? null);

        $leadsNuevos = (int) $db->table('tbl_marketing_lead')
            ->where('created_at >=', $desdeDt)->where('created_at <=', $hastaDt)
            ->countAllResults();

        $calificados = (int) $db->table('tbl_marketing_lead')
            ->where('fecha_calificacion >=', $desdeDt)->where('fecha_calificacion <=', $hastaDt)
            ->countAllResults();

        $accionesRows = $db->table('tbl_marketing_accion a')
            ->select('t.nombre AS tipo, t.color, COUNT(*) AS cantidad, COALESCE(SUM(a.costo),0) AS costo')
            ->join('tbl_marketing_tipo_accion t', 't.id_tipo_accion = a.id_tipo_accion')
            ->where('a.fecha >=', $desde)->where('a.fecha <=', $hasta)
            ->groupBy('t.id_tipo_accion, t.nombre, t.color')
            ->orderBy('cantidad', 'DESC')
            ->get()->getResultArray();

        $accionesTotal = array_sum(array_map(fn($r) => (int) $r['cantidad'], $accionesRows));
        $costoTotal = array_sum(array_map(fn($r) => (float) $r['costo'], $accionesRows));

        return [
            'rango' => ['lunes' => $desde, 'domingo' => $hasta],
            'leads_nuevos' => $leadsNuevos,
            'leads_calificados' => $calificados,
            'tasa_calificacion_pct' => $leadsNuevos > 0 ? round($calificados / $leadsNuevos * 100, 1) : 0,
            'acciones_total' => $accionesTotal,
            'acciones_por_tipo' => $accionesRows,
            'costo_total' => round($costoTotal, 2),
            'cac_semana' => $leadsNuevos > 0 && $costoTotal > 0 ? round($costoTotal / $leadsNuevos, 0) : null,
        ];
    }

    private function tool_comparar_semanas(array $in): array
    {
        $fechaA = $in['fecha_a'] ?? date('Y-m-d', strtotime('-1 week'));
        $fechaB = $in['fecha_b'] ?? date('Y-m-d');

        $semanaA = $this->tool_obtener_resumen_semanal(['fecha' => $fechaA]);
        $semanaB = $this->tool_obtener_resumen_semanal(['fecha' => $fechaB]);

        return [
            'semana_anterior' => $semanaA['rango'],
            'semana_actual'   => $semanaB['rango'],
            'deltas' => [
                'leads_nuevos'           => $this->delta($semanaA['leads_nuevos'], $semanaB['leads_nuevos']),
                'leads_calificados'      => $this->delta($semanaA['leads_calificados'], $semanaB['leads_calificados']),
                'tasa_calificacion_pct'  => $this->delta($semanaA['tasa_calificacion_pct'], $semanaB['tasa_calificacion_pct']),
                'acciones_total'         => $this->delta($semanaA['acciones_total'], $semanaB['acciones_total']),
                'costo_total'            => $this->delta($semanaA['costo_total'], $semanaB['costo_total']),
            ],
            'detalle_anterior' => $semanaA,
            'detalle_actual'   => $semanaB,
        ];
    }

    private function tool_obtener_embudo_actual(array $in): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('vw_marketing_embudo_resumen')->get()->getResultArray();
        $total = array_sum(array_map(fn($r) => (int) $r['cantidad'], $rows));

        return [
            'total_leads' => $total,
            'por_estado' => $rows,
            'interpretacion_sugerida' => 'Si "nuevo" tiene mucho stock vs "contactado", hay falta de seguimiento. Si "contactado" es mucho mayor que "calificado", la calificación es débil o los leads no son de calidad.',
        ];
    }

    private function tool_obtener_fuentes_por_calificacion(array $in): array
    {
        $db = \Config\Database::connect();
        $limite = min(20, max(1, (int) ($in['limite'] ?? 10)));

        $sql = "
            SELECT
                COALESCE(f.nombre, 'Sin fuente') AS fuente,
                COUNT(l.id_lead) AS total,
                SUM(CASE WHEN l.estado = 'calificado' THEN 1 ELSE 0 END) AS calificados,
                SUM(CASE WHEN l.fue_convertido = 1 THEN 1 ELSE 0 END) AS convertidos,
                ROUND(SUM(CASE WHEN l.estado = 'calificado' THEN 1 ELSE 0 END) / COUNT(l.id_lead) * 100, 1) AS tasa_calificacion_pct
            FROM vw_marketing_lead_360 l
            GROUP BY f.id_fuente, f.nombre
            ORDER BY calificados DESC, tasa_calificacion_pct DESC
            LIMIT {$limite}
        ";
        // El alias l.fue_convertido es una columna de la vista; f viene del LEFT JOIN dentro de la vista
        // así que reconstruyo el query referenciando la vista (que ya tiene fuente_nombre):
        $sql = "
            SELECT
                COALESCE(fuente_nombre, 'Sin fuente') AS fuente,
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'calificado' THEN 1 ELSE 0 END) AS calificados,
                SUM(CASE WHEN fue_convertido = 1 THEN 1 ELSE 0 END) AS convertidos,
                ROUND(SUM(CASE WHEN estado = 'calificado' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) AS tasa_calificacion_pct
            FROM vw_marketing_lead_360
            GROUP BY fuente_nombre
            ORDER BY calificados DESC, tasa_calificacion_pct DESC
            LIMIT {$limite}
        ";
        $rows = $db->query($sql)->getResultArray();

        return [
            'criterio' => 'Ordenado por calificados (cantidad) DESC, luego por tasa de calificación. La fuente que CALIFICA MEJOR vale más que la que trae más volumen.',
            'fuentes' => $rows,
        ];
    }

    private function tool_obtener_leads_estancados(array $in): array
    {
        $db = \Config\Database::connect();
        $diasMin = max(1, (int) ($in['dias_min'] ?? 7));
        $limite  = min(50, max(1, (int) ($in['limite'] ?? 20)));

        $rows = $db->table('vw_marketing_lead_360')
            ->select('id_lead, nombre, empresa_text, email, telefono, estado, fuente_nombre, responsable_nombre, dias_sin_actualizar, dias_desde_creacion, notas')
            ->whereIn('estado', ['nuevo', 'contactado'])
            ->where('dias_sin_actualizar >=', $diasMin)
            ->orderBy('dias_sin_actualizar', 'DESC')
            ->limit($limite)
            ->get()->getResultArray();

        return [
            'criterio' => "Leads en estado 'nuevo' o 'contactado' sin actualizar en {$diasMin}+ días",
            'total_encontrados' => count($rows),
            'leads' => $rows,
        ];
    }

    private function tool_obtener_acciones_periodo(array $in): array
    {
        $db = \Config\Database::connect();
        $desde = $in['desde'] ?? date('Y-m-01');
        $hasta = $in['hasta'] ?? date('Y-m-d');

        $rows = $db->table('tbl_marketing_accion a')
            ->select('a.fecha, t.nombre AS tipo, a.descripcion, a.costo, a.leads_generados, u.nombre_completo AS responsable, a.notas')
            ->join('tbl_marketing_tipo_accion t', 't.id_tipo_accion = a.id_tipo_accion')
            ->join('users u', 'u.id_users = a.id_responsable', 'left')
            ->where('a.fecha >=', $desde)
            ->where('a.fecha <=', $hasta)
            ->orderBy('a.fecha', 'DESC')
            ->limit(100)
            ->get()->getResultArray();

        return [
            'rango' => ['desde' => $desde, 'hasta' => $hasta],
            'total' => count($rows),
            'acciones' => $rows,
        ];
    }

    private function tool_obtener_resumen_acciones_por_tipo(array $in): array
    {
        $db = \Config\Database::connect();
        $desde = $in['desde'] ?? date('Y-m-01');
        $hasta = $in['hasta'] ?? date('Y-m-d');

        $rows = $db->table('tbl_marketing_accion a')
            ->select("t.id_tipo_accion, t.nombre AS tipo_nombre, t.color AS tipo_color,
                      COUNT(*) AS cantidad,
                      COALESCE(SUM(a.costo), 0) AS costo_total,
                      COALESCE(SUM(a.leads_generados), 0) AS leads_atribuidos")
            ->join('tbl_marketing_tipo_accion t', 't.id_tipo_accion = a.id_tipo_accion')
            ->where('a.fecha >=', $desde)->where('a.fecha <=', $hasta)
            ->groupBy('t.id_tipo_accion, t.nombre, t.color')
            ->orderBy('cantidad', 'DESC')
            ->get()->getResultArray();

        return [
            'rango' => ['desde' => $desde, 'hasta' => $hasta],
            'por_tipo' => $rows,
        ];
    }

    private function tool_calcular_cac(array $in): array
    {
        $db = \Config\Database::connect();
        $anio = (int) ($in['anio'] ?? date('Y'));
        $mes  = (int) ($in['mes']  ?? date('n'));
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));

        $costoTotal = (float) $db->table('tbl_marketing_accion')
            ->selectSum('costo', 'total')
            ->where('fecha >=', $desde)->where('fecha <=', $hasta)
            ->where('costo IS NOT NULL', null, false)
            ->get()->getRow('total');

        $leadsMes = (int) $db->table('tbl_marketing_lead')
            ->where('created_at >=', "$desde 00:00:00")
            ->where('created_at <=', "$hasta 23:59:59")
            ->countAllResults();

        $cac = $leadsMes > 0 && $costoTotal > 0 ? round($costoTotal / $leadsMes, 0) : null;

        return [
            'periodo' => "$anio-" . str_pad((string) $mes, 2, '0', STR_PAD_LEFT),
            'costo_total_registrado' => round($costoTotal, 2),
            'leads_nuevos_mes' => $leadsMes,
            'cac_informal' => $cac,
            'nota' => $cac === null
                ? 'No se pudo calcular (faltan costos o leads). Recuerda registrar el campo costo en las acciones pagadas.'
                : 'CAC informal: cuánto cuesta en promedio cada lead nuevo. Para CAC real necesitarías tiempo de Solangel + costos indirectos.',
        ];
    }
}
