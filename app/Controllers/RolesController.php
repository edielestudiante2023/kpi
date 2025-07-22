<?php namespace App\Controllers;

use App\Models\RolesModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class RolesController extends BaseController
{
    /** @var RolesModel */
    protected $RolesModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->RolesModel = new RolesModel();
    }

    // Listar roles
    public function listRol()
    {
        $data['roles'] = $this->RolesModel->orderBy('nombre_rol', 'ASC')->findAll();
        return view('management/list_rol', $data);
    }

    // Formulario agregar rol
    public function addRol()
    {
        return view('management/add_rol');
    }

    // Procesar creación de rol
    public function addRolPost()
    {
        $rules = ['nombre_rol' => 'required|is_unique[roles.nombre_rol]'];
        if (! $this->validate($rules)) {
            return redirect()->back()
                             ->with('errors', $this->validator->getErrors())
                             ->withInput();
        }
        $this->RolesModel->insert(['nombre_rol' => $this->request->getPost('nombre_rol')]);
        return redirect()->to('/roles')->with('success', 'Rol creado.');
    }

    // Formulario editar rol
    public function editRol($id)
    {
        $rol = $this->RolesModel->find($id);
        if (! $rol) throw new PageNotFoundException("Rol con ID $id no existe");
        return view('management/edit_rol', ['rol' => $rol]);
    }

    // Procesar edición de rol
    public function editRolPost($id)
    {
        $rules = ['nombre_rol' => "required|is_unique[roles.nombre_rol,nombre_rol,{$id},id_roles]"];
        if (! $this->validate($rules)) {
            return redirect()->back()
                             ->with('errors', $this->validator->getErrors())
                             ->withInput();
        }
        $this->RolesModel->update($id, ['nombre_rol' => $this->request->getPost('nombre_rol')]);
        return redirect()->to('/roles')->with('success', 'Rol actualizado.');
    }

    // Eliminar rol
    public function deleteRol($id)
    {
        $this->RolesModel->delete($id);
        return redirect()->to('/roles')->with('success', 'Rol eliminado.');
    }
}
