<?php namespace App\Controllers;

use App\Models\EquipoModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class EquipoController extends BaseController
{
    /** @var EquipoModel */
    protected $equipoModel;
    /** @var UserModel */
    protected $userModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->equipoModel = new EquipoModel();
        $this->userModel   = new UserModel();
    }

    // Listar equipos
    public function listEquipo()
    {
        $data['equipos'] = $this->equipoModel
            ->select('equipos.*, uj.nombre_completo as jefe_nombre, us.nombre_completo as sub_nombre')
            ->join('users as uj', 'uj.id_users = equipos.id_jefe')
            ->join('users as us', 'us.id_users = equipos.id_subordinado')
            ->findAll();
        return view('management/list_equipo', $data);
    }

    // Formulario crear equipo
    public function addEquipo()
    {
        $data['users'] = $this->userModel->where('activo', 1)->findAll();
        return view('management/add_equipo', $data);
    }

    // Procesar creación
    public function addEquipopost()
    {
        $post = $this->request->getPost();
        $post['fecha_asignacion'] = date('Y-m-d H:i:s');
        $post['estado_relacion']  = 1;
        if (! $this->equipoModel->insert($post)) {
            return redirect()->back()
                             ->with('errors', $this->equipoModel->errors())
                             ->withInput();
        }
        return redirect()->to('/equipos')->with('success', 'Equipo asignado.');
    }

    // Formulario editar equipo
    public function editEquipo($id)
    {
        $equipo = $this->equipoModel->find($id);
        if (! $equipo) {
            throw new PageNotFoundException("Equipo con ID $id no existe");
        }
        $data = [
            'equipo' => $equipo,
            'users'  => $this->userModel->where('activo', 1)->findAll()
        ];
        return view('management/edit_equipo', $data);
    }

    // Procesar edición
    public function editEquipopost($id)
    {
        $post = $this->request->getPost();
        if (! $this->equipoModel->update($id, $post)) {
            return redirect()->back()
                             ->with('errors', $this->equipoModel->errors())
                             ->withInput();
        }
        return redirect()->to('/equipos')->with('success', 'Equipo actualizado.');
    }

    // Eliminar equipo
    public function deleteEquipo($id)
    {
        $this->equipoModel->delete($id);
        return redirect()->to('/equipos')->with('success', 'Equipo eliminado.');
    }
}

