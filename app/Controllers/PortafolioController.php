<?php

namespace App\Controllers;

use App\Models\PortafolioModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class PortafolioController extends BaseController
{
    /** @var PortafolioModel */
    protected $portafolioModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->portafolioModel = new PortafolioModel();
    }

    // Listar portafolios
    public function listPortafolio()
    {
        $data['portafolios'] = $this->portafolioModel->orderBy('portafolio', 'ASC')->findAll();
        return view('conciliaciones/list_portafolio', $data);
    }

    // Formulario crear
    public function addPortafolio()
    {
        return view('conciliaciones/add_portafolio');
    }

    // Procesar creación
    public function addPortafolioPost()
    {
        $rules = [
            'portafolio' => 'required|is_unique[tbl_portafolios.portafolio]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }
        $this->portafolioModel->insert($this->request->getPost());
        return redirect()->to('/conciliaciones/portafolios')->with('success', 'Portafolio creado.');
    }

    // Formulario editar
    public function editPortafolio($id)
    {
        $portafolio = $this->portafolioModel->find($id);
        if (! $portafolio) {
            throw new PageNotFoundException("Portafolio con ID $id no existe");
        }
        return view('conciliaciones/edit_portafolio', ['portafolio' => $portafolio]);
    }

    // Procesar edición
    public function editPortafolioPost($id)
    {
        $rules = [
            'portafolio' => "required|is_unique[tbl_portafolios.portafolio,id_portafolio,{$id}]",
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }
        $this->portafolioModel->update($id, $this->request->getPost());
        return redirect()->to('/conciliaciones/portafolios')->with('success', 'Portafolio actualizado.');
    }

    // Eliminar
    public function deletePortafolio($id)
    {
        $this->portafolioModel->delete($id);
        return redirect()->to('/conciliaciones/portafolios')->with('success', 'Portafolio eliminado.');
    }
}
