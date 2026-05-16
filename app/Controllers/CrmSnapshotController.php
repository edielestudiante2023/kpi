<?php

namespace App\Controllers;

use App\Models\CrmSnapshotSemanalModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CrmSnapshotController extends BaseController
{
    protected $snapshotModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'crm']);
        $this->snapshotModel = new CrmSnapshotSemanalModel();
    }

    private function chequearAcceso()
    {
        if (!crm_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo CRM.');
        }
        return null;
    }

    /**
     * Lista de snapshots + botón generar.
     */
    public function index()
    {
        if ($r = $this->chequearAcceso()) return $r;
        return view('crm/snapshots', [
            'snapshots'    => $this->snapshotModel->getHistorial(100),
            'masReciente'  => $this->snapshotModel->getMasReciente(),
        ]);
    }

    /**
     * Generar un nuevo snapshot del estado actual del pipeline.
     */
    public function generar()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $notas = trim((string) $this->request->getPost('notas')) ?: null;
        $id = $this->snapshotModel->generar((int) session()->get('id_users'), $notas);
        return redirect()->to('/crm/snapshots/ver/' . $id)
            ->with('success', "Snapshot #{$id} generado correctamente.");
    }

    /**
     * Ver detalle de un snapshot (KPIs + breakdown + comparación con anterior).
     */
    public function ver($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $snap = $this->snapshotModel->getConDetalle($id);
        if (!$snap) {
            return redirect()->to('/crm/snapshots')->with('errors', ['Snapshot no encontrado.']);
        }
        // Anterior cronológico para comparar
        $anterior = $this->snapshotModel
            ->where('fecha_corte <', $snap['fecha_corte'])
            ->orderBy('fecha_corte', 'DESC')->first();
        if ($anterior) {
            $anterior = $this->snapshotModel->getConDetalle((int) $anterior['id_snapshot']);
        }

        return view('crm/snapshot_detalle', [
            'snap'     => $snap,
            'anterior' => $anterior,
        ]);
    }

    /**
     * Eliminar un snapshot (solo admin CRM).
     */
    public function eliminar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        if (!crm_es_admin()) {
            return redirect()->to('/crm/snapshots')->with('errors', ['Solo admins CRM pueden eliminar snapshots.']);
        }
        $this->snapshotModel->delete((int) $id);
        return redirect()->to('/crm/snapshots')->with('success', 'Snapshot eliminado.');
    }
}
