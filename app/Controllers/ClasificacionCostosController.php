<?php

namespace App\Controllers;

use App\Models\ClasificacionCostosModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class ClasificacionCostosController extends BaseController
{
    protected $clasificacionModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->clasificacionModel = new ClasificacionCostosModel();
    }

    public function listClasificacion()
    {
        $db = \Config\Database::connect();

        // Clasificaciones con conteo de movimientos
        $data['clasificaciones'] = $db->table('tbl_clasificacion_costos cc')
            ->select('cc.*, COUNT(cb.id_conciliacion) as total_movimientos')
            ->join('tbl_conciliacion_bancaria cb', 'cb.llave_item = cc.llave_item', 'left')
            ->groupBy('cc.id_clasificacion')
            ->orderBy('cc.categoria', 'ASC')
            ->orderBy('cc.llave_item', 'ASC')
            ->get()->getResultArray();

        // Items sin clasificar
        $data['sinClasificar'] = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cb.llave_item, COUNT(*) as total')
            ->join('tbl_clasificacion_costos cc', 'cc.llave_item = cb.llave_item', 'left')
            ->where('cc.id_clasificacion IS NULL')
            ->groupBy('cb.llave_item')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        return view('conciliaciones/list_clasificacion', $data);
    }

    public function addClasificacion()
    {
        $db = \Config\Database::connect();
        // Categorías existentes para sugerir
        $data['categorias'] = $db->table('tbl_clasificacion_costos')
            ->select('categoria')->distinct()->orderBy('categoria', 'ASC')
            ->get()->getResultArray();
        // Items sin clasificar
        $data['sinClasificar'] = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cb.llave_item, COUNT(*) as total')
            ->join('tbl_clasificacion_costos cc', 'cc.llave_item = cb.llave_item', 'left')
            ->where('cc.id_clasificacion IS NULL')
            ->groupBy('cb.llave_item')
            ->orderBy('cb.llave_item', 'ASC')
            ->get()->getResultArray();

        return view('conciliaciones/add_clasificacion', $data);
    }

    public function addClasificacionPost()
    {
        $rules = [
            'llave_item' => 'required|is_unique[tbl_clasificacion_costos.llave_item]',
            'categoria'  => 'required',
            'tipo'       => 'required|in_list[fijo,variable,ingreso,neutro]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }
        $this->clasificacionModel->insert($this->request->getPost());
        return redirect()->to('/conciliaciones/clasificacion')->with('success', 'Clasificación creada.');
    }

    public function editClasificacion($id)
    {
        $clasif = $this->clasificacionModel->find($id);
        if (! $clasif) throw new PageNotFoundException("Clasificación con ID $id no existe");

        $db = \Config\Database::connect();
        $data['clasif'] = $clasif;
        $data['categorias'] = $db->table('tbl_clasificacion_costos')
            ->select('categoria')->distinct()->orderBy('categoria', 'ASC')
            ->get()->getResultArray();

        return view('conciliaciones/edit_clasificacion', $data);
    }

    public function editClasificacionPost($id)
    {
        $rules = [
            'llave_item' => "required|is_unique[tbl_clasificacion_costos.llave_item,id_clasificacion,{$id}]",
            'categoria'  => 'required',
            'tipo'       => 'required|in_list[fijo,variable,ingreso,neutro]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }
        $this->clasificacionModel->update($id, $this->request->getPost());
        return redirect()->to('/conciliaciones/clasificacion')->with('success', 'Clasificación actualizada.');
    }

    public function deleteClasificacion($id)
    {
        $this->clasificacionModel->delete($id);
        return redirect()->to('/conciliaciones/clasificacion')->with('success', 'Clasificación eliminada.');
    }
}
