<?php

namespace App\Controllers;

use App\Models\DeudaModel;
use App\Models\DeudaAbonoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class DeudaController extends BaseController
{
    protected $deudaModel;
    protected $abonoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->deudaModel = new DeudaModel();
        $this->abonoModel = new DeudaAbonoModel();
    }

    // ── Listado de deudas ──
    public function listDeudas()
    {
        $db = \Config\Database::connect();

        $deudas = $db->table('tbl_deudas d')
            ->select('d.*, IFNULL(SUM(a.valor_abono), 0) as total_abonado')
            ->join('tbl_deuda_abonos a', 'a.id_deuda = d.id_deuda', 'left')
            ->groupBy('d.id_deuda')
            ->orderBy('d.estado', 'ASC')
            ->orderBy('d.fecha_vencimiento', 'ASC')
            ->get()->getResultArray();

        // Calcular saldo pendiente
        foreach ($deudas as &$d) {
            $d['saldo_pendiente'] = (float)$d['monto_original'] - (float)$d['total_abonado'];
        }

        $data['deudas'] = $deudas;

        // Totales
        $activas = array_filter($deudas, fn($d) => $d['estado'] === 'activa');
        $data['totalDeuda']   = array_sum(array_column($activas, 'monto_original'));
        $data['totalAbonado'] = array_sum(array_column($activas, 'total_abonado'));
        $data['totalSaldo']   = array_sum(array_column($activas, 'saldo_pendiente'));

        return view('conciliaciones/list_deudas', $data);
    }

    // ── Crear deuda ──
    public function addDeuda()
    {
        return view('conciliaciones/add_deuda');
    }

    public function addDeudaPost()
    {
        $rules = [
            'concepto'        => 'required',
            'acreedor'        => 'required',
            'monto_original'  => 'required|decimal',
            'fecha_registro'  => 'required|valid_date',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $this->deudaModel->insert([
            'concepto'          => $this->request->getPost('concepto'),
            'acreedor'          => $this->request->getPost('acreedor'),
            'monto_original'    => $this->request->getPost('monto_original'),
            'fecha_registro'    => $this->request->getPost('fecha_registro'),
            'fecha_vencimiento' => $this->request->getPost('fecha_vencimiento') ?: null,
            'notas'             => $this->request->getPost('notas') ?: null,
        ]);

        return redirect()->to('/conciliaciones/deudas')->with('success', 'Deuda registrada.');
    }

    // ── Editar deuda ──
    public function editDeuda($id)
    {
        $deuda = $this->deudaModel->find($id);
        if (! $deuda) throw new PageNotFoundException("Deuda con ID $id no existe");
        return view('conciliaciones/edit_deuda', ['deuda' => $deuda]);
    }

    public function editDeudaPost($id)
    {
        $rules = [
            'concepto'        => 'required',
            'acreedor'        => 'required',
            'monto_original'  => 'required|decimal',
            'fecha_registro'  => 'required|valid_date',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $this->deudaModel->update($id, [
            'concepto'          => $this->request->getPost('concepto'),
            'acreedor'          => $this->request->getPost('acreedor'),
            'monto_original'    => $this->request->getPost('monto_original'),
            'fecha_registro'    => $this->request->getPost('fecha_registro'),
            'fecha_vencimiento' => $this->request->getPost('fecha_vencimiento') ?: null,
            'notas'             => $this->request->getPost('notas') ?: null,
        ]);

        return redirect()->to('/conciliaciones/deudas')->with('success', 'Deuda actualizada.');
    }

    // ── Eliminar deuda ──
    public function deleteDeuda($id)
    {
        $this->deudaModel->delete($id); // CASCADE borra abonos
        return redirect()->to('/conciliaciones/deudas')->with('success', 'Deuda eliminada.');
    }

    // ── Ver detalle + abonos ──
    public function viewDeuda($id)
    {
        $deuda = $this->deudaModel->find($id);
        if (! $deuda) throw new PageNotFoundException("Deuda con ID $id no existe");

        $abonos = $this->abonoModel
            ->where('id_deuda', $id)
            ->orderBy('fecha_abono', 'DESC')
            ->findAll();

        $totalAbonado = array_sum(array_column($abonos, 'valor_abono'));

        $data['deuda']         = $deuda;
        $data['abonos']        = $abonos;
        $data['totalAbonado']  = $totalAbonado;
        $data['saldoPendiente'] = (float)$deuda['monto_original'] - $totalAbonado;

        return view('conciliaciones/view_deuda', $data);
    }

    // ── Agregar abono ──
    public function addAbonoPost($idDeuda)
    {
        $deuda = $this->deudaModel->find($idDeuda);
        if (! $deuda) throw new PageNotFoundException("Deuda con ID $idDeuda no existe");

        $rules = [
            'fecha_abono' => 'required|valid_date',
            'valor_abono' => 'required|decimal',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $this->abonoModel->insert([
            'id_deuda'    => $idDeuda,
            'fecha_abono' => $this->request->getPost('fecha_abono'),
            'valor_abono' => $this->request->getPost('valor_abono'),
            'referencia'  => $this->request->getPost('referencia') ?: null,
        ]);

        // Verificar si queda saldada
        $totalAbonado = $this->abonoModel
            ->where('id_deuda', $idDeuda)
            ->selectSum('valor_abono')
            ->first()['valor_abono'] ?? 0;

        if ((float)$totalAbonado >= (float)$deuda['monto_original']) {
            $this->deudaModel->update($idDeuda, ['estado' => 'saldada']);
        }

        return redirect()->to("/conciliaciones/deudas/ver/{$idDeuda}")->with('success', 'Abono registrado.');
    }

    // ── Eliminar abono ──
    public function deleteAbono($idDeuda, $idAbono)
    {
        $this->abonoModel->delete($idAbono);

        // Recalcular estado
        $deuda = $this->deudaModel->find($idDeuda);
        $totalAbonado = $this->abonoModel
            ->where('id_deuda', $idDeuda)
            ->selectSum('valor_abono')
            ->first()['valor_abono'] ?? 0;

        $nuevoEstado = ((float)$totalAbonado >= (float)$deuda['monto_original']) ? 'saldada' : 'activa';
        $this->deudaModel->update($idDeuda, ['estado' => $nuevoEstado]);

        return redirect()->to("/conciliaciones/deudas/ver/{$idDeuda}")->with('success', 'Abono eliminado.');
    }
}
