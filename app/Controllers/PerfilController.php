<?php

namespace App\Controllers;

use App\Models\PerfilCargoModel;
use App\Models\AreaModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;

class PerfilController extends BaseController
{
    /** @var PerfilCargoModel */
    protected $perfilModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->perfilModel = new PerfilCargoModel();
    }

    // Listar perfiles
    public function listPerfil()
    {
        $data['perfiles'] = $this->perfilModel->orderBy('created_at', 'DESC')->findAll();
        return view('management/list_perfil', $data);
    }

    // Formulario crear perfil
    public function addPerfil()
    {
        $areaModel = new AreaModel();
        $userModel = new UserModel();

        $data['areas'] = $areaModel->where('estado_area', 'activa')->orderBy('nombre_area')->findAll();
        $data['jefes'] = $userModel->where('activo', 1)->orderBy('cargo')->findAll();

        return view('management/add_perfil', $data);
    }

    // Procesar creación perfil
    public function addPerfilPost()
    {
        $rules = [
            'nombre_cargo' => 'required',
            'area' => 'required',
            'jefe_inmediato' => 'required'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $this->perfilModel->insert($this->request->getPost());
        return redirect()->to('/perfiles')->with('success', 'Perfil creado.');
    }

    // Formulario editar perfil
    public function editPerfil($id)
    {
        $perfil = $this->perfilModel->find($id);
        if (! $perfil) {
            throw new PageNotFoundException("Perfil con ID $id no existe");
        }

        $areaModel = new AreaModel();
        $userModel = new UserModel();

        $data['perfil'] = $perfil;
        $data['areas'] = $areaModel->where('estado_area', 'activa')->orderBy('nombre_area')->findAll();
        $data['jefes'] = $userModel->where('activo', 1)->orderBy('cargo')->findAll();

        return view('management/edit_perfil', $data);
    }

    // Procesar edición perfil
    public function editPerfilPost($id)
    {
        $rules = [
            'nombre_cargo' => 'required',
            'area' => 'required',
            'jefe_inmediato' => 'required'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $this->perfilModel->update($id, $this->request->getPost());
        return redirect()->to('/perfiles')->with('success', 'Perfil actualizado.');
    }

    // Eliminar perfil
    public function deletePerfil($id)
    {
        $this->perfilModel->delete($id);
        return redirect()->to('/perfiles')->with('success', 'Perfil eliminado.');
    }
}
