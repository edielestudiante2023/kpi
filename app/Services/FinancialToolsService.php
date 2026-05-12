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
