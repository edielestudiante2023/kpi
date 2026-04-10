<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class DashboardFinancieroController extends BaseController
{
    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
    }

    public function index()
    {
        $db = \Config\Database::connect();

        $anio  = $this->request->getGet('anio') ?: date('Y');
        $rango = $this->request->getGet('rango') ?: 'todos';

        // Años disponibles
        $data['anios'] = $db->table('tbl_conciliacion_bancaria')
            ->select('anio')->distinct()->orderBy('anio', 'DESC')
            ->get()->getResultArray();
        $data['anioActual']  = $anio;
        $data['rangoActual'] = $rango;

        // Calcular rango de fechas
        if ($rango === 'personalizado') {
            $fechas = [
                'desde' => $this->request->getGet('desde') ?: null,
                'hasta' => $this->request->getGet('hasta') ?: null,
            ];
        } else {
            $anioNum = ($anio === 'todos') ? 0 : (int) $anio;
            $fechas = $this->calcularRangoFechas($rango, $anioNum);
        }
        $data['fechaDesde'] = $fechas['desde'];
        $data['fechaHasta'] = $fechas['hasta'];

        // Helper para aplicar filtro de fechas
        $aplicarFechas = function($builder, $campoFecha = 'cb.fecha_sistema') use ($fechas) {
            if ($fechas['desde']) $builder->where("{$campoFecha} >=", $fechas['desde']);
            if ($fechas['hasta']) $builder->where("{$campoFecha} <=", $fechas['hasta']);
            return $builder;
        };

        // ── INGRESOS, COSTOS FIJOS, VARIABLES, NEUTROS ──
        $builder = $db->table('tbl_conciliacion_bancaria cb')
            ->select("cc.tipo, SUM(cb.valor) as total_valor")
            ->join('tbl_clasificacion_costos cc', 'cc.llave_item = cb.llave_item', 'left');
        $aplicarFechas($builder);

        $resumen = $builder->groupBy('cc.tipo')->get()->getResultArray();

        $ingresos      = 0;
        $costosFijos    = 0;
        $costosVariables = 0;
        $neutros        = 0;

        foreach ($resumen as $r) {
            $val = (float) $r['total_valor'];
            switch ($r['tipo']) {
                case 'ingreso':  $ingresos = $val; break;
                case 'fijo':     $costosFijos = $val; break;
                case 'variable': $costosVariables = $val; break;
                case 'neutro':   $neutros = $val; break;
            }
        }

        // Costos son negativos en la BD, los convertimos a positivo para mostrar
        $data['ingresos']         = $ingresos;
        $data['costosFijos']      = abs($costosFijos);
        $data['costosVariables']  = abs($costosVariables);
        $data['utilidadOperativa'] = $ingresos + $costosFijos + $costosVariables; // costos ya son negativos

        // ── CARTERA POR COBRAR (facturas no pagadas) ──
        $carteraBuilder = $db->table('tbl_facturacion')
            ->select('SUM(base_gravada) as total_cartera, COUNT(*) as facturas_pendientes')
            ->where('pagado', 0);
        // Cartera siempre es global (no filtrada por fecha)
        $cartera = $carteraBuilder->get()->getRow();
        $data['cartera']           = (float) ($cartera->total_cartera ?? 0);
        $data['facturasPendientes'] = (int) ($cartera->facturas_pendientes ?? 0);

        // ── DEUDAS (saldo pendiente de obligaciones activas) ──
        $deudas = $db->table('tbl_deudas d')
            ->select('SUM(d.monto_original) as total_deuda, COUNT(*) as total_obligaciones')
            ->where('d.estado', 'activa')
            ->get()->getRow();

        $abonos = $db->table('tbl_deuda_abonos a')
            ->select('SUM(a.valor_abono) as total_abonado')
            ->join('tbl_deudas d', 'd.id_deuda = a.id_deuda')
            ->where('d.estado', 'activa')
            ->get()->getRow();

        $totalDeuda  = (float) ($deudas->total_deuda ?? 0);
        $totalAbonado = (float) ($abonos->total_abonado ?? 0);
        $data['deudaSaldo']        = $totalDeuda - $totalAbonado;
        $data['totalObligaciones'] = (int) ($deudas->total_obligaciones ?? 0);

        // ── SALDOS BANCARIOS ──
        $cuentas = $db->table('tbl_cuentas_banco')->get()->getResultArray();
        $data['cuentasBanco'] = [];
        $data['saldoTotalBancos'] = 0;

        foreach ($cuentas as $cuenta) {
            $idCuenta = (int) $cuenta['id_cuenta_banco'];
            $saldoInicial = (float) $cuenta['saldo_inicial'];

            // Sumar todos los movimientos de esta cuenta (valor ya tiene signo)
            $movimientos = $db->table('tbl_conciliacion_bancaria')
                ->select('SUM(valor) as total_mov')
                ->where('id_cuenta_banco', $idCuenta)
                ->get()->getRow();

            $totalMov = (float) ($movimientos->total_mov ?? 0);
            $saldoActual = $saldoInicial + $totalMov;

            $data['cuentasBanco'][] = [
                'id'            => $idCuenta,
                'nombre'        => $cuenta['nombre_cuenta'],
                'saldo_inicial' => $saldoInicial,
                'movimientos'   => $totalMov,
                'saldo_actual'  => $saldoActual,
            ];

            $data['saldoTotalBancos'] += $saldoActual;
        }

        // ── IVA PROYECTADO (cuatrimestre actual) ──
        $mesActual = (int) date('m');
        $anioActualIva = (int) date('Y');
        if ($mesActual >= 1 && $mesActual <= 4) {
            $ivaDesde = sprintf('%04d-01-01', $anioActualIva);
            $ivaHasta = sprintf('%04d-04-30', $anioActualIva);
            $ivaPeriodoLabel = 'Ene–Abr ' . $anioActualIva;
        } elseif ($mesActual >= 5 && $mesActual <= 8) {
            $ivaDesde = sprintf('%04d-05-01', $anioActualIva);
            $ivaHasta = sprintf('%04d-08-31', $anioActualIva);
            $ivaPeriodoLabel = 'May–Ago ' . $anioActualIva;
        } else {
            $ivaDesde = sprintf('%04d-09-01', $anioActualIva);
            $ivaHasta = sprintf('%04d-12-31', $anioActualIva);
            $ivaPeriodoLabel = 'Sep–Dic ' . $anioActualIva;
        }

        $ivaProyectado = $db->table('tbl_facturacion')
            ->select('SUM(iva) as total_iva, COUNT(*) as facturas')
            ->where('fecha_elaboracion >=', $ivaDesde)
            ->where('fecha_elaboracion <=', $ivaHasta)
            ->get()->getRow();

        $data['ivaProyectado']    = (float) ($ivaProyectado->total_iva ?? 0);
        $data['ivaFacturas']      = (int) ($ivaProyectado->facturas ?? 0);
        $data['ivaPeriodoLabel']  = $ivaPeriodoLabel;

        // ── POSICIÓN NETA ──
        // Activos: Bancos + Cartera
        // Pasivos: Deudas + IVA Proyectado
        $data['totalActivos']       = $data['saldoTotalBancos'] + $data['cartera'];
        $data['totalPasivos']       = $data['deudaSaldo'] + $data['ivaProyectado'];
        $data['posicionNeta']       = $data['totalActivos'] - $data['totalPasivos'];
        $data['resultadoIntegral']  = $data['utilidadOperativa'] - $data['deudaSaldo'];

        // ── DESGLOSE POR CATEGORÍA ──
        $desglose = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cc.categoria, cc.tipo, SUM(cb.valor) as total_valor, COUNT(*) as movimientos')
            ->join('tbl_clasificacion_costos cc', 'cc.llave_item = cb.llave_item', 'left')
            ->where('cc.tipo !=', 'neutro');
        $aplicarFechas($desglose);

        $data['desglose'] = $desglose->groupBy('cc.categoria, cc.tipo')
            ->orderBy('cc.tipo', 'ASC')
            ->orderBy('total_valor', 'ASC')
            ->get()->getResultArray();

        // ── EVOLUCIÓN MENSUAL (para gráfico) ──
        $anioGrafico = ($anio === 'todos') ? (int) date('Y') : (int) $anio;
        $evolucion = $db->table('tbl_conciliacion_bancaria cb')
            ->select("cb.mes_real, cc.tipo, SUM(cb.valor) as total_valor")
            ->join('tbl_clasificacion_costos cc', 'cc.llave_item = cb.llave_item', 'left')
            ->where('cb.anio', $anioGrafico)
            ->whereIn('cc.tipo', ['ingreso', 'fijo', 'variable'])
            ->groupBy('cb.mes_real, cc.tipo')
            ->orderBy('cb.mes_real', 'ASC')
            ->get()->getResultArray();

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $meses[$m] = ['ingreso' => 0, 'fijo' => 0, 'variable' => 0];
        }
        foreach ($evolucion as $e) {
            $meses[(int)$e['mes_real']][$e['tipo']] = (float) $e['total_valor'];
        }
        $data['evolucionMensual'] = $meses;

        return view('conciliaciones/dashboard_financiero', $data);
    }

    private function calcularRangoFechas(string $rango, int $anio): array
    {
        $desde = null;
        $hasta = null;

        switch ($rango) {
            case 'todos':
                if ($anio !== 0) {
                    $desde = sprintf('%04d-01-01', $anio);
                    $hasta = sprintf('%04d-12-31', $anio);
                }
                break;
            case 'mes_actual':
                $desde = date('Y-m-01');
                $hasta = date('Y-m-t');
                break;
            case 'mes_anterior':
                $desde = date('Y-m-01', strtotime('first day of last month'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'bimestre_anterior':
                $desde = date('Y-m-01', strtotime('-2 months'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'trimestre_anterior':
                $desde = date('Y-m-01', strtotime('-3 months'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'cuatrimestre_anterior':
                $desde = date('Y-m-01', strtotime('-4 months'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'semestre_anterior':
                $desde = date('Y-m-01', strtotime('-6 months'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            default:
                if (preg_match('/^(\d{2})$/', $rango, $m)) {
                    $mes = (int) $m[1];
                    if ($anio === 0) $anio = (int) date('Y');
                    $desde = sprintf('%04d-%02d-01', $anio, $mes);
                    $hasta = date('Y-m-t', strtotime($desde));
                }
                break;
        }

        return ['desde' => $desde, 'hasta' => $hasta];
    }
}
