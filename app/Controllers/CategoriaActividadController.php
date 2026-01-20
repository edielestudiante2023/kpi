<?php

namespace App\Controllers;

use App\Models\CategoriaActividadModel;

class CategoriaActividadController extends BaseController
{
    protected CategoriaActividadModel $categoriaModel;

    public function __construct()
    {
        $this->categoriaModel = new CategoriaActividadModel();
    }

    /**
     * Lista todas las categorías
     */
    public function index()
    {
        $data = [
            'categorias' => $this->categoriaModel->orderBy('nombre_categoria', 'ASC')->findAll()
        ];

        return view('categorias_actividad/list_categorias', $data);
    }

    /**
     * Formulario para crear categoría
     */
    public function create()
    {
        return view('categorias_actividad/add_categoria');
    }

    /**
     * Procesar creación de categoría
     */
    public function store()
    {
        $rules = [
            'nombre_categoria' => 'required|min_length[2]|max_length[100]|is_unique[categorias_actividad.nombre_categoria]',
            'descripcion'      => 'permit_empty|max_length[500]',
            'color'            => 'required|max_length[20]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $data = [
            'nombre_categoria' => $this->request->getPost('nombre_categoria'),
            'descripcion'      => $this->request->getPost('descripcion'),
            'color'            => $this->request->getPost('color'),
            'estado'           => $this->request->getPost('estado') ?? 'activa'
        ];

        $this->categoriaModel->insert($data);

        return redirect()->to('/categorias-actividad')
            ->with('success', 'Categoría creada exitosamente.');
    }

    /**
     * Formulario para editar categoría
     */
    public function edit($id)
    {
        $categoria = $this->categoriaModel->find($id);

        if (!$categoria) {
            return redirect()->to('/categorias-actividad')
                ->with('error', 'Categoría no encontrada.');
        }

        $data = [
            'categoria' => $categoria
        ];

        return view('categorias_actividad/edit_categoria', $data);
    }

    /**
     * Procesar edición de categoría
     */
    public function update($id)
    {
        $categoria = $this->categoriaModel->find($id);

        if (!$categoria) {
            return redirect()->to('/categorias-actividad')
                ->with('error', 'Categoría no encontrada.');
        }

        $rules = [
            'nombre_categoria' => "required|min_length[2]|max_length[100]|is_unique[categorias_actividad.nombre_categoria,id_categoria,{$id}]",
            'descripcion'      => 'permit_empty|max_length[500]',
            'color'            => 'required|max_length[20]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $data = [
            'nombre_categoria' => $this->request->getPost('nombre_categoria'),
            'descripcion'      => $this->request->getPost('descripcion'),
            'color'            => $this->request->getPost('color'),
            'estado'           => $this->request->getPost('estado')
        ];

        $this->categoriaModel->update($id, $data);

        return redirect()->to('/categorias-actividad')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Eliminar categoría
     */
    public function delete($id)
    {
        $categoria = $this->categoriaModel->find($id);

        if (!$categoria) {
            return redirect()->to('/categorias-actividad')
                ->with('error', 'Categoría no encontrada.');
        }

        // Verificar si hay actividades usando esta categoría
        $actividadModel = new \App\Models\ActividadModel();
        $actividadesConCategoria = $actividadModel->where('id_categoria', $id)->countAllResults();

        if ($actividadesConCategoria > 0) {
            return redirect()->to('/categorias-actividad')
                ->with('error', "No se puede eliminar: hay {$actividadesConCategoria} actividad(es) usando esta categoría.");
        }

        $this->categoriaModel->delete($id);

        return redirect()->to('/categorias-actividad')
            ->with('success', 'Categoría eliminada exitosamente.');
    }

    /**
     * Cambiar estado via AJAX
     */
    public function toggleEstado($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso no permitido']);
        }

        $categoria = $this->categoriaModel->find($id);

        if (!$categoria) {
            return $this->response->setJSON(['success' => false, 'message' => 'Categoría no encontrada']);
        }

        $nuevoEstado = $categoria['estado'] === 'activa' ? 'inactiva' : 'activa';
        $this->categoriaModel->update($id, ['estado' => $nuevoEstado]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Estado actualizado',
            'nuevo_estado' => $nuevoEstado
        ]);
    }
}
