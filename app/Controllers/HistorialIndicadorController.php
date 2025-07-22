<?php

namespace App\Controllers;

use App\Models\HistorialIndicadorModel;
use App\Models\IndicadorPerfilModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class HistorialIndicadorController extends BaseController
{
    /** @var HistorialIndicadorModel */
    protected $histModel;
    /** @var IndicadorPerfilModel */
    protected $ipModel;
    /** @var UserModel */
    protected $userModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->histModel = new HistorialIndicadorModel();
        $this->ipModel   = new IndicadorPerfilModel();
        $this->userModel = new UserModel();
    }

    /**
     * Lista todo el historial de indicadores con campos extendidos
     */
    public function listHistorialIndicador()
    {
        $data['records'] = $this->histModel
            ->select([
                'historial_indicadores.*',
                'ip.id_indicador_perfil',
                'ip.id_indicador AS id_indicador_asignado',
                'i.id_indicador',
                'i.nombre         AS indicador',
                'i.periodicidad',
                'i.ponderacion',
                'i.meta_valor',
                'i.meta_descripcion',
                'i.tipo_meta',
                'i.metodo_calculo',
                'i.unidad',
                'i.objetivo_proceso',
                'i.objetivo_calidad',
                'i.tipo_aplicacion',
                'i.created_at',
                'p.nombre_cargo   AS perfil',
                'u.nombre_completo AS usuario',
            ])
            ->join('indicadores_perfil ip', 'ip.id_indicador_perfil = historial_indicadores.id_indicador_perfil')
            ->join('indicadores i',           'i.id_indicador = ip.id_indicador')
            ->join('perfiles_cargo p',        'p.id_perfil_cargo = ip.id_perfil_cargo')
            ->join('users u',                 'u.id_users = historial_indicadores.id_usuario')
            ->orderBy('historial_indicadores.fecha_registro', 'DESC')
            ->findAll();

        return view('management/list_historial_indicador', $data);
    }

    /**
     * Muestra formulario para crear un nuevo registro de historial
     */
    public function addHistorialIndicador()
    {
        // Opciones de asignación incluyendo campos extendidos del indicador
        $data['asignaciones'] = $this->ipModel
            ->select([
                'indicadores_perfil.id_indicador_perfil',
                'indicadores_perfil.id_indicador',
                'i.nombre           AS nombre_indicador',
                'i.periodicidad',
                'i.ponderacion',
                'i.meta_valor',
                'i.meta_descripcion',
            ])
            ->join('indicadores i', 'i.id_indicador = indicadores_perfil.id_indicador')
            ->findAll();

        $data['users'] = $this->userModel->where('activo', 1)->findAll();

        return view('management/add_historial_indicador', $data);
    }

    /**
     * Procesa el POST de creación de historial
     */
    public function addHistorialIndicadorPost()
    {
        $rules = [
            'id_indicador_perfil' => 'required|integer',
            'id_usuario'          => 'required|integer',
            'periodo'             => 'required',
            'valores_json'        => 'required',
            'resultado_real'      => 'required|decimal',
            'comentario'          => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $this->histModel->insert($this->request->getPost());
        return redirect()->to('/historial_indicador')
            ->with('success', 'Registro creado.');
    }

    /**
     * Muestra formulario para editar un registro existente
     */
    public function editHistorialIndicador($id)
    {
        $record = $this->histModel
            ->select([
                'historial_indicadores.*',
                'ip.id_indicador_perfil',
                'ip.id_indicador       AS id_indicador_asignado',
                'i.id_indicador',
                'i.nombre              AS nombre_indicador',
                'i.periodicidad',
                'i.ponderacion',
                'i.meta_valor',
                'i.meta_descripcion',
                'i.tipo_meta',
                'i.metodo_calculo',
                'i.unidad',
                'i.objetivo_proceso',
                'i.objetivo_calidad',
                'i.tipo_aplicacion',
                'i.created_at',
                
            ])
            ->join('indicadores_perfil ip', 'ip.id_indicador_perfil = historial_indicadores.id_indicador_perfil')
            ->join('indicadores i',           'i.id_indicador = ip.id_indicador')
            ->where('historial_indicadores.id_historial', $id)
            ->first();

        if (! $record) {
            throw new PageNotFoundException("Registro con ID $id no existe");
        }

            $data = [
        'record'       => $record,
        'asignaciones' => $this->ipModel
            ->select([
                'indicadores_perfil.id_indicador_perfil',
                'indicadores_perfil.id_indicador',
                'i.nombre           AS nombre_indicador',
                'i.periodicidad',
                'i.ponderacion',
                'i.meta_valor',
                'i.meta_descripcion',
                'i.tipo_meta',       // ← agregado
                'i.metodo_calculo',  // ← agregado
                'i.unidad',          // ← agregado
                'i.objetivo_proceso', 
                'i.objetivo_calidad',
                'i.tipo_aplicacion',
                'i.created_at',
            ])
            ->join('indicadores i', 'i.id_indicador = indicadores_perfil.id_indicador')
            ->findAll(),
        'users'        => $this->userModel->where('activo', 1)->findAll(),
    ];


        return view('management/edit_historial_indicador', $data);
    }

    /**
     * Procesa el POST de edición de historial
     */
    public function editHistorialIndicadorPost($id)
    {
        $rules = [
            'id_indicador_perfil' => 'required|integer',
            'id_usuario'          => 'required|integer',
            'periodo'             => 'required',
            'valores_json'        => 'required',
            'resultado_real'      => 'required|decimal',
            'comentario'          => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $this->histModel->update($id, $this->request->getPost());
        return redirect()->to('/historial_indicador')
            ->with('success', 'Registro actualizado.');
    }

    /**
     * Elimina un registro de historial
     */
    public function deleteHistorialIndicador($id)
    {
        $this->histModel->delete($id);
        return redirect()->to('/historial_indicador')
            ->with('success', 'Registro eliminado.');
    }
}
