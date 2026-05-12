<?php

namespace App\Controllers;

use App\Models\BalanceSnapshotModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BalanceController extends BaseController
{
    protected $snapshotModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->snapshotModel = new BalanceSnapshotModel();
    }

    /**
     * Vista principal del balance: dinámico o snapshot según fecha de corte.
     */
    public function index()
    {
        $db = \Config\Database::connect();

        // Fecha de corte: por defecto último día del mes corriente
        $corteParam = $this->request->getGet('corte');
        $corte = $corteParam ?: date('Y-m-t');

        $snapshot = $this->snapshotModel->where('fecha_corte', $corte)->first();

        if ($snapshot) {
            $data = $this->expandirSnapshot($snapshot);
            $data['modo'] = 'snapshot';
        } else {
            $data = $this->calcularBalance($corte);
            $data['modo'] = 'dinamico';
        }

        $data['corte']      = $corte;
        $data['snapshots']  = $this->snapshotModel
            ->select('id_snapshot, fecha_corte, estado_empresa, created_at')
            ->orderBy('fecha_corte', 'DESC')->findAll(12);

        return view('conciliaciones/balance', $data);
    }

    /**
     * POST: Cierra el mes guardando snapshot inmutable.
     */
    public function cerrarMes()
    {
        $corte = $this->request->getPost('corte') ?: date('Y-m-t');

        $existe = $this->snapshotModel->where('fecha_corte', $corte)->first();
        if ($existe) {
            return redirect()->to("/conciliaciones/balance?corte={$corte}")
                ->with('errors', ["Ya existe un snapshot al {$corte}. Elimínalo antes de recerrar."]);
        }

        $balance = $this->calcularBalance($corte);

        $session = session();
        $usuario = $session->get('nombre') ?? $session->get('email') ?? 'sistema';

        $this->snapshotModel->insert([
            'fecha_corte'      => $corte,
            'cartera_sst'      => $balance['cartera_sst'],
            'cartera_rps'      => $balance['cartera_rps'],
            'saldo_banco_sst'  => $balance['saldo_banco_sst'],
            'saldo_banco_rps'  => $balance['saldo_banco_rps'],
            'total_activos'    => $balance['total_activos'],
            'total_pasivos'    => $balance['total_pasivos'],
            'estado_empresa'   => $balance['estado_empresa'],
            'detalle_pasivos'  => json_encode($balance['pasivos'], JSON_UNESCAPED_UNICODE),
            'creado_por'       => $usuario,
            'notas'            => $this->request->getPost('notas') ?: null,
        ]);

        return redirect()->to("/conciliaciones/balance?corte={$corte}")
            ->with('success', "Snapshot del {$corte} guardado. El balance quedó congelado.");
    }

    /**
     * Listado de snapshots históricos
     */
    public function historico()
    {
        $data['snapshots'] = $this->snapshotModel
            ->orderBy('fecha_corte', 'DESC')->findAll();
        return view('conciliaciones/balance_historico', $data);
    }

    /**
     * Eliminar snapshot (permite recerrar el mes)
     */
    public function eliminarSnapshot($id)
    {
        $snap = $this->snapshotModel->find((int)$id);
        if ($snap) {
            $this->snapshotModel->delete((int)$id);
            return redirect()->to('/conciliaciones/balance/historico')
                ->with('success', "Snapshot del {$snap['fecha_corte']} eliminado.");
        }
        return redirect()->to('/conciliaciones/balance/historico')
            ->with('errors', ['Snapshot no encontrado.']);
    }

    /**
     * Calcula el balance dinámico al corte (sin guardar).
     * Retorna array con cartera, bancos, deudas, totales.
     */
    private function calcularBalance(string $corte): array
    {
        $db = \Config\Database::connect();

        // ── ACTIVOS ──
        // 1) Cartera por portafolio: facturas elaboradas <= corte y no pagadas a esa fecha.
        //    Considera: una factura es "no pagada al corte" si pagado=0 OR fecha_pago > corte
        $carteraSST = $this->cartera($db, 'SST', $corte);
        $carteraRPS = $this->cartera($db, 'RPS', $corte);

        // 2) Saldo bancos: SUM(valor) hasta corte por cuenta
        //    Los EGRESOS ya están almacenados con signo negativo en tbl_conciliacion_bancaria.
        $saldoSST = $this->saldoBanco($db, 'SST', $corte);
        $saldoRPS = $this->saldoBanco($db, 'RPS', $corte);

        $totalActivos = $carteraSST + $carteraRPS + $saldoSST + $saldoRPS;

        // ── PASIVOS ──
        // Lista de deudas activas: registradas <= corte y no saldadas
        // Saldo deuda = monto_original - SUM(abonos con fecha <= corte)
        $deudas = $db->table('tbl_deudas')
            ->select('id_deuda, concepto, acreedor, monto_original, fecha_registro, fecha_vencimiento, estado')
            ->where('fecha_registro <=', $corte)
            ->orderBy('concepto', 'ASC')
            ->get()->getResultArray();

        $pasivos = [];
        $totalPasivos = 0;
        foreach ($deudas as $d) {
            $abonado = (float) $db->table('tbl_deuda_abonos')
                ->selectSum('valor_abono')
                ->where('id_deuda', $d['id_deuda'])
                ->where('fecha_abono <=', $corte)
                ->get()->getRow()->valor_abono ?? 0;

            $saldo = (float) $d['monto_original'] - $abonado;
            if ($saldo <= 0) continue; // ya saldada al corte

            $pasivos[] = [
                'id_deuda'        => (int) $d['id_deuda'],
                'concepto'        => $d['concepto'],
                'acreedor'        => $d['acreedor'],
                'monto_original'  => (float) $d['monto_original'],
                'abonado'         => $abonado,
                'saldo_al_corte'  => $saldo,
            ];
            $totalPasivos += $saldo;
        }

        return [
            'cartera_sst'      => $carteraSST,
            'cartera_rps'      => $carteraRPS,
            'saldo_banco_sst'  => $saldoSST,
            'saldo_banco_rps'  => $saldoRPS,
            'total_activos'    => $totalActivos,
            'pasivos'          => $pasivos,
            'total_pasivos'    => $totalPasivos,
            'estado_empresa'   => $totalActivos - $totalPasivos,
        ];
    }

    /**
     * Cartera al corte de un portafolio por nombre (SST/RPS).
     */
    private function cartera($db, string $portafolio, string $corte): float
    {
        $port = $db->table('tbl_portafolios')
            ->where('portafolio', $portafolio)->get()->getRow();
        if (! $port) return 0.0;

        $row = $db->table('tbl_facturacion')
            ->select('SUM(base_gravada - COALESCE(valor_pagado,0) - COALESCE(anticipo,0)) as saldo')
            ->where('id_portafolio', $port->id_portafolio)
            ->where('fecha_elaboracion <=', $corte)
            ->groupStart()
                ->where('pagado', 0)
                ->orWhere('fecha_pago >', $corte)
            ->groupEnd()
            ->get()->getRow();

        return max(0, (float) ($row->saldo ?? 0));
    }

    /**
     * Saldo banco al corte (cuenta SST o RPS).
     * INGRESOS suman positivo, EGRESOS suman negativo (signo ya almacenado).
     */
    private function saldoBanco($db, string $nombreCuenta, string $corte): float
    {
        $cu = $db->table('tbl_cuentas_banco')
            ->where('nombre_cuenta', $nombreCuenta)->get()->getRow();
        if (! $cu) return 0.0;

        $row = $db->table('tbl_conciliacion_bancaria')
            ->selectSum('valor')
            ->where('id_cuenta_banco', $cu->id_cuenta_banco)
            ->where('fecha_sistema <=', $corte)
            ->get()->getRow();

        $saldoInicial = (float) ($cu->saldo_inicial ?? 0);
        return $saldoInicial + (float) ($row->valor ?? 0);
    }

    /**
     * Expande un snapshot guardado al formato esperado por la vista.
     */
    private function expandirSnapshot(array $snap): array
    {
        return [
            'cartera_sst'      => (float) $snap['cartera_sst'],
            'cartera_rps'      => (float) $snap['cartera_rps'],
            'saldo_banco_sst'  => (float) $snap['saldo_banco_sst'],
            'saldo_banco_rps'  => (float) $snap['saldo_banco_rps'],
            'total_activos'    => (float) $snap['total_activos'],
            'pasivos'          => json_decode($snap['detalle_pasivos'] ?? '[]', true) ?: [],
            'total_pasivos'    => (float) $snap['total_pasivos'],
            'estado_empresa'   => (float) $snap['estado_empresa'],
            'snapshot_meta'    => [
                'id'         => (int) $snap['id_snapshot'],
                'creado_por' => $snap['creado_por'],
                'created_at' => $snap['created_at'],
                'notas'      => $snap['notas'],
            ],
        ];
    }
}
