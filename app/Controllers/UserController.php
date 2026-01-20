<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RolesModel;
use App\Models\AreaModel;
use App\Models\PerfilCargoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UserController extends BaseController
{
    protected $userModel;
    protected $RolesModel;
    protected $areaModel;
    protected $perfilModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['form', 'url']);
        $this->userModel   = new UserModel();
        $this->RolesModel   = new RolesModel();
        $this->areaModel   = new AreaModel();
        $this->perfilModel = new PerfilCargoModel();
    }

    public function listUser()
    {
        $data['users'] = $this->userModel
            ->select('users.*, 
              roles.nombre_rol as rol_nombre, 
              areas.nombre_area as area_nombre, 
              perfiles_cargo.nombre_cargo as perfil_nombre,
              jefe.nombre_completo as nombre_jefe')
            ->join('roles', 'roles.id_roles = users.id_roles')
            ->join('areas', 'areas.id_areas = users.id_areas', 'left')
            ->join('perfiles_cargo', 'perfiles_cargo.id_perfil_cargo = users.id_perfil_cargo', 'left')
            ->join('users as jefe', 'jefe.id_users = users.id_jefe', 'left')
            ->findAll();

        $data['total_usuarios'] = $this->userModel->countAllResults();
        $data['total_roles']    = (new \App\Models\RolesModel())->countAllResults();
        $data['total_areas']    = (new \App\Models\AreaModel())->countAllResults();

        return view('management/list_user', $data);
    }

    public function addUser()
    {
        $data = [
            'roles'          => $this->RolesModel->findAll(),
            'areas'          => $this->areaModel->findAll(),
            'perfiles_cargo' => $this->perfilModel->orderBy('nombre_cargo', 'ASC')->findAll(),
            'jefes'          => $this->userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll()
        ];
        return view('management/add_user', $data);
    }

    public function addUserPost()
    {
        $post = $this->request->getPost();

        // Encriptar contraseña
        $post['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
        $post['primer_login'] = 1;

        // Guardar el usuario
        if (! $this->userModel->insert($post)) {
            return redirect()->back()
                ->with('errors', $this->userModel->errors())
                ->withInput();
        }

        // ✅ Obtener el ID del nuevo usuario
        $nuevoId = $this->userModel->getInsertID();

        // ✅ Redirigir a la nueva vista para completar los datos
        return redirect()->to("/users/completar/$nuevoId")
            ->with('success', 'Usuario creado. Ahora puedes asignar perfil de cargo y jefe inmediato.');
    }



    public function editUser($id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Usuario con ID $id no existe");
        }
        $data = [
            'user'           => $user,
            'roles'          => $this->RolesModel->findAll(),
            'areas'          => $this->areaModel->findAll(),
            'perfiles_cargo' => $this->perfilModel->orderBy('nombre_cargo', 'ASC')->findAll(),
            'jefes'          => $this->userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll()
        ];
        return view('management/edit_user', $data);
    }

    public function editUserPost($id)
    {
        $post = $this->request->getPost();

        /* dd($post); */
        if (empty($post['password'])) {
            unset($post['password']);
        } else {
            $post['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
        }

        // Sincronizar cargo con el nombre del perfil seleccionado
        if (!empty($post['id_perfil_cargo'])) {
            $perfil = $this->perfilModel->find($post['id_perfil_cargo']);
            if ($perfil) {
                $post['cargo'] = $perfil['nombre_cargo'];
            }
        }

        if (! $this->userModel->update($id, $post)) {
            return redirect()->back()
                ->with('errors', $this->userModel->errors())
                ->withInput();
        }


        return redirect()->to('/users')->with('success', 'Usuario actualizado.');
    }

    public function deleteUser($id)
    {
        $this->userModel->delete($id);
        return redirect()->to('/users')->with('success', 'Usuario eliminado.');
    }

    public function completarUsuario($id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Usuario con ID $id no existe");
        }

        $data = [
            'user'           => $user,
            'perfiles_cargo' => $this->perfilModel->orderBy('nombre_cargo', 'ASC')->findAll(),
            'jefes'          => $this->userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll()
        ];
        return view('management/completar_usuario', $data);
    }

    public function completarUsuarioPost($id)
    {
        $post = $this->request->getPost();

        // Validaciones mínimas (puedes expandir si lo deseas)
        if (empty($post['id_perfil_cargo'])) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['Debes seleccionar un perfil de cargo.']);
        }

        // Obtener nombre del perfil para sincronizar cargo
        $perfil = $this->perfilModel->find($post['id_perfil_cargo']);

        // Construimos el arreglo de actualización
        $datos = [
            'id_perfil_cargo' => $post['id_perfil_cargo'],
            'id_jefe'         => $post['id_jefe'] ?? null,
            'cargo'           => $perfil ? $perfil['nombre_cargo'] : null
        ];

        // Ejecutamos el update
        if (! $this->userModel->update($id, $datos)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->userModel->errors());
        }

        return redirect()->to('/users')->with('success', 'Datos completados exitosamente para el usuario.');
    }
}
