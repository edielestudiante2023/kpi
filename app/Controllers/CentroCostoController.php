<?php

namespace App\Controllers;

use App\Models\CentroCostoConciliacionModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class CentroCostoController extends BaseController
{
    protected $centroCostoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->centroCostoModel = new CentroCostoConciliacionModel();
    }

    public function listCentroCosto()
    {
        $data['centros'] = $this->centroCostoModel->orderBy('centro_costo', 'ASC')->findAll();
        return view('conciliaciones/list_centro_costo', $data);
    }

    public function addCentroCosto()
    {
        return view('conciliaciones/add_centro_costo');
    }

    public function addCentroCostoPost()
    {
        $rules = [
            'centro_costo' => 'required|is_unique[tbl_centros_costo.centro_costo]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }
        $this->centroCostoModel->insert($this->request->getPost());
        return redirect()->to('/conciliaciones/centros-costo')->with('success', 'Centro de costo creado.');
    }

    public function editCentroCosto($id)
    {
        $centro = $this->centroCostoModel->find($id);
        if (! $centro) {
            throw new PageNotFoundException("Centro de costo con ID $id no existe");
        }
        return view('conciliaciones/edit_centro_costo', ['centro' => $centro]);
    }

    public function editCentroCostoPost($id)
    {
        $rules = [
            'centro_costo' => "required|is_unique[tbl_centros_costo.centro_costo,id_centro_costo,{$id}]",
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }
        $this->centroCostoModel->update($id, $this->request->getPost());
        return redirect()->to('/conciliaciones/centros-costo')->with('success', 'Centro de costo actualizado.');
    }

    public function deleteCentroCosto($id)
    {
        $this->centroCostoModel->delete($id);
        return redirect()->to('/conciliaciones/centros-costo')->with('success', 'Centro de costo eliminado.');
    }
}
