<?php

namespace App\Services;

use App\Models\BalanceSnapshotModel;

/**
 * Herramientas (function calling) que la IA puede invocar
 * para consultar la BD financiera de Cycloid.
 *
 * Cada método público con prefijo `tool_` es una herramienta disponible.
 * El método `definiciones()` retorna el JSON schema para enviar a Anthropic.
 */
class FinancialToolsService
{
    /**
     * Definiciones JSON schema que se le mandan a Claude.
     */
    public function definiciones(): array
    {
        return [
            [
                'name' => 'obtener_balance_al_corte',
                'description' => 'Retorna el estado financiero de la empresa Cycloid Talent a una fecha de corte específica: activos (cartera SST/RPS y saldo en bancos), pasivos (deudas activas con detalle) y el estado de la empresa (activos - pasivos). Si existe un snapshot guardado para esa fecha, retorna los valores congelados; si no, calcula dinámicamente. Usar cuando se necesite conocer la posición financiera consolidada.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'Fecha de corte YYYY-MM-DD. Si no se especifica, usa el último día del mes anterior.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_facturado_recaudo_por_mes',
                'description' => 'Retorna series mensuales de facturación (base gravable) y recaudo bancario por portafolio y año. Útil para ver tendencias, comparar contra presupuesto, identificar meses débiles o picos. Incluye también el presupuesto mensual configurado y el % de cumplimiento.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'portafolio' => [
                            'type' => 'string',
                            'enum' => ['SST', 'RPS', 'HUNTING', 'FRAMEWORK'],
                            'description' => 'Portafolio a consultar. FRAMEWORK = consolidado SST + RPS.',
                        ],
                        'anio' => [
                            'type' => 'integer',
                            'description' => 'Año a consultar (ej. 2026). Si no se especifica, usa el año actual.',
                        ],
                    ],
                    'required' => ['portafolio'],
                ],
            ],
            [
                'name' => 'obtener_cartera_detalle',
                'description' => 'Lista las facturas pendientes de cobro (cartera) de un portafolio, ordenadas por antigüedad (más viejas primero). Incluye cliente, NIT, fecha de elaboración, días de mora, base gravada, lo abonado y el saldo pendiente. Útil para priorizar gestión de cobro.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'portafolio' => [
                            'type' => 'string',
                            'enum' => ['SST', 'RPS', 'HUNTING'],
                            'description' => 'Portafolio cuya cartera se quiere consultar',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Máximo de facturas a retornar (default 30, máx 100)',
                        ],
                    ],
                    'required' => ['portafolio'],
                ],
            ],
            [
                'name' => 'obtener_deudas_activas',
                'description' => 'Retorna todas las deudas/obligaciones activas de la empresa (con acreedores como DIAN, etc), incluyendo monto original, total abonado, saldo actual y fecha de vencimiento. Útil para evaluar pasivos corrientes y obligaciones inmediatas.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            // ─── TOOLS DE CONSULTA AVANZADA ───
            [
                'name' => 'buscar_cliente',
                'description' => 'Busca clientes por NIT o por nombre (búsqueda parcial, case insensitive). Retorna lista con vista 360: total de facturas, facturas pagadas, saldo pendiente, fecha primera/última factura y último pago. Útil para identificar al cliente antes de consultar su historial detallado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'q' => [
                            'type' => 'string',
                            'description' => 'Texto a buscar: NIT (numérico) o parte del nombre (ej: "lucerna", "900624804", "conjunto").',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Máximo de resultados (default 10).',
                        ],
                    ],
                    'required' => ['q'],
                ],
            ],
            [
                'name' => 'obtener_actividad_cliente',
                'description' => 'Historial completo de un cliente identificado por NIT. Retorna todas sus facturas (pagadas y pendientes) con comprobante, fechas, montos, estado calculado, días de mora, días para cobrar. Incluye también resumen 360.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nit' => [
                            'type' => 'string',
                            'description' => 'NIT o identificación del cliente (sin puntos ni guiones).',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Máximo de facturas a retornar (default 50, máx 200).',
                        ],
                    ],
                    'required' => ['nit'],
                ],
            ],
            [
                'name' => 'obtener_facturas_pagadas',
                'description' => 'Lista las últimas facturas pagadas (ordenadas por fecha_pago DESC). Filtros opcionales por portafolio, año y mes. Útil para responder "qué clientes me pagaron en X mes" o "últimos pagos recibidos".',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'portafolio' => ['type' => 'string', 'enum' => ['SST','RPS','HUNTING'], 'description' => 'Filtrar por portafolio'],
                        'anio' => ['type' => 'integer', 'description' => 'Año del pago'],
                        'mes' => ['type' => 'integer', 'description' => 'Mes del pago (1-12)'],
                        'limite' => ['type' => 'integer', 'description' => 'Default 30, máx 100'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'consultar_factura',
                'description' => 'Devuelve el detalle completo de UNA factura específica buscada por su comprobante (ej. "FV-2-2107") o número de factura. Incluye datos del cliente, fechas, montos, estado actual, días de mora/cobro y portafolio.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'comprobante' => ['type' => 'string', 'description' => 'Comprobante (ej. FV-2-2107) o número de factura. Búsqueda exacta o parcial.'],
                    ],
                    'required' => ['comprobante'],
                ],
            ],
            [
                'name' => 'buscar_movimiento_bancario',
                'description' => 'Búsqueda libre en movimientos bancarios cargados. Busca el texto en descripción, transacción, oficina de recaudo, referencias y llave_item. Útil para encontrar un pago por referencia, por concepto, por banco origen, etc.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'texto' => ['type' => 'string', 'description' => 'Texto a buscar (ej: "lucerna", "TRX-12345", "DIAN")'],
                        'desde' => ['type' => 'string', 'format' => 'date', 'description' => 'Fecha desde YYYY-MM-DD'],
                        'hasta' => ['type' => 'string', 'format' => 'date', 'description' => 'Fecha hasta YYYY-MM-DD'],
                        'limite' => ['type' => 'integer', 'description' => 'Default 30, máx 100'],
                    ],
                    'required' => ['texto'],
                ],
            ],
            [
                'name' => 'obtener_top_clientes',
                'description' => 'Ranking de clientes por facturado o por cartera pendiente. Útil para identificar clientes estratégicos, clientes con mayor exposición de cartera, top deudores, etc.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'portafolio' => ['type' => 'string', 'enum' => ['SST','RPS','HUNTING'], 'description' => 'Filtrar por portafolio'],
                        'criterio' => ['type' => 'string', 'enum' => ['facturado','cartera_pendiente'], 'description' => 'Ordenamiento. Default: facturado.'],
                        'limite' => ['type' => 'integer', 'description' => 'Default 10, máx 50'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_actividad_mes',
                'description' => 'Resumen de actividad de un mes específico: número de facturas emitidas, facturas pagadas, total facturado, total recaudado bancario (INGRESOS) y top 5 ingresos / top 5 egresos del mes. Útil para análisis mensual rápido.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'anio' => ['type' => 'integer', 'description' => 'Año (ej. 2026). Requerido.'],
                        'mes' => ['type' => 'integer', 'description' => 'Mes 1-12. Requerido.'],
                        'portafolio' => ['type' => 'string', 'enum' => ['SST','RPS','HUNTING'], 'description' => 'Filtrar por portafolio'],
                    ],
                    'required' => ['anio','mes'],
                ],
            ],
            [
                'name' => 'obtener_cuentas_cobro',
                'description' => 'Lista cuentas de cobro de contratistas externos (Cuentas de Cobro = pagos a personas naturales). Filtros por estado (pendiente/pagada) y centro de costo. Útil para ver obligaciones operativas a terceros.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => ['type' => 'string', 'enum' => ['pendiente','pagada','castigada'], 'description' => 'Estado de la cuenta'],
                        'centro_costo' => ['type' => 'string', 'description' => 'Nombre del centro de costo (SST, RPS, etc.)'],
                        'limite' => ['type' => 'integer', 'description' => 'Default 20, máx 100'],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Despacha la ejecución de una tool por nombre.
     * Retorna string JSON listo para enviar a Claude.
     */
    public function ejecutar(string $nombre, array $input): string
    {
        $metodo = 'tool_' . $nombre;
        if (! method_exists($this, $metodo)) {
            return json_encode([
                'error' => "Tool '{$nombre}' no existe en el sistema.",
            ], JSON_UNESCAPED_UNICODE);
        }
        try {
            $resultado = $this->$metodo($input);
            return json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return json_encode([
                'error' => 'Error ejecutando tool: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ─────────────────────────────── TOOLS ───────────────────────────────

    private function tool_obtener_balance_al_corte(array $input): array
    {
        $fecha = $input['fecha'] ?? date('Y-m-t', strtotime('first day of last month'));
        $db = \Config\Database::connect();

        $snapModel = new BalanceSnapshotModel();
        $snap = $snapModel->where('fecha_corte', $fecha)->first();

        if ($snap) {
            return [
                'fecha_corte'       => $fecha,
                'origen'            => 'snapshot_inmutable',
                'creado_por'        => $snap['creado_por'],
                'creado_el'         => $snap['created_at'],
                'cartera_sst'       => (float) $snap['cartera_sst'],
                'cartera_rps'       => (float) $snap['cartera_rps'],
                'saldo_banco_sst'   => (float) $snap['saldo_banco_sst'],
                'saldo_banco_rps'   => (float) $snap['saldo_banco_rps'],
                'total_activos'    => (float) $snap['total_activos'],
                'total_pasivos'    => (float) $snap['total_pasivos'],
                'estado_empresa'   => (float) $snap['estado_empresa'],
                'detalle_pasivos'  => json_decode($snap['detalle_pasivos'] ?? '[]', true) ?: [],
                'notas'            => $snap['notas'],
            ];
        }

        // Cálculo dinámico (sin guardar): replicamos lógica de BalanceController
        $carteraSST = $this->cartera($db, 'SST', $fecha);
        $carteraRPS = $this->cartera($db, 'RPS', $fecha);
        $saldoSST   = $this->saldoBanco($db, 'SST', $fecha);
        $saldoRPS   = $this->saldoBanco($db, 'RPS', $fecha);
        $activos    = $carteraSST + $carteraRPS + $saldoSST + $saldoRPS;

        $deudas = $db->table('tbl_deudas')
            ->where('fecha_registro <=', $fecha)
            ->get()->getResultArray();

        $pasivos = [];
        $totalPasivos = 0;
        foreach ($deudas as $d) {
            $abonado = (float) ($db->table('tbl_deuda_abonos')
                ->selectSum('valor_abono')
                ->where('id_deuda', $d['id_deuda'])
                ->where('fecha_abono <=', $fecha)
                ->get()->getRow()->valor_abono ?? 0);
            $saldo = (float) $d['monto_original'] - $abonado;
            if ($saldo <= 0) continue;
            $pasivos[] = [
                'concepto'        => $d['concepto'],
                'acreedor'        => $d['acreedor'],
                'monto_original'  => (float) $d['monto_original'],
                'abonado'         => $abonado,
                'saldo_al_corte'  => $saldo,
            ];
            $totalPasivos += $saldo;
        }

        return [
            'fecha_corte'      => $fecha,
            'origen'           => 'calculo_dinamico',
            'cartera_sst'      => $carteraSST,
            'cartera_rps'      => $carteraRPS,
            'saldo_banco_sst'  => $saldoSST,
            'saldo_banco_rps'  => $saldoRPS,
            'total_activos'    => $activos,
            'total_pasivos'    => $totalPasivos,
            'estado_empresa'   => $activos - $totalPasivos,
            'detalle_pasivos'  => $pasivos,
        ];
    }

    private function tool_obtener_facturado_recaudo_por_mes(array $input): array
    {
        $portafolio = strtoupper(trim($input['portafolio'] ?? 'FRAMEWORK'));
        $anio = (int) ($input['anio'] ?? date('Y'));
        $db = \Config\Database::connect();

        // FRAMEWORK = SST + RPS consolidado
        $idsPort = [];
        $nombresCC = [];
        if ($portafolio === 'FRAMEWORK') {
            $rows = $db->table('tbl_portafolios')->whereIn('portafolio', ['SST','RPS'])->get()->getResultArray();
            foreach ($rows as $r) { $idsPort[] = (int) $r['id_portafolio']; $nombresCC[] = $r['portafolio']; }
        } else {
            $row = $db->table('tbl_portafolios')->where('portafolio', $portafolio)->get()->getRow();
            if (! $row) return ['error' => "Portafolio '{$portafolio}' no encontrado"];
            $idsPort[] = (int) $row->id_portafolio;
            $nombresCC[] = $portafolio;
        }

        // Presupuesto mensual del año
        $presBuilder = $db->table('tbl_presupuesto_portafolio')
            ->select('mes, SUM(presupuesto) as total')
            ->whereIn('id_portafolio', $idsPort)
            ->where('anio', $anio)
            ->groupBy('mes');
        $presupuestoPorMes = [];
        foreach ($presBuilder->get()->getResultArray() as $r) {
            $presupuestoPorMes[(int) $r['mes']] = (float) $r['total'];
        }

        // Facturado
        $factRows = $db->table('tbl_facturacion')
            ->select('mes, SUM(base_gravada) as total')
            ->whereIn('id_portafolio', $idsPort)
            ->where('anio', $anio)
            ->groupBy('mes')->get()->getResultArray();
        $facturadoPorMes = [];
        foreach ($factRows as $r) {
            $facturadoPorMes[(int) $r['mes']] = (float) $r['total'];
        }

        // Recaudo (INGRESOS bancarios filtrados por centro_costo)
        $recRows = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cb.mes, SUM(cb.valor) as total')
            ->join('tbl_centros_costo cc', 'cc.id_centro_costo = cb.id_centro_costo', 'left')
            ->where('cb.anio', $anio)
            ->where('cb.deb_cred', 'INGRESO')
            ->whereIn('cc.centro_costo', $nombresCC)
            ->groupBy('cb.mes')->get()->getResultArray();
        $recaudoPorMes = [];
        foreach ($recRows as $r) {
            $recaudoPorMes[(int) $r['mes']] = (float) $r['total'];
        }

        $serie = [];
        $totales = ['presupuesto' => 0, 'facturado' => 0, 'recaudo' => 0];
        for ($m = 1; $m <= 12; $m++) {
            $pres = $presupuestoPorMes[$m] ?? 0;
            $fact = $facturadoPorMes[$m] ?? 0;
            $rec  = $recaudoPorMes[$m] ?? 0;
            $serie[] = [
                'mes' => $m,
                'presupuesto' => $pres,
                'facturado'   => $fact,
                'recaudo'     => $rec,
                'cumpl_facturacion_pct' => $pres > 0 ? round($fact / $pres * 100, 2) : null,
                'cumpl_recaudo_pct'     => $pres > 0 ? round($rec  / $pres * 100, 2) : null,
            ];
            $totales['presupuesto'] += $pres;
            $totales['facturado']   += $fact;
            $totales['recaudo']     += $rec;
        }

        return [
            'portafolio' => $portafolio,
            'anio'       => $anio,
            'serie_mensual' => $serie,
            'totales_anio'  => $totales,
            'cumpl_facturacion_anio_pct' => $totales['presupuesto'] > 0
                ? round($totales['facturado'] / $totales['presupuesto'] * 100, 2) : null,
            'cumpl_recaudo_anio_pct' => $totales['presupuesto'] > 0
                ? round($totales['recaudo']  / $totales['presupuesto'] * 100, 2) : null,
        ];
    }

    private function tool_obtener_cartera_detalle(array $input): array
    {
        $portafolio = strtoupper(trim($input['portafolio'] ?? ''));
        $limite = min(100, max(1, (int) ($input['limite'] ?? 30)));
        $db = \Config\Database::connect();

        $port = $db->table('tbl_portafolios')->where('portafolio', $portafolio)->get()->getRow();
        if (! $port) return ['error' => "Portafolio '{$portafolio}' no encontrado"];

        $rows = $db->table('tbl_facturacion')
            ->select('comprobante, fecha_elaboracion, identificacion, nombre_tercero, base_gravada, valor_pagado, anticipo, fecha_pago, estado_pago')
            ->where('id_portafolio', $port->id_portafolio)
            ->where('pagado', 0)
            ->orderBy('fecha_elaboracion', 'ASC') // más viejas primero
            ->limit($limite)
            ->get()->getResultArray();

        $hoy = date('Y-m-d');
        $facturas = [];
        $totalCartera = 0;
        foreach ($rows as $r) {
            $saldo = (float)$r['base_gravada'] - (float)($r['valor_pagado'] ?? 0) - (float)($r['anticipo'] ?? 0);
            if ($saldo <= 0) continue;
            $diasMora = $r['fecha_elaboracion']
                ? (int) ((strtotime($hoy) - strtotime($r['fecha_elaboracion'])) / 86400) - 30
                : null;
            $facturas[] = [
                'comprobante'      => $r['comprobante'],
                'fecha_elaboracion'=> $r['fecha_elaboracion'],
                'dias_mora'        => $diasMora > 0 ? $diasMora : 0,
                'nit_cliente'      => $r['identificacion'],
                'cliente'          => $r['nombre_tercero'],
                'base_gravada'     => (float) $r['base_gravada'],
                'abonado'          => (float) ($r['valor_pagado'] ?? 0),
                'anticipo'         => (float) ($r['anticipo'] ?? 0),
                'saldo_pendiente'  => $saldo,
                'estado'           => $r['estado_pago'] ?: 'pendiente',
            ];
            $totalCartera += $saldo;
        }

        return [
            'portafolio'          => $portafolio,
            'total_facturas'      => count($facturas),
            'total_cartera_usd_local' => $totalCartera,
            'facturas'            => $facturas,
            'ordenamiento'        => 'fecha_elaboracion ascendente (más viejas primero)',
        ];
    }

    private function tool_obtener_deudas_activas(array $input): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('tbl_deudas')
            ->where('estado', 'activa')
            ->orderBy('fecha_registro', 'ASC')
            ->get()->getResultArray();

        $hoy = date('Y-m-d');
        $deudas = [];
        $total = 0;
        foreach ($rows as $d) {
            $abonado = (float) ($db->table('tbl_deuda_abonos')
                ->selectSum('valor_abono')
                ->where('id_deuda', $d['id_deuda'])
                ->get()->getRow()->valor_abono ?? 0);
            $saldo = (float)$d['monto_original'] - $abonado;
            if ($saldo <= 0) continue;
            $diasParaVencer = $d['fecha_vencimiento']
                ? (int) ((strtotime($d['fecha_vencimiento']) - strtotime($hoy)) / 86400)
                : null;
            $deudas[] = [
                'concepto'        => $d['concepto'],
                'acreedor'        => $d['acreedor'],
                'monto_original'  => (float) $d['monto_original'],
                'abonado'         => $abonado,
                'saldo_actual'    => $saldo,
                'fecha_registro'  => $d['fecha_registro'],
                'fecha_vencimiento' => $d['fecha_vencimiento'],
                'dias_para_vencer' => $diasParaVencer,
                'vencida'         => $diasParaVencer !== null && $diasParaVencer < 0,
                'notas'           => $d['notas'],
            ];
            $total += $saldo;
        }

        return [
            'total_deudas'        => count($deudas),
            'total_saldo_actual'  => $total,
            'deudas'              => $deudas,
            'al_dia_de_hoy'       => $hoy,
        ];
    }

    // ──────────────────── TOOLS DE CONSULTA AVANZADA ────────────────────

    private function tool_buscar_cliente(array $input): array
    {
        $q = trim((string) ($input['q'] ?? ''));
        $limite = min(50, max(1, (int) ($input['limite'] ?? 10)));
        if ($q === '') return ['error' => 'Parámetro q vacío'];
        $db = \Config\Database::connect();

        $builder = $db->table('vw_cliente_360')
            ->orderBy('total_facturado_bruto', 'DESC')
            ->limit($limite);

        if (ctype_digit($q)) {
            $builder->like('nit', $q);
        } else {
            $builder->like('nombre_cliente', $q);
        }
        $rows = $builder->get()->getResultArray();

        return [
            'query'      => $q,
            'resultados' => $rows,
            'total'      => count($rows),
            'nota'       => count($rows) === 0 ? 'Sin coincidencias. Probá con otra parte del nombre o el NIT exacto.' : null,
        ];
    }

    private function tool_obtener_actividad_cliente(array $input): array
    {
        $nit = preg_replace('/[^0-9]/', '', (string) ($input['nit'] ?? ''));
        $limite = min(200, max(1, (int) ($input['limite'] ?? 50)));
        if ($nit === '') return ['error' => 'NIT requerido'];
        $db = \Config\Database::connect();

        // Resumen 360 (puede haber múltiples portafolios)
        $resumen = $db->table('vw_cliente_360')->where('nit', $nit)->get()->getResultArray();
        if (empty($resumen)) return ['error' => "Cliente con NIT {$nit} no encontrado en facturación"];

        // Facturas
        $facturas = $db->table('vw_factura_detalle')
            ->select('comprobante, numero_factura, fecha_elaboracion, fecha_pago, base_gravada, valor_pagado, anticipo, saldo_actual, estado_calculado, dias_mora, dias_para_cobrar, portafolio')
            ->where('nit_cliente', $nit)
            ->orderBy('fecha_elaboracion', 'DESC')
            ->limit($limite)
            ->get()->getResultArray();

        return [
            'nit'      => $nit,
            'cliente'  => $resumen[0]['nombre_cliente'] ?? null,
            'resumen_por_portafolio' => $resumen,
            'facturas' => $facturas,
            'total_facturas_retornadas' => count($facturas),
        ];
    }

    private function tool_obtener_facturas_pagadas(array $input): array
    {
        $portafolio = isset($input['portafolio']) ? strtoupper((string) $input['portafolio']) : null;
        $anio = isset($input['anio']) ? (int) $input['anio'] : null;
        $mes  = isset($input['mes']) ? (int) $input['mes'] : null;
        $limite = min(100, max(1, (int) ($input['limite'] ?? 30)));
        $db = \Config\Database::connect();

        $builder = $db->table('vw_factura_detalle')
            ->select('comprobante, fecha_elaboracion, fecha_pago, nit_cliente, cliente, portafolio, base_gravada, valor_pagado, dias_para_cobrar, estado_calculado')
            ->where('pagado', 1)
            ->orderBy('fecha_pago', 'DESC')
            ->limit($limite);
        if ($portafolio) $builder->where('portafolio', $portafolio);
        if ($anio) $builder->where('YEAR(fecha_pago)', $anio);
        if ($mes) $builder->where('MONTH(fecha_pago)', $mes);

        $rows = $builder->get()->getResultArray();
        return [
            'filtros' => compact('portafolio','anio','mes','limite'),
            'total'   => count($rows),
            'facturas' => $rows,
        ];
    }

    private function tool_consultar_factura(array $input): array
    {
        $comp = trim((string) ($input['comprobante'] ?? ''));
        if ($comp === '') return ['error' => 'Comprobante requerido'];
        $db = \Config\Database::connect();

        $rows = $db->table('vw_factura_detalle')
            ->groupStart()
                ->like('comprobante', $comp)
                ->orWhere('numero_factura', is_numeric($comp) ? (int)$comp : 0)
            ->groupEnd()
            ->orderBy('fecha_elaboracion', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        if (empty($rows)) return ['error' => "No se encontró factura con '{$comp}'"];
        return ['total' => count($rows), 'facturas' => $rows];
    }

    private function tool_buscar_movimiento_bancario(array $input): array
    {
        $texto = trim((string) ($input['texto'] ?? ''));
        $desde = $input['desde'] ?? null;
        $hasta = $input['hasta'] ?? null;
        $limite = min(100, max(1, (int) ($input['limite'] ?? 30)));
        if ($texto === '') return ['error' => 'Texto requerido'];
        $db = \Config\Database::connect();

        $builder = $db->table('vw_recaudo_detalle')
            ->select('id_conciliacion, fecha_sistema, nombre_cuenta, centro_costo, deb_cred, valor, llave_item, descripcion_motivo, transaccion, oficina_recaudo, referencia_1, referencia_2, item_cliente')
            ->groupStart()
                ->like('descripcion_motivo', $texto)
                ->orLike('transaccion', $texto)
                ->orLike('oficina_recaudo', $texto)
                ->orLike('referencia_1', $texto)
                ->orLike('referencia_2', $texto)
                ->orLike('llave_item', $texto)
                ->orLike('item_cliente', $texto)
            ->groupEnd()
            ->orderBy('fecha_sistema', 'DESC')
            ->limit($limite);
        if ($desde) $builder->where('fecha_sistema >=', $desde);
        if ($hasta) $builder->where('fecha_sistema <=', $hasta);

        $rows = $builder->get()->getResultArray();
        return ['query' => $texto, 'total' => count($rows), 'movimientos' => $rows];
    }

    private function tool_obtener_top_clientes(array $input): array
    {
        $portafolio = isset($input['portafolio']) ? strtoupper((string) $input['portafolio']) : null;
        $criterio = $input['criterio'] ?? 'facturado';
        $limite = min(50, max(1, (int) ($input['limite'] ?? 10)));
        $db = \Config\Database::connect();

        $orderField = $criterio === 'cartera_pendiente' ? 'saldo_pendiente' : 'total_facturado_bruto';

        $builder = $db->table('vw_cliente_360')
            ->orderBy($orderField, 'DESC')
            ->limit($limite);
        if ($portafolio) $builder->where('portafolio', $portafolio);

        $rows = $builder->get()->getResultArray();
        return [
            'criterio'   => $criterio,
            'portafolio' => $portafolio,
            'total'      => count($rows),
            'clientes'   => $rows,
        ];
    }

    private function tool_obtener_actividad_mes(array $input): array
    {
        $anio = (int) ($input['anio'] ?? 0);
        $mes  = (int) ($input['mes'] ?? 0);
        $portafolio = isset($input['portafolio']) ? strtoupper((string) $input['portafolio']) : null;
        if (! $anio || ! $mes) return ['error' => 'anio y mes son requeridos'];
        $db = \Config\Database::connect();

        // Facturación del mes
        $fBuilder = $db->table('vw_factura_detalle')
            ->select('COUNT(*) as emitidas, SUM(CASE WHEN pagado=1 THEN 1 ELSE 0 END) as ya_pagadas, SUM(base_gravada) as total_facturado')
            ->where('anio', $anio)->where('mes', $mes);
        if ($portafolio) $fBuilder->where('portafolio', $portafolio);
        $fact = $fBuilder->get()->getRow();

        // Pagos recibidos del mes (fecha_pago)
        $pBuilder = $db->table('vw_factura_detalle')
            ->select('COUNT(*) as pagos_recibidos, SUM(valor_pagado) as total_recaudado_facturas')
            ->where('YEAR(fecha_pago)', $anio)->where('MONTH(fecha_pago)', $mes);
        if ($portafolio) $pBuilder->where('portafolio', $portafolio);
        $pagos = $pBuilder->get()->getRow();

        // Top 5 ingresos bancarios del mes
        $rBuilder = $db->table('vw_recaudo_detalle')
            ->select('fecha_sistema, valor, item_cliente, descripcion_motivo, nombre_cuenta, centro_costo')
            ->where('anio', $anio)->where('mes', $mes)
            ->where('deb_cred', 'INGRESO')
            ->orderBy('valor', 'DESC')->limit(5);
        if ($portafolio) $rBuilder->where('centro_costo', $portafolio);
        $topIngresos = $rBuilder->get()->getResultArray();

        // Top 5 egresos bancarios del mes
        $eBuilder = $db->table('vw_recaudo_detalle')
            ->select('fecha_sistema, valor, item_cliente, descripcion_motivo, nombre_cuenta, centro_costo')
            ->where('anio', $anio)->where('mes', $mes)
            ->where('deb_cred', 'EGRESO')
            ->orderBy('valor', 'ASC')->limit(5);
        if ($portafolio) $eBuilder->where('centro_costo', $portafolio);
        $topEgresos = $eBuilder->get()->getResultArray();

        return [
            'periodo' => sprintf('%04d-%02d', $anio, $mes),
            'portafolio' => $portafolio,
            'facturacion' => [
                'facturas_emitidas' => (int) ($fact->emitidas ?? 0),
                'facturas_ya_pagadas' => (int) ($fact->ya_pagadas ?? 0),
                'total_facturado_base_gravada' => (float) ($fact->total_facturado ?? 0),
            ],
            'pagos_recibidos_en_mes' => [
                'cantidad' => (int) ($pagos->pagos_recibidos ?? 0),
                'total_recaudado' => (float) ($pagos->total_recaudado_facturas ?? 0),
            ],
            'top_5_ingresos_bancarios' => $topIngresos,
            'top_5_egresos_bancarios'  => $topEgresos,
        ];
    }

    private function tool_obtener_cuentas_cobro(array $input): array
    {
        $estado = $input['estado'] ?? null;
        $centroCosto = isset($input['centro_costo']) ? strtoupper((string) $input['centro_costo']) : null;
        $limite = min(100, max(1, (int) ($input['limite'] ?? 20)));
        $db = \Config\Database::connect();

        $builder = $db->table('tbl_cuenta_cobro cc')
            ->select('cc.id_cuenta_cobro, cc.tipo_documento, cc.documento, cc.nombre_cobrador, cc.fecha_gasto, cc.descripcion_servicio, ce.centro_costo, cc.valor_bruto, cc.total_retenciones, cc.valor_neto_a_pagar, cc.estado, cc.fecha_pago, cc.forma_pago')
            ->join('tbl_centros_costo ce', 'ce.id_centro_costo = cc.id_centro_costo', 'left')
            ->orderBy('cc.fecha_gasto', 'DESC')
            ->limit($limite);
        if ($estado) $builder->where('cc.estado', $estado);
        if ($centroCosto) $builder->where('ce.centro_costo', $centroCosto);

        $rows = $builder->get()->getResultArray();
        $total = 0;
        foreach ($rows as $r) $total += (float) $r['valor_neto_a_pagar'];

        return [
            'filtros' => ['estado' => $estado, 'centro_costo' => $centroCosto],
            'total_cuentas' => count($rows),
            'total_neto_a_pagar' => $total,
            'cuentas' => $rows,
        ];
    }

    // ── helpers privados ──

    private function cartera($db, string $portafolio, string $corte): float
    {
        $port = $db->table('tbl_portafolios')->where('portafolio', $portafolio)->get()->getRow();
        if (! $port) return 0.0;
        $row = $db->table('tbl_facturacion')
            ->select('SUM(base_gravada - COALESCE(valor_pagado,0) - COALESCE(anticipo,0)) as saldo')
            ->where('id_portafolio', $port->id_portafolio)
            ->where('fecha_elaboracion <=', $corte)
            ->groupStart()->where('pagado', 0)->orWhere('fecha_pago >', $corte)->groupEnd()
            ->get()->getRow();
        return max(0, (float) ($row->saldo ?? 0));
    }

    private function saldoBanco($db, string $nombreCuenta, string $corte): float
    {
        $cu = $db->table('tbl_cuentas_banco')->where('nombre_cuenta', $nombreCuenta)->get()->getRow();
        if (! $cu) return 0.0;
        $row = $db->table('tbl_conciliacion_bancaria')
            ->selectSum('valor')
            ->where('id_cuenta_banco', $cu->id_cuenta_banco)
            ->where('fecha_sistema <=', $corte)
            ->get()->getRow();
        return (float) ($cu->saldo_inicial ?? 0) + (float) ($row->valor ?? 0);
    }
}
