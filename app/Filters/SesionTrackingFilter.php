<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\SesionUsuarioModel;

class SesionTrackingFilter implements FilterInterface
{
    /**
     * Se ejecuta antes de cada request
     * Verifica si hay sesion de tracking activa, si no la crea
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Solo para usuarios logueados
        $idUsuario = session()->get('id_users');
        if (!$idUsuario) {
            return;
        }

        // No trackear requests AJAX de heartbeat para evitar loops
        $uri = $request->getUri()->getPath();
        if (strpos($uri, 'sesion/heartbeat') !== false) {
            return;
        }

        // Verificar si ya tiene token de sesion
        $tokenSesion = session()->get('sesion_token');

        if (!$tokenSesion) {
            // Crear nueva sesion de tracking
            $sesionModel = new SesionUsuarioModel();
            $resultado = $sesionModel->iniciarSesion(
                $idUsuario,
                $request->getIPAddress(),
                $request->getUserAgent()->getAgentString()
            );

            session()->set('sesion_token', $resultado['token']);
            session()->set('sesion_id', $resultado['id_sesion']);
        }
    }

    /**
     * Se ejecuta despues de cada request
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No necesitamos hacer nada despues del request
    }
}
