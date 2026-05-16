<?php

namespace App\Services;

/**
 * Herramientas (function calling) que OTTO modo comercial puede invocar
 * para consultar el estado del pipeline CRM y proponer acciones.
 *
 * Paralelo a FinancialToolsService pero enfocado en ventas.
 * Cada método con prefijo `tool_` es una herramienta disponible.
 * `definiciones()` retorna el JSON schema para enviar a Anthropic.
 */
class CrmToolsService
{
    /**
     * Definiciones JSON schema de las 10 tools comerciales.
     */
    public function definiciones(): array
    {
        return [
            [
                'name' => 'obtener_snapshot_semanal',
                'description' => 'Recupera un snapshot semanal del pipeline CRM (KPIs congelados en un momento del tiempo: pipeline abierto, ganadas/perdidas año, conversión, ciclo promedio, estancadas, breakdown por etapa, responsable y motivos de pérdida). Si no se especifica fecha, retorna el más reciente. Usar para responder "¿cómo estábamos hace X tiempo?".',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'Fecha YYYY-MM-DD. Devuelve el snapshot más cercano (≤) a esa fecha. Si se omite, retorna el último.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'comparar_snapshots',
                'description' => 'Compara dos snapshots y devuelve los deltas (anterior, actual, diferencia, tendencia) de cada KPI. Sirve para responder la pregunta clave "¿avanzamos esta semana/este mes?". Si no se pasan fechas, compara los dos snapshots más recientes.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha_a' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'Snapshot anterior (referencia). YYYY-MM-DD. Si se omite, usa el penúltimo.',
                        ],
                        'fecha_b' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'Snapshot posterior (actual). YYYY-MM-DD. Si se omite, usa el más reciente.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_pipeline_actual',
                'description' => 'Estado VIVO (no congelado) del pipeline abierto agrupado por etapa: cantidad, valor total, valor ponderado por probabilidad. Útil para responder "¿cómo está el pipeline hoy?" o detectar cuellos de botella (etapas con mucha cantidad pero poco valor que avanza).',
                'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name' => 'obtener_oportunidades_estancadas',
                'description' => 'Lista oportunidades abiertas SIN actividad reciente (sin interacciones registradas en X días). Ordenadas por antigüedad descendente. Útil para responder "¿qué oportunidades están muriendo por falta de seguimiento?".',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'dias_min' => [
                            'type' => 'integer',
                            'description' => 'Días mínimos sin actividad para considerar estancada (default 30).',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Máximo a retornar (default 30, máx 50).',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_top_oportunidades',
                'description' => 'Ranking de oportunidades ABIERTAS por criterio: valor crudo, valor ponderado (valor × probabilidad), probabilidad, o proximidad de cierre. Útil para priorizar dónde poner foco: "¿qué oportunidades atacar primero?".',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'criterio' => [
                            'type' => 'string',
                            'enum' => ['valor', 'valor_ponderado', 'probabilidad', 'proximidad_cierre'],
                            'description' => 'Por qué ordenar. valor_ponderado es el más útil para priorizar acción comercial.',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Cantidad a retornar (default 10, máx 50).',
                        ],
                    ],
                    'required' => ['criterio'],
                ],
            ],
            [
                'name' => 'obtener_oportunidades_proximas_cierre',
                'description' => 'Oportunidades abiertas cuya fecha de cierre estimada está dentro de los próximos N días. Ordenadas por urgencia. Útil para identificar dónde concentrar esfuerzo de cierre inmediato.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'dias' => [
                            'type' => 'integer',
                            'description' => 'Horizonte en días (default 30). Si una oportunidad ya pasó su fecha de cierre, también aparece (días negativos).',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_ranking_responsables',
                'description' => 'Ranking del equipo de ventas por desempeño: oportunidades abiertas y pipeline en curso, ganadas y valor ganado, perdidas. Ordenado por valor ganado descendente. Útil para identificar top performers, vendedores sin movimiento y distribución de carga.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'periodo' => [
                            'type' => 'string',
                            'enum' => ['mes_actual', 'anio', 'total'],
                            'description' => 'Periodo para contabilizar ganadas/perdidas (las abiertas son siempre actuales).',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_motivos_perdida_top',
                'description' => 'Ranking de motivos por los que se pierden oportunidades, con cantidad y valor total perdido. Útil para identificar patrones ("¿por qué perdemos? ¿precio, timing, competencia?") y proponer acciones correctivas.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'periodo' => [
                            'type' => 'string',
                            'enum' => ['mes_actual', 'anio', 'total'],
                            'description' => 'Periodo a considerar (default anio).',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_oportunidad_detalle',
                'description' => 'Ficha completa de una oportunidad por su código (ej: OPP-20260515-0001): datos básicos, empresa, contacto, etapa, valor ponderado, días sin actividad, últimas 15 interacciones (timeline) y historial completo de cambios de etapa. Útil para diagnóstico profundo de un negocio específico.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'codigo' => [
                            'type' => 'string',
                            'description' => 'Código de la oportunidad, ej: OPP-20260515-0001.',
                        ],
                    ],
                    'required' => ['codigo'],
                ],
            ],
            [
                'name' => 'obtener_empresa_actividad',
                'description' => 'Historial 360 de una empresa: datos básicos, contactos, todas sus oportunidades (abiertas, ganadas, perdidas) y últimas 20 interacciones. Útil para entender el contexto completo antes de proponer acciones con un cliente.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id_empresa' => [
                            'type' => 'integer',
                            'description' => 'ID interno de la empresa. Si se pasa, ignora `nombre`.',
                        ],
                        'nombre' => [
                            'type' => 'string',
                            'description' => 'Razón social (búsqueda LIKE). Solo se usa si no se pasa id_empresa.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Despachador: recibe nombre + input y devuelve JSON string.
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

    // ─────────────────────────────── TOOLS ───────────────────────────────

    private function tool_obtener_snapshot_semanal(array $in): array
    {
        $db = \Config\Database::connect();
        $fecha = $in['fecha'] ?? null;

        $b = $db->table('tbl_crm_snapshot_semanal s')
            ->select('s.*, u.nombre_completo AS autor_nombre')
            ->join('users u', 'u.id_users = s.creado_por', 'left')
            ->orderBy('s.fecha_corte', 'DESC');
        if ($fecha) $b->where('DATE(s.fecha_corte) <=', $fecha);

        $r = $b->limit(1)->get()->getRowArray();
        if (!$r) return ['error' => 'No hay snapshots disponibles. Genera uno desde /crm/snapshots.'];

        foreach (['por_etapa', 'por_responsable', 'motivos_perdida_top'] as $k) {
            $r[$k] = !empty($r[$k]) ? json_decode($r[$k], true) : [];
        }
        return $r;
    }

    private function tool_comparar_snapshots(array $in): array
    {
        $db = \Config\Database::connect();
        $fechaA = $in['fecha_a'] ?? null;
        $fechaB = $in['fecha_b'] ?? null;

        $getCerca = function ($fecha) use ($db) {
            $b = $db->table('tbl_crm_snapshot_semanal')->orderBy('fecha_corte', 'DESC');
            if ($fecha) $b->where('DATE(fecha_corte) <=', $fecha);
            return $b->limit(1)->get()->getRowArray();
        };

        if (!$fechaA && !$fechaB) {
            $rows = $db->table('tbl_crm_snapshot_semanal')
                ->orderBy('fecha_corte', 'DESC')->limit(2)->get()->getResultArray();
            if (count($rows) < 2) {
                return ['error' => 'Solo hay ' . count($rows) . ' snapshot(s). Se necesitan al menos 2 para comparar.'];
            }
            $b = $rows[0]; $a = $rows[1];
        } else {
            $a = $getCerca($fechaA);
            $b = $fechaB ? $getCerca($fechaB) :
                 $db->table('tbl_crm_snapshot_semanal')->orderBy('fecha_corte', 'DESC')->limit(1)->get()->getRowArray();
            if (!$a || !$b) return ['error' => 'No se encontraron los dos snapshots para comparar.'];
        }

        $delta = function ($vA, $vB) {
            $diff = round($vB - $vA, 2);
            return [
                'anterior' => $vA,
                'actual' => $vB,
                'delta' => $diff,
                'tendencia' => $diff > 0 ? 'crece' : ($diff < 0 ? 'baja' : 'sin_cambio'),
            ];
        };

        $diasEntre = (int) round((strtotime($b['fecha_corte']) - strtotime($a['fecha_corte'])) / 86400);

        return [
            'snapshot_anterior' => [
                'id' => (int) $a['id_snapshot'],
                'fecha' => $a['fecha_corte'],
                'notas' => $a['notas'],
            ],
            'snapshot_actual' => [
                'id' => (int) $b['id_snapshot'],
                'fecha' => $b['fecha_corte'],
                'notas' => $b['notas'],
            ],
            'dias_entre_snapshots' => $diasEntre,
            'deltas' => [
                'total_abiertas'               => $delta((int) $a['total_abiertas'], (int) $b['total_abiertas']),
                'valor_pipeline'               => $delta((float) $a['valor_pipeline'], (float) $b['valor_pipeline']),
                'total_ganadas_anio'           => $delta((int) $a['total_ganadas_anio'], (int) $b['total_ganadas_anio']),
                'valor_ganadas_anio'           => $delta((float) $a['valor_ganadas_anio'], (float) $b['valor_ganadas_anio']),
                'total_perdidas_anio'          => $delta((int) $a['total_perdidas_anio'], (int) $b['total_perdidas_anio']),
                'tasa_conversion_anio'         => $delta((float) $a['tasa_conversion_anio'], (float) $b['tasa_conversion_anio']),
                'ciclo_promedio_dias'          => $delta((int) ($a['ciclo_promedio_dias'] ?? 0), (int) ($b['ciclo_promedio_dias'] ?? 0)),
                'oportunidades_estancadas_30d' => $delta((int) $a['oportunidades_estancadas_30d'], (int) $b['oportunidades_estancadas_30d']),
            ],
        ];
    }

    private function tool_obtener_pipeline_actual(array $in): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('vw_crm_pipeline_resumen')
            ->where('etapa_tipo', 'abierta')
            ->orderBy('etapa_orden', 'ASC')
            ->get()->getResultArray();

        $totCant = 0; $totVal = 0; $totPond = 0;
        foreach ($rows as $r) {
            $totCant += (int) $r['cantidad'];
            $totVal  += (float) $r['valor_total'];
            $totPond += (float) $r['valor_ponderado'];
        }

        return [
            'por_etapa' => $rows,
            'totales' => [
                'cantidad'        => $totCant,
                'valor_total'     => round($totVal, 2),
                'valor_ponderado' => round($totPond, 2),
            ],
            'interpretacion_sugerida' => 'Si una etapa avanzada (Negociación) tiene poco vs etapas tempranas (Prospecto), hay cuello de botella. Si valor_ponderado << valor_total, el pipeline tiene poca probabilidad real de cerrarse.',
        ];
    }

    private function tool_obtener_oportunidades_estancadas(array $in): array
    {
        $db = \Config\Database::connect();
        $diasMin = max(1, (int) ($in['dias_min'] ?? 30));
        $limite  = min(50, max(1, (int) ($in['limite'] ?? 30)));

        $rows = $db->table('vw_crm_oportunidad_360')
            ->select('codigo, titulo, empresa_nombre, valor, valor_ponderado, etapa_nombre, etapa_color, responsable_nombre, dias_sin_actividad, ultima_actividad_at, fecha_cierre_estimada, dias_para_cierre_estimado')
            ->where('etapa_tipo', 'abierta')
            ->where('dias_sin_actividad >=', $diasMin)
            ->orderBy('dias_sin_actividad', 'DESC')
            ->limit($limite)
            ->get()->getResultArray();

        return [
            'criterio' => "Oportunidades abiertas con {$diasMin}+ días sin interacción registrada",
            'total_encontradas' => count($rows),
            'oportunidades' => $rows,
        ];
    }

    private function tool_obtener_top_oportunidades(array $in): array
    {
        $db = \Config\Database::connect();
        $criterio = $in['criterio'] ?? 'valor_ponderado';
        $limite   = min(50, max(1, (int) ($in['limite'] ?? 10)));

        $b = $db->table('vw_crm_oportunidad_360')
            ->select('codigo, titulo, empresa_nombre, valor, valor_ponderado, probabilidad, etapa_nombre, etapa_color, responsable_nombre, fecha_cierre_estimada, dias_para_cierre_estimado, dias_sin_actividad')
            ->where('etapa_tipo', 'abierta');

        switch ($criterio) {
            case 'valor':              $b->orderBy('valor', 'DESC'); break;
            case 'probabilidad':       $b->orderBy('probabilidad', 'DESC')->orderBy('valor', 'DESC'); break;
            case 'proximidad_cierre':
                $b->where('fecha_cierre_estimada IS NOT NULL', null, false)
                  ->orderBy('fecha_cierre_estimada', 'ASC');
                break;
            case 'valor_ponderado':
            default:
                $b->orderBy('valor_ponderado', 'DESC');
                $criterio = 'valor_ponderado';
        }

        $rows = $b->limit($limite)->get()->getResultArray();
        return [
            'criterio' => $criterio,
            'total' => count($rows),
            'oportunidades' => $rows,
        ];
    }

    private function tool_obtener_oportunidades_proximas_cierre(array $in): array
    {
        $db = \Config\Database::connect();
        $dias = max(1, (int) ($in['dias'] ?? 30));

        $rows = $db->table('vw_crm_oportunidad_360')
            ->select('codigo, titulo, empresa_nombre, valor, valor_ponderado, probabilidad, etapa_nombre, responsable_nombre, fecha_cierre_estimada, dias_para_cierre_estimado, dias_sin_actividad')
            ->where('etapa_tipo', 'abierta')
            ->where('fecha_cierre_estimada IS NOT NULL', null, false)
            ->where('dias_para_cierre_estimado <=', $dias)
            ->orderBy('dias_para_cierre_estimado', 'ASC')
            ->limit(50)
            ->get()->getResultArray();

        return [
            'horizonte_dias' => $dias,
            'total' => count($rows),
            'oportunidades' => $rows,
            'nota' => 'dias_para_cierre_estimado negativo = ya pasó la fecha estimada (urgente).',
        ];
    }

    private function tool_obtener_ranking_responsables(array $in): array
    {
        $db = \Config\Database::connect();
        $periodo = $in['periodo'] ?? 'anio';
        $anio = (int) date('Y');
        $mes  = (int) date('m');

        // Construir condición de cierre según periodo (valores controlados, no input directo)
        $condCierre = match ($periodo) {
            'mes_actual' => "AND YEAR(o.fecha_cierre_real) = {$anio} AND MONTH(o.fecha_cierre_real) = {$mes}",
            'anio'       => "AND YEAR(o.fecha_cierre_real) = {$anio}",
            'total'      => '',
            default      => "AND YEAR(o.fecha_cierre_real) = {$anio}",
        };

        $sql = "
            SELECT u.id_users, u.nombre_completo,
                   COUNT(CASE WHEN et.tipo = 'abierta' THEN 1 END) AS abiertas,
                   COALESCE(SUM(CASE WHEN et.tipo = 'abierta' THEN o.valor ELSE 0 END), 0) AS valor_pipeline,
                   COUNT(CASE WHEN et.tipo = 'ganada' {$condCierre} THEN 1 END) AS ganadas,
                   COALESCE(SUM(CASE WHEN et.tipo = 'ganada' {$condCierre} THEN o.valor ELSE 0 END), 0) AS valor_ganado,
                   COUNT(CASE WHEN et.tipo = 'perdida' {$condCierre} THEN 1 END) AS perdidas
            FROM tbl_crm_oportunidad o
            JOIN tbl_crm_etapa et ON et.id_etapa = o.id_etapa
            JOIN users u ON u.id_users = o.id_responsable
            GROUP BY u.id_users, u.nombre_completo
            ORDER BY valor_ganado DESC, ganadas DESC
        ";
        $rows = $db->query($sql)->getResultArray();

        return [
            'periodo' => $periodo,
            'ranking' => $rows,
        ];
    }

    private function tool_obtener_motivos_perdida_top(array $in): array
    {
        $db = \Config\Database::connect();
        $periodo = $in['periodo'] ?? 'anio';

        $b = $db->table('tbl_crm_oportunidad o')
            ->select("mp.nombre AS motivo, COUNT(o.id_oportunidad) AS cantidad, COALESCE(SUM(o.valor), 0) AS valor_total_perdido")
            ->join('tbl_crm_motivo_perdida mp', 'mp.id_motivo_perdida = o.id_motivo_perdida')
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->where('et.tipo', 'perdida')
            ->groupBy('mp.id_motivo_perdida, mp.nombre')
            ->orderBy('cantidad', 'DESC')
            ->limit(10);

        if ($periodo === 'mes_actual') {
            $b->where('YEAR(o.fecha_cierre_real)', (int) date('Y'))
              ->where('MONTH(o.fecha_cierre_real)', (int) date('m'));
        } elseif ($periodo === 'anio') {
            $b->where('YEAR(o.fecha_cierre_real)', (int) date('Y'));
        }

        return [
            'periodo' => $periodo,
            'motivos' => $b->get()->getResultArray(),
        ];
    }

    private function tool_obtener_oportunidad_detalle(array $in): array
    {
        $db = \Config\Database::connect();
        $codigo = trim((string) ($in['codigo'] ?? ''));
        if ($codigo === '') return ['error' => 'Falta el código de oportunidad (ej: OPP-20260515-0001).'];

        $op = $db->table('vw_crm_oportunidad_360')
            ->where('codigo', $codigo)
            ->get()->getRowArray();
        if (!$op) return ['error' => "Oportunidad {$codigo} no encontrada."];

        $interacciones = $db->table('tbl_crm_interaccion i')
            ->select('i.tipo, i.asunto, i.detalle, i.estado, i.fecha_completada, i.fecha_programada, i.created_at, u.nombre_completo AS usuario_nombre, c.nombre AS contacto_nombre')
            ->join('users u', 'u.id_users = i.id_usuario', 'left')
            ->join('tbl_crm_contacto c', 'c.id_contacto = i.id_contacto', 'left')
            ->where('i.id_oportunidad', $op['id_oportunidad'])
            ->orderBy('COALESCE(i.fecha_completada, i.fecha_programada, i.created_at)', 'DESC')
            ->limit(15)
            ->get()->getResultArray();

        $historial = $db->table('tbl_crm_oportunidad_historial h')
            ->select('h.created_at, h.comentario, ea.nombre AS etapa_anterior, en.nombre AS etapa_nueva, u.nombre_completo AS usuario_nombre')
            ->join('tbl_crm_etapa ea', 'ea.id_etapa = h.id_etapa_anterior', 'left')
            ->join('tbl_crm_etapa en', 'en.id_etapa = h.id_etapa_nueva', 'left')
            ->join('users u', 'u.id_users = h.id_usuario', 'left')
            ->where('h.id_oportunidad', $op['id_oportunidad'])
            ->orderBy('h.created_at', 'DESC')
            ->get()->getResultArray();

        return [
            'oportunidad' => $op,
            'interacciones_recientes' => $interacciones,
            'historial_etapas' => $historial,
        ];
    }

    private function tool_obtener_empresa_actividad(array $in): array
    {
        $db = \Config\Database::connect();
        $id = (int) ($in['id_empresa'] ?? 0);
        $nombre = trim((string) ($in['nombre'] ?? ''));

        if ($id <= 0 && $nombre === '') return ['error' => 'Pasa id_empresa o nombre.'];

        $b = $db->table('tbl_crm_empresa')
            ->select('id_empresa, razon_social, nit, sector, tamano, ciudad, email_principal, telefono, sitio_web, activo, notas');
        if ($id > 0) {
            $b->where('id_empresa', $id);
        } else {
            $b->like('razon_social', $nombre);
        }
        $emp = $b->limit(1)->get()->getRowArray();
        if (!$emp) return ['error' => 'Empresa no encontrada.'];

        $opos = $db->table('vw_crm_oportunidad_360')
            ->select('codigo, titulo, valor, valor_ponderado, etapa_nombre, etapa_tipo, fecha_cierre_estimada, fecha_cierre_real, responsable_nombre, dias_sin_actividad')
            ->where('id_empresa', $emp['id_empresa'])
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        $contactos = $db->table('tbl_crm_contacto')
            ->select('nombre, cargo, email, telefono, es_decisor')
            ->where('id_empresa', $emp['id_empresa'])
            ->where('activo', 1)
            ->orderBy('es_decisor', 'DESC')
            ->get()->getResultArray();

        $interacciones = $db->table('tbl_crm_interaccion i')
            ->select('i.tipo, i.asunto, i.estado, i.fecha_completada, i.fecha_programada, i.created_at, o.codigo AS oportunidad_codigo, u.nombre_completo AS usuario_nombre')
            ->join('tbl_crm_oportunidad o', 'o.id_oportunidad = i.id_oportunidad', 'left')
            ->join('users u', 'u.id_users = i.id_usuario', 'left')
            ->groupStart()
                ->where('i.id_empresa', $emp['id_empresa'])
                ->orWhere('o.id_empresa', $emp['id_empresa'])
            ->groupEnd()
            ->orderBy('i.created_at', 'DESC')
            ->limit(20)
            ->get()->getResultArray();

        return [
            'empresa' => $emp,
            'oportunidades' => $opos,
            'contactos' => $contactos,
            'interacciones_recientes' => $interacciones,
        ];
    }
}
