<?php

namespace App\Controllers;

use App\Models\PortafolioModel;
use App\Models\PresupuestoPortafolioModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class DashboardPortafolioController extends BaseController
{
    protected $portafolioModel;
    protected $presupuestoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->portafolioModel  = new PortafolioModel();
        $this->presupuestoModel = new PresupuestoPortafolioModel();
    }

    /**
     * Dashboard de cumplimiento por portafolio.
     * Segmentadores: portafolio (SST/RPS/HUNTING/framework), anio, meses[]
     */
    public function index()
    {
        $db = \Config\Database::connect();

        // ── Segmentadores ──
        $portafolioParam = $this->request->getGet('portafolio') ?: 'framework';
        $anioParam       = $this->request->getGet('anio');
        // 'todos' (default) o un año específico
        $anioFiltro      = ($anioParam === null || $anioParam === '' || $anioParam === 'todos')
            ? 'todos'
            : (int) $anioParam;
        $mesesParam        = $this->request->getGet('meses');
        $filtrosAplicados  = (bool) $this->request->getGet('filtros_aplicados');
        if ($filtrosAplicados) {
            // El usuario interactuó: respetar exactamente lo que llegó (puede ser vacío)
            $meses = is_array($mesesParam) ? array_map('intval', $mesesParam) : [];
        } else {
            // Primera carga: default a todos los meses
            $meses = is_array($mesesParam) && !empty($mesesParam)
                ? array_map('intval', $mesesParam)
                : range(1, 12);
        }

        // Lista de portafolios disponibles + framework (consolidado)
        $todosPortafolios = $this->portafolioModel->orderBy('portafolio', 'ASC')->findAll();
        $data['portafolios']     = $todosPortafolios;
        $data['portafolioParam'] = $portafolioParam;
        $data['anio']            = $anioFiltro;
        $data['mesesSel']        = $meses;

        // Años disponibles: unión de presupuestos + facturación + bancaria
        $aniosPres = $db->table('tbl_presupuesto_portafolio')->select('anio')->distinct()->get()->getResultArray();
        $aniosFact = $db->table('tbl_facturacion')->select('anio')->distinct()->get()->getResultArray();
        $aniosBan  = $db->table('tbl_conciliacion_bancaria')->select('anio')->distinct()->get()->getResultArray();
        $aniosUnion = array_unique(array_merge(
            array_column($aniosPres, 'anio'),
            array_column($aniosFact, 'anio'),
            array_column($aniosBan, 'anio')
        ));
        rsort($aniosUnion);
        $data['anios'] = $aniosUnion ?: [date('Y')];

        // Resolver qué id_portafolio o nombre aplicar
        // framework = SST + RPS consolidado
        $portafoliosFiltro = []; // ids a sumar
        $nombresFiltro     = []; // nombres equivalentes en centro_costo
        $titulo = '';
        $colorPrimario = '#0d6efd';

        if ($portafolioParam === 'framework') {
            $titulo = 'FRAMEWORK';
            $colorPrimario = '#0d6efd';
            foreach ($todosPortafolios as $p) {
                if (in_array($p['portafolio'], ['SST', 'RPS'])) {
                    $portafoliosFiltro[] = (int) $p['id_portafolio'];
                    $nombresFiltro[]     = $p['portafolio'];
                }
            }
        } else {
            // Buscar por nombre
            foreach ($todosPortafolios as $p) {
                if ($p['portafolio'] === $portafolioParam) {
                    $portafoliosFiltro[] = (int) $p['id_portafolio'];
                    $nombresFiltro[]     = $p['portafolio'];
                    $titulo              = $p['portafolio'];
                    break;
                }
            }
            $colorPrimario = match($portafolioParam) {
                'SST'     => '#dc3545',
                'RPS'     => '#198754',
                'HUNTING' => '#fd7e14',
                default   => '#0d6efd',
            };
        }
        $data['titulo']        = $titulo ?: 'PORTAFOLIO';
        $data['colorPrimario'] = $colorPrimario;

        if (empty($portafoliosFiltro)) {
            $data['presupuestoTotal'] = 0;
            $data['facturadoTotal']   = 0;
            $data['recaudoTotal']     = 0;
            $data['carteraTotal']     = 0;
            $data['indicadorFact']    = 0;
            $data['indicadorRecaudo'] = 0;
            $data['serieFacturado']   = [];
            $data['serieRecaudo']     = [];
            $data['serieMeses']       = [];
            return view('conciliaciones/dashboard_portafolio', $data);
        }

        // ── Helper: aplicar filtros año + meses + portafolios ──
        $aplicarFiltros = function ($builder, string $aliasAnio, string $aliasMes, string $aliasPort, $idsPort) use ($anioFiltro, $meses) {
            if ($anioFiltro !== 'todos') $builder->where($aliasAnio, $anioFiltro);
            $builder->whereIn($aliasMes, $meses);
            $builder->whereIn($aliasPort, $idsPort);
            return $builder;
        };

        // ── 1) PRESUPUESTO del periodo ──
        $presupuestoBuilder = $db->table('tbl_presupuesto_portafolio')->selectSum('presupuesto');
        $aplicarFiltros($presupuestoBuilder, 'anio', 'mes', 'id_portafolio', $portafoliosFiltro);
        $presupuestoRow = $presupuestoBuilder->get()->getRow();
        $data['presupuestoTotal'] = (float) ($presupuestoRow->presupuesto ?? 0);

        // ── 2) FACTURADO (base_gravada) por anio+mes ──
        $facturadoBuilder = $db->table('tbl_facturacion')
            ->select('anio, mes, SUM(base_gravada) as total')
            ->groupBy(['anio', 'mes'])
            ->orderBy('anio, mes', 'ASC');
        $aplicarFiltros($facturadoBuilder, 'anio', 'mes', 'id_portafolio', $portafoliosFiltro);
        $facturadoRows = $facturadoBuilder->get()->getResultArray();

        $facturadoPorAnioMes = []; // [anio][mes] = total
        foreach ($facturadoRows as $r) {
            $facturadoPorAnioMes[(int) $r['anio']][(int) $r['mes']] = (float) $r['total'];
        }

        // ── 3) RECAUDO: INGRESOS por centro_costo equivalente ──
        $recaudoBuilder = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cb.anio, cb.mes, SUM(cb.valor) as total')
            ->join('tbl_centros_costo cc', 'cc.id_centro_costo = cb.id_centro_costo', 'left')
            ->where('cb.deb_cred', 'INGRESO')
            ->whereIn('cc.centro_costo', $nombresFiltro)
            ->whereIn('cb.mes', $meses)
            ->groupBy(['cb.anio', 'cb.mes'])
            ->orderBy('cb.anio, cb.mes', 'ASC');
        if ($anioFiltro !== 'todos') $recaudoBuilder->where('cb.anio', $anioFiltro);
        $recaudoRows = $recaudoBuilder->get()->getResultArray();

        $recaudoPorAnioMes = [];
        foreach ($recaudoRows as $r) {
            $recaudoPorAnioMes[(int) $r['anio']][(int) $r['mes']] = (float) $r['total'];
        }

        // ── 4) CARTERA: facturas no pagadas (acumulado total del portafolio) ──
        $carteraRow = $db->table('tbl_facturacion')
            ->select('SUM(base_gravada - COALESCE(valor_pagado,0) - COALESCE(anticipo,0)) as cartera')
            ->where('pagado', 0)
            ->whereIn('id_portafolio', $portafoliosFiltro)
            ->get()->getRow();
        $data['carteraTotal'] = max(0, (float) ($carteraRow->cartera ?? 0));

        // ── Totales para cards ──
        $data['facturadoTotal'] = 0;
        foreach ($facturadoPorAnioMes as $anioData) $data['facturadoTotal'] += array_sum($anioData);
        $data['recaudoTotal'] = 0;
        foreach ($recaudoPorAnioMes as $anioData) $data['recaudoTotal'] += array_sum($anioData);

        // ── Indicadores % ──
        $data['indicadorFact']    = $data['presupuestoTotal'] > 0
            ? ($data['facturadoTotal'] / $data['presupuestoTotal']) * 100
            : 0;
        $data['indicadorRecaudo'] = $data['presupuestoTotal'] > 0
            ? ($data['recaudoTotal'] / $data['presupuestoTotal']) * 100
            : 0;

        // ── Series para gráficos ──
        // Eje X: si "todos" → recorre todos los años con datos × meses seleccionados
        //        si año fijo → solo ese año × meses seleccionados
        $mesesNombre = ['', 'Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $aniosEjeX = ($anioFiltro === 'todos') ? $aniosUnion : [(int) $anioFiltro];
        sort($aniosEjeX);

        $serieMeses = [];
        $serieFact  = [];
        $serieRec   = [];
        foreach ($aniosEjeX as $a) {
            foreach ($meses as $m) {
                $serieMeses[] = $mesesNombre[$m] . ' ' . $a;
                $serieFact[]  = round($facturadoPorAnioMes[$a][$m] ?? 0);
                $serieRec[]   = round($recaudoPorAnioMes[$a][$m] ?? 0);
            }
        }
        $data['serieMeses']     = $serieMeses;
        $data['serieFacturado'] = $serieFact;
        $data['serieRecaudo']   = $serieRec;

        return view('conciliaciones/dashboard_portafolio', $data);
    }
}
