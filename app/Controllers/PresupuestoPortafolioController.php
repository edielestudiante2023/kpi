<?php

namespace App\Controllers;

use App\Models\PortafolioModel;
use App\Models\PresupuestoPortafolioModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class PresupuestoPortafolioController extends BaseController
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
     * Listado matriz: filas = anio×portafolio, columnas = meses
     */
    public function listPresupuesto()
    {
        $db = \Config\Database::connect();

        $anioFiltro = $this->request->getGet('anio') ?: date('Y');

        $aniosRows = $db->table('tbl_presupuesto_portafolio')
            ->select('anio')->distinct()->orderBy('anio', 'DESC')
            ->get()->getResultArray();
        $data['anios']      = array_column($aniosRows, 'anio') ?: [date('Y')];
        $data['anioActual'] = (int) $anioFiltro;

        $rows = $db->table('tbl_presupuesto_portafolio pp')
            ->select('pp.id_presupuesto, pp.id_portafolio, p.portafolio, pp.anio, pp.mes, pp.presupuesto')
            ->join('tbl_portafolios p', 'p.id_portafolio = pp.id_portafolio', 'left')
            ->where('pp.anio', (int) $anioFiltro)
            ->orderBy('p.portafolio, pp.mes', 'ASC')
            ->get()->getResultArray();

        // Pivotear: matriz[portafolio][mes] = ['id' => x, 'valor' => y]
        $matriz = [];
        foreach ($rows as $r) {
            $matriz[$r['portafolio']][(int) $r['mes']] = [
                'id'    => (int) $r['id_presupuesto'],
                'valor' => (float) $r['presupuesto'],
            ];
        }
        $data['matriz']      = $matriz;
        $data['portafolios'] = $this->portafolioModel->orderBy('portafolio', 'ASC')->findAll();

        return view('conciliaciones/list_presupuesto_portafolio', $data);
    }

    /**
     * Inicializar 12 meses de un portafolio+año con un valor base.
     * POST: id_portafolio, anio, presupuesto_base
     */
    public function inicializarAnio()
    {
        $idPortafolio = (int) $this->request->getPost('id_portafolio');
        $anio         = (int) $this->request->getPost('anio');
        $base         = (float) $this->request->getPost('presupuesto_base');

        if (! $idPortafolio || ! $anio) {
            return redirect()->back()->with('errors', ['Faltan datos: portafolio y/o año.']);
        }

        $db = \Config\Database::connect();
        $existentes = $db->table('tbl_presupuesto_portafolio')
            ->where('id_portafolio', $idPortafolio)
            ->where('anio', $anio)
            ->countAllResults();

        if ($existentes > 0) {
            return redirect()->back()->with('errors', ["Ya existen presupuestos para este portafolio en {$anio}."]);
        }

        $lote = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $lote[] = [
                'id_portafolio' => $idPortafolio,
                'anio'          => $anio,
                'mes'           => $mes,
                'presupuesto'   => $base,
            ];
        }
        $this->presupuestoModel->insertBatch($lote);

        return redirect()->to("/conciliaciones/presupuestos?anio={$anio}")
            ->with('success', "Año {$anio} inicializado con 12 meses a $" . number_format($base, 0, ',', '.'));
    }

    /**
     * Actualizar un mes vía AJAX
     * POST: id_presupuesto, presupuesto
     */
    public function actualizarMes()
    {
        $id    = (int) $this->request->getPost('id_presupuesto');
        $valor = (float) $this->request->getPost('presupuesto');

        if (! $id) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Id inválido.']);
        }
        $this->presupuestoModel->update($id, ['presupuesto' => $valor]);
        return $this->response->setJSON(['ok' => true, 'valor' => $valor]);
    }

    /**
     * Aplicar el mismo valor a todos los meses de un portafolio+año
     */
    public function aplicarATodos()
    {
        $idPortafolio = (int) $this->request->getPost('id_portafolio');
        $anio         = (int) $this->request->getPost('anio');
        $valor        = (float) $this->request->getPost('presupuesto');

        if (! $idPortafolio || ! $anio) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Faltan datos.']);
        }

        $db = \Config\Database::connect();
        $db->table('tbl_presupuesto_portafolio')
            ->where('id_portafolio', $idPortafolio)
            ->where('anio', $anio)
            ->update(['presupuesto' => $valor]);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Eliminar todos los presupuestos de un portafolio+año
     */
    public function eliminarAnio($idPortafolio, $anio)
    {
        $db = \Config\Database::connect();
        $db->table('tbl_presupuesto_portafolio')
            ->where('id_portafolio', (int) $idPortafolio)
            ->where('anio', (int) $anio)
            ->delete();
        return redirect()->to("/conciliaciones/presupuestos?anio={$anio}")
            ->with('success', 'Año eliminado para ese portafolio.');
    }
}
