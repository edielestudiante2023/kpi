<?php

namespace App\Controllers;

use App\Models\PreferenciaNotificacionModel;

class PreferenciaNotificacionController extends BaseController
{
    protected $preferenciaModel;

    public function __construct()
    {
        $this->preferenciaModel = new PreferenciaNotificacionModel();
        helper(['url', 'form']);
    }

    /**
     * Muestra el formulario de preferencias de notificación
     */
    public function index()
    {
        $idUsuario = session()->get('id_users');

        $data = [
            'preferencias' => $this->preferenciaModel->getPreferenciasUsuario($idUsuario)
        ];

        return view('preferencias/notificaciones', $data);
    }

    /**
     * Guarda las preferencias de notificación
     */
    public function guardar()
    {
        $idUsuario = session()->get('id_users');

        $datos = [
            'notif_asignacion' => $this->request->getPost('notif_asignacion') ? 1 : 0,
            'notif_cambio_estado' => $this->request->getPost('notif_cambio_estado') ? 1 : 0,
            'notif_comentarios' => $this->request->getPost('notif_comentarios') ? 1 : 0,
            'notif_vencimiento' => $this->request->getPost('notif_vencimiento') ? 1 : 0,
            'resumen_diario' => $this->request->getPost('resumen_diario') ? 1 : 0
        ];

        $this->preferenciaModel->actualizarPreferencias($idUsuario, $datos);

        return redirect()->to('/preferencias/notificaciones')
            ->with('success', 'Preferencias guardadas correctamente.');
    }
}
