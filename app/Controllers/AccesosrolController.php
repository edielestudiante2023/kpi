<?php

namespace App\Controllers;

use App\Models\AccesosrolModel;
use App\Models\RolesModel;
use CodeIgniter\Controller;

class AccesosrolController extends Controller
{
    public function listAccesosrol()
    {
        $model = new AccesosrolModel();
        $data['accesos'] = $model->getAccesosConRol();
        return view('management/list_accesosrol', $data);
    }

    public function addAccesosrol()
    {
        $rolesModel = new RolesModel();
        $data['roles'] = $rolesModel->findAll();
        return view('management/add_accesosrol', $data);
    }

    public function addAccesosrolpost()
    {
        $model = new AccesosrolModel();
        $model->save($this->request->getPost());
        return redirect()->to('/accesosrol')->with('success', 'Acceso creado correctamente.');
    }

    public function editAccesosrol($id)
    {
        $model = new AccesosrolModel();
        $rolesModel = new RolesModel();
        $data['acceso'] = $model->find($id);
        $data['roles'] = $rolesModel->findAll();
        return view('management/edit_accesosrol', $data);
    }

    public function editAccesosrolpost($id)
    {
        $model = new AccesosrolModel();
        $model->update($id, $this->request->getPost());
        return redirect()->to('/accesosrol')->with('success', 'Acceso actualizado correctamente.');
    }

    public function deleteAccesosrol($id)
    {
        $model = new AccesosrolModel();
        $model->delete($id);
        return redirect()->to('/accesosrol')->with('success', 'Acceso eliminado correctamente.');
    }
}
