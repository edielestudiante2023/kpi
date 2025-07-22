<?php

namespace App\Controllers;

use App\Models\IndicadorPerfilModel;
use App\Models\IndicadorModel;
use App\Models\UserModel; // Agregado para validar relaciones
use CodeIgniter\Controller;

class IndicadorPerfilController extends Controller
{
    protected $indicadorPerfilModel;
    protected $indicadorModel;
    protected $userModel;

    public function __construct()
    {
        helper(['form', 'url']);
        $this->indicadorPerfilModel = new IndicadorPerfilModel();
        $this->indicadorModel = new IndicadorModel();
        $this->userModel = new UserModel();
    }

    public function listIndicadorPerfil()
    {
        $indicadores_perfil = $this->indicadorPerfilModel->getIndicadoresConCargoYArea();

        return view('management/list_indicador_perfil', [
            'indicadores_perfil' => $indicadores_perfil
        ]);
    }

    public function addIndicadorPerfil()
    {
        $perfilCargoModel = new \App\Models\PerfilCargoModel();
        $areaModel = new \App\Models\AreaModel();

        $data['indicadores'] = $this->indicadorModel->findAll();
        $data['perfiles'] = $perfilCargoModel->findAll();
        $data['areas'] = $areaModel->where('estado_area', 'activa')->findAll();

        return view('management/add_indicador_perfil', $data);
    }

    public function addIndicadorPerfilPost()
    {
        $this->indicadorPerfilModel->save($this->request->getPost());
        return redirect()->to('/indicadores_perfil')->with('success', 'Asignación creada correctamente.');
    }

    public function editIndicadorPerfil($id)
    {
        $perfilCargoModel = new \App\Models\PerfilCargoModel();
        $areaModel        = new \App\Models\AreaModel();

        $registro = $this->indicadorPerfilModel->find($id);

        $perfil = $perfilCargoModel->find($registro['id_perfil_cargo']);
        $registro['area'] = $perfil['area'];

        $data['registro']    = $registro;
        $data['indicadores'] = $this->indicadorModel->findAll();
        $data['perfiles']    = $perfilCargoModel->findAll();
        $data['areas']       = $areaModel->where('estado_area', 'activa')->findAll();

        return view('management/edit_indicador_perfil', $data);
    }

    public function editIndicadorPerfilPost($id)
    {
        $this->indicadorPerfilModel->update($id, $this->request->getPost());
        return redirect()->to('/indicadores_perfil')->with('success', 'Asignación actualizada correctamente.');
    }

    public function deleteIndicadorPerfil($id)
    {
        // 1) Consultar el registro para obtener el id_perfil_cargo
        $registro = $this->indicadorPerfilModel->find($id);

        if (!$registro) {
            return redirect()->to('/indicadores_perfil')->with('error', 'No se encontró la asignación a eliminar.');
        }

        $idPerfilCargo = $registro['id_perfil_cargo'];

        // 2) Verificar si hay usuarios asociados a ese perfil
        $usuariosRelacionados = $this->userModel
            ->where('id_perfil_cargo', $idPerfilCargo)
            ->countAllResults();

        if ($usuariosRelacionados > 0) {
            return redirect()->to('/indicadores_perfil')
                ->with('error', 'No puedes eliminar esta asignación porque hay usuarios vinculados al perfil de cargo.');
        }

        // 3) Eliminar la asignación
        $this->indicadorPerfilModel->delete($id);

        return redirect()->to('/indicadores_perfil')->with('success', 'Asignación eliminada correctamente.');
    }
}
