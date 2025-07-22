<?php namespace App\Controllers;

use App\Models\AreaModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class AreasController extends BaseController
{
    /** @var AreaModel */
    protected $areaModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->areaModel = new AreaModel();
    }

    // Listar áreas
    public function listAreas()
    {
        $data['areas'] = $this->areaModel->orderBy('nombre_area', 'ASC')->findAll();
        return view('management/list_areas', $data);
    }

    // Formulario crear área
    public function addAreas()
    {
        return view('management/add_areas');
    }

    // Procesar creación de área
    public function addAreasPost()
    {
        $rules = [
            'nombre_area'      => 'required|is_unique[areas.nombre_area]',
            'descripcion_area' => 'permit_empty',
            'estado_area'      => 'required|in_list[0,1]'
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                             ->with('errors', $this->validator->getErrors())
                             ->withInput();
        }
        $this->areaModel->insert($this->request->getPost());
        return redirect()->to('/areas')->with('success', 'Área creada.');
    }

    // Formulario editar área
    public function editAreas($id)
    {
        $area = $this->areaModel->find($id);
        if (! $area) {
            throw new PageNotFoundException("Área con ID $id no existe");
        }
        return view('management/edit_areas', ['area' => $area]);
    }

    // Procesar edición de área
    public function editAreasPost($id)
    {
        $rules = [
            'nombre_area'      => "required|is_unique[areas.nombre_area,nombre_area,{$id},id_areas]",
            'descripcion_area' => 'permit_empty',
            'estado_area'      => 'required|in_list[0,1]'
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                             ->with('errors', $this->validator->getErrors())
                             ->withInput();
        }
        $this->areaModel->update($id, $this->request->getPost());
        return redirect()->to('/areas')->with('success', 'Área actualizada.');
    }

    // Eliminar área
    public function deleteAreas($id)
    {
        $this->areaModel->delete($id);
        return redirect()->to('/areas')->with('success', 'Área eliminada.');
    }
}

