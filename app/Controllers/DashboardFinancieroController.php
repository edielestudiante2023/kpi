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

        $anio = (int) ($this->request->getGet('anio') ?: date('Y'));
        $mes  = $this->request->getGet('mes') ?: null;

        // Años disponibles
        $data['anios'] = $db->table('tbl_conciliacion_bancaria')
            ->select('anio')->distinct()->orderBy('anio', 'DESC')
            ->get()->getResultArray();
        $data['anioActual'] = $anio;
        $data['mesActual']  = $mes;

        // ── INGRESOS, COSTOS FIJOS, VARIABLES, NEUTROS ──
        $builder = $db->table('tbl_conciliacion_bancaria cb')
            ->select("
                cc.tipo,
                SUM(cb.valor) as total_valor
            ")
            ->join('tbl_clasificacion_costos cc', 'cc.llave_item = cb.llave_item', 'left')
            ->where('cb.anio', $anio);

        if ($mes) {
            $builder->where('cb.mes_real', (int) $mes);
        }

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

        if ($mes) {
            $carteraBuilder->where('anio', $anio)->where('mes', (int) $mes);
        }
        // Si no hay filtro de mes, mostrar toda la cartera vigente
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
                'nombre'        => $cuenta['nombre_cuenta'],
                'saldo_inicial' => $saldoInicial,
                'movimientos'   => $totalMov,
                'saldo_actual'  => $saldoActual,
            ];

            $data['saldoTotalBancos'] += $saldoActual;
        }

        // ── POSICIÓN NETA ──
        $data['posicionNeta'] = $data['utilidadOperativa'] + $data['cartera'] + $data['saldoTotalBancos'] - $data['deudaSaldo'];

        // ── DESGLOSE POR CATEGORÍA ──
        $desglose = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cc.categoria, cc.tipo, SUM(cb.valor) as total_valor, COUNT(*) as movimientos')
            ->join('tbl_clasificacion_costos cc', 'cc.llave_item = cb.llave_item', 'left')
            ->where('cb.anio', $anio)
            ->where('cc.tipo !=', 'neutro');

        if ($mes) {
            $desglose->where('cb.mes_real', (int) $mes);
        }

        $data['desglose'] = $desglose->groupBy('cc.categoria, cc.tipo')
            ->orderBy('cc.tipo', 'ASC')
            ->orderBy('total_valor', 'ASC')
            ->get()->getResultArray();

        // ── EVOLUCIÓN MENSUAL (para gráfico) ──
        $evolucion = $db->table('tbl_conciliacion_bancaria cb')
            ->select("cb.mes_real, cc.tipo, SUM(cb.valor) as total_valor")
            ->join('tbl_clasificacion_costos cc', 'cc.llave_item = cb.llave_item', 'left')
            ->where('cb.anio', $anio)
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
}
