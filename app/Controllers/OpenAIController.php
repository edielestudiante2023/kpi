<?php

namespace App\Controllers;

use App\Libraries\OpenAIService;

class OpenAIController extends BaseController
{
    protected OpenAIService $openai;

    public function __construct()
    {
        $this->openai = new OpenAIService();
    }

    /**
     * Genera un indicador con IA
     * POST /ia/generar-indicador
     */
    public function generarIndicador()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso no permitido']);
        }

        $descripcion = $this->request->getPost('descripcion');
        $ajuste = $this->request->getPost('ajuste');
        $contextoPrevio = $this->request->getPost('contexto_previo');

        // Si hay ajuste, permitir descripción vacía
        if (empty($descripcion) && empty($ajuste)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Debes proporcionar una descripción de lo que quieres medir'
            ]);
        }

        if (strlen($descripcion) < 10 && empty($ajuste)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'La descripción es muy corta. Sé más específico sobre lo que quieres medir.'
            ]);
        }

        // Si hay ajuste, pasar contexto previo
        $contexto = null;
        if (!empty($ajuste) && !empty($contextoPrevio)) {
            $contexto = json_decode($contextoPrevio, true);
        }

        $resultado = $this->openai->generarIndicador($descripcion, $ajuste, $contexto);

        return $this->response->setJSON($resultado);
    }

    /**
     * Genera una actividad con IA
     * POST /ia/generar-actividad
     */
    public function generarActividad()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso no permitido']);
        }

        $descripcion = $this->request->getPost('descripcion');
        $ajuste = $this->request->getPost('ajuste');
        $contextoPrevio = $this->request->getPost('contexto_previo');

        // Si hay ajuste, permitir descripción vacía
        if (empty($descripcion) && empty($ajuste)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Debes proporcionar una descripción de la actividad'
            ]);
        }

        if (strlen($descripcion) < 10 && empty($ajuste)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'La descripción es muy corta. Describe mejor qué actividad necesitas.'
            ]);
        }

        // Si hay ajuste, pasar contexto previo
        $contexto = null;
        if (!empty($ajuste) && !empty($contextoPrevio)) {
            $contexto = json_decode($contextoPrevio, true);
        }

        $resultado = $this->openai->generarActividad($descripcion, $ajuste, $contexto);

        return $this->response->setJSON($resultado);
    }

    /**
     * Verifica si la API está configurada
     * GET /ia/status
     */
    public function status()
    {
        return $this->response->setJSON([
            'configured' => $this->openai->isConfigured()
        ]);
    }
}
